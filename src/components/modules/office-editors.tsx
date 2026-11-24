"use client";
// ── Nyansapo Office editors (wave 56) ──
// Three sibling editors behind the Office file manager:
//   • DocEditor   — rich-text word processor (contentEditable + execCommand)
//   • SheetEditor — spreadsheet with real formula evaluation (SUM/AVG/MIN/MAX/
//                   COUNT, ranges, arithmetic), cell styles & GH₵ formats
//   • SlideEditor — presentation builder with Ghana-inspired themes,
//                   speaker notes and a fullscreen present mode
// All three autosave into OfficeFile.contentJson and speak the same props.
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { apiPost } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { useToast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import {
  Bold, Italic, Underline, Strikethrough, List, ListOrdered, AlignLeft, AlignCenter, AlignRight, AlignJustify,
  Undo2, Redo2, Minus, Eraser, Image as ImageIcon, Save, Printer, Download, Type, Link2,
  Plus, Trash2, Copy, ChevronLeft, ChevronRight, ArrowUp, ArrowDown, Play, X,
} from "lucide-react";

export type OfficeFileLike = {
  id: string; kind: string; title: string; folder: string; contentJson: string;
};
type EditorProps = {
  file: OfficeFileLike;
  onSaved?: () => void;
  onDirtyChange?: (dirty: boolean) => void;
};

/* ── shared autosave hook ──
   Plain closures + a ref to the latest doSave, so the Ctrl+S listener always
   saves the newest content without stale-closure traps. */
function useAutosave(file: OfficeFileLike, build: () => string, onSaved?: () => void, onDirtyChange?: (d: boolean) => void, delay = 2500) {
  const { toast } = useToast();
  const dirtyRef = useRef(false);
  const timerRef = useRef<number | null>(null);

  const doSave = async () => {
    if (!dirtyRef.current) return;
    dirtyRef.current = false;
    onDirtyChange?.(false);
    try {
      await apiPost(`/api/school/${getSchoolId()}/ops/office`, { action: "save", id: file.id, contentJson: build() });
      onSaved?.();
    } catch (e: any) {
      dirtyRef.current = true;
      onDirtyChange?.(true);
      toast({ title: "Save failed", description: e.message, variant: "destructive" });
    }
  };

  const markDirty = () => {
    dirtyRef.current = true;
    onDirtyChange?.(true);
    if (timerRef.current) window.clearTimeout(timerRef.current);
    timerRef.current = window.setTimeout(() => void doSave(), delay);
  };

  const latest = useRef({ doSave });
  useEffect(() => {
    latest.current.doSave = doSave;
  });

  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "s") { e.preventDefault(); dirtyRef.current = true; void latest.current.doSave(); }
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, []);

  return { markDirty, doSave };
}

/* school id is needed by the autosave hook — the shell stores it; editors get
   it via a module-level setter set by the manager on mount. */
let _schoolId = "";
export function setOfficeSchoolId(id: string) { _schoolId = id; }
const getSchoolId = () => _schoolId;

/* ═══════════════════════════ DOC EDITOR ═══════════════════════════ */

const exec = (cmd: string, val?: string) => {
  document.execCommand(cmd, false, val);
};

/* toolbar button shared by the doc editor */
function TBtn({ icon, label, onClick }: { icon: React.ReactNode; label: string; onClick: () => void }) {
  return (
    <Button type="button" variant="ghost" size="icon" className="size-8" title={label} aria-label={label}
      onMouseDown={(e) => e.preventDefault()} onClick={onClick}>{icon}</Button>
  );
}

function DocEditor({ file, onSaved, onDirtyChange }: EditorProps) {
  const seed = useMemo(() => { try { return JSON.parse(file.contentJson)?.html ?? "<p><br></p>"; } catch { return "<p><br></p>"; } }, [file.contentJson]);
  const ref = useRef<HTMLDivElement>(null);
  const [dirty, setDirty] = useState(false);
  const [words, setWords] = useState(0);
  const [saving, setSaving] = useState(false);

  const build = useCallback(() => JSON.stringify({ html: ref.current?.innerHTML ?? "<p><br></p>" }), []);
  const { markDirty, doSave } = useAutosave(file, build, onSaved, onDirtyChange);

  const countWords = () => setWords((ref.current?.innerText ?? "").trim().split(/\s+/).filter(Boolean).length);

  useEffect(() => {
    if (ref.current && !ref.current.innerHTML) ref.current.innerHTML = seed;
    const id = window.setTimeout(countWords, 0);
    return () => window.clearTimeout(id);
     
  }, []);

  const onInput = () => { setDirty(true); markDirty(); countWords(); };

  const saveNow = async () => {
    setSaving(true);
    markDirty();
    await doSave();
    setSaving(false);
  };

  const download = () => {
    const html = `<!doctype html><html><head><meta charset="utf-8"><title>${file.title}</title><style>body{font-family:Georgia,serif;max-width:760px;margin:40px auto;line-height:1.6;padding:0 20px}img{max-width:100%}blockquote{border-left:3px solid #ccc;margin-left:0;padding-left:16px;color:#555}</style></head><body>${ref.current?.innerHTML ?? ""}</body></html>`;
    const a = document.createElement("a");
    a.href = URL.createObjectURL(new Blob([html], { type: "text/html" }));
    a.download = `${file.title}.html`;
    a.click();
    URL.revokeObjectURL(a.href);
  };
  const printDoc = () => {
    const w = window.open("", "_blank");
    if (!w) return;
    w.document.write(`<!doctype html><html><head><title>${file.title}</title></head><body style="font-family:Georgia,serif;line-height:1.7">${ref.current?.innerHTML ?? ""}<script>onload=()=>print()<\/script></body></html>`);
    w.document.close();
  };
  const insertImage = async (f: File) => {
    const dataUrl = await new Promise<string>((res, rej) => {
      const img = new Image();
      const url = URL.createObjectURL(f);
      img.onload = () => {
        const scale = Math.min(1, 800 / Math.max(1, img.width));
        const c = document.createElement("canvas");
        c.width = Math.round(img.width * scale); c.height = Math.round(img.height * scale);
        c.getContext("2d")!.drawImage(img, 0, 0, c.width, c.height);
        URL.revokeObjectURL(url);
        res(c.toDataURL("image/png"));
      };
      img.onerror = () => { URL.revokeObjectURL(url); rej(new Error("unreadable image")); };
      img.src = url;
    });
    ref.current?.focus();
    exec("insertImage", dataUrl);
    onInput();
  };
  const insertLink = () => {
    const url = window.prompt("Link URL", "https://");
    if (url) { ref.current?.focus(); exec("createLink", url); onInput(); }
  };

  return (
    <div className="flex flex-col h-full min-h-0">
      {/* toolbar */}
      <div className="flex flex-wrap items-center gap-1 border-b bg-muted/40 px-2 py-1.5">
        <TBtn icon={<Undo2 className="size-4" />} label="Undo" onClick={() => { exec("undo"); onInput(); }} />
        <TBtn icon={<Redo2 className="size-4" />} label="Redo" onClick={() => { exec("redo"); onInput(); }} />
        <span className="mx-1 h-5 w-px bg-border" />
        <Select defaultValue="p" onValueChange={(v) => { exec("formatBlock", `<${v}>`); onInput(); }}>
          <SelectTrigger className="h-8 w-32" onMouseDown={(e) => e.preventDefault()}><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem value="p">Normal text</SelectItem>
            <SelectItem value="h1">Heading 1</SelectItem>
            <SelectItem value="h2">Heading 2</SelectItem>
            <SelectItem value="h3">Heading 3</SelectItem>
            <SelectItem value="blockquote">Quote</SelectItem>
          </SelectContent>
        </Select>
        <Select defaultValue="3" onValueChange={(v) => { exec("fontSize", v); onInput(); }}>
          <SelectTrigger className="h-8 w-16" onMouseDown={(e) => e.preventDefault()}><SelectValue /></SelectTrigger>
          <SelectContent>{["1", "2", "3", "4", "5", "6", "7"].map((n) => <SelectItem key={n} value={n}>{n}</SelectItem>)}</SelectContent>
        </Select>
        <span className="mx-1 h-5 w-px bg-border" />
        <TBtn icon={<Bold className="size-4" />} label="Bold" onClick={() => { exec("bold"); onInput(); }} />
        <TBtn icon={<Italic className="size-4" />} label="Italic" onClick={() => { exec("italic"); onInput(); }} />
        <TBtn icon={<Underline className="size-4" />} label="Underline" onClick={() => { exec("underline"); onInput(); }} />
        <TBtn icon={<Strikethrough className="size-4" />} label="Strikethrough" onClick={() => { exec("strikeThrough"); onInput(); }} />
        <span className="mx-1 h-5 w-px bg-border" />
        <TBtn icon={<List className="size-4" />} label="Bullet list" onClick={() => { exec("insertUnorderedList"); onInput(); }} />
        <TBtn icon={<ListOrdered className="size-4" />} label="Numbered list" onClick={() => { exec("insertOrderedList"); onInput(); }} />
        <TBtn icon={<AlignLeft className="size-4" />} label="Align left" onClick={() => { exec("justifyLeft"); onInput(); }} />
        <TBtn icon={<AlignCenter className="size-4" />} label="Center" onClick={() => { exec("justifyCenter"); onInput(); }} />
        <TBtn icon={<AlignRight className="size-4" />} label="Align right" onClick={() => { exec("justifyRight"); onInput(); }} />
        <TBtn icon={<AlignJustify className="size-4" />} label="Justify" onClick={() => { exec("justifyFull"); onInput(); }} />
        <span className="mx-1 h-5 w-px bg-border" />
        <TBtn icon={<Minus className="size-4" />} label="Horizontal rule" onClick={() => { exec("insertHorizontalRule"); onInput(); }} />
        <TBtn icon={<Link2 className="size-4" />} label="Insert link" onClick={insertLink} />
        <label className="inline-flex cursor-pointer" title="Insert image">
          <ImageIcon className="size-4 mx-2" />
          <input type="file" accept="image/*" className="hidden" onChange={(e) => e.target.files?.[0] && insertImage(e.target.files[0]).then(onInput).catch(() => {})} />
        </label>
        <TBtn icon={<Eraser className="size-4" />} label="Clear formatting" onClick={() => { exec("removeFormat"); onInput(); }} />
        <div className="ml-auto flex items-center gap-1.5">
          <span className="text-xs text-muted-foreground">{words} words · autosaves</span>
          <Button size="sm" variant="outline" onClick={download}><Download className="size-3.5 mr-1" /> .html</Button>
          <Button size="sm" variant="outline" onClick={printDoc}><Printer className="size-3.5 mr-1" /> Print</Button>
          <Button size="sm" onClick={saveNow} disabled={saving}><Save className="size-3.5 mr-1" /> {saving ? "Saving…" : dirty ? "Save" : "Saved"}</Button>
        </div>
      </div>
      {/* page */}
      <div className="flex-1 overflow-y-auto bg-neutral-200/70 dark:bg-neutral-900 p-4 lg:p-8">
        <div
          ref={ref}
          contentEditable
          suppressContentEditableWarning
          onInput={onInput}
          className="mx-auto min-h-[600px] max-w-[820px] bg-white dark:bg-neutral-950 text-neutral-900 dark:text-neutral-100 shadow-lg px-12 py-10 outline-none rounded-sm"
          style={{ fontFamily: "Georgia, 'Times New Roman', serif", lineHeight: 1.7 }}
        />
      </div>
    </div>
  );
}

/* ═══════════════════════════ SHEET EDITOR ═══════════════════════════ */

type Cell = { v: string; b?: boolean; i?: boolean; a?: "left" | "center" | "right"; fmt?: "num" | "ghs" | "pct" };
type SheetContent = { cols: number; rows: number; cells: Record<string, Cell> };

const colName = (i: number): string => {
  let s = "";
  let n = i;
  while (n >= 0) { s = String.fromCharCode(65 + (n % 26)) + s; n = Math.floor(n / 26) - 1; }
  return s;
};
const colIndex = (name: string): number => {
  let n = 0;
  for (const ch of name) n = n * 26 + (ch.charCodeAt(0) - 64);
  return n - 1;
};
const cellKey = (c: number, r: number) => `${colName(c)}${r + 1}`;

function evaluateSheet(content: SheetContent): Record<string, string> {
  const out: Record<string, string> = {};
  const visiting = new Set<string>();

  const numOf = (key: string): number => {
    const raw = content.cells[key]?.v ?? "";
    if (!raw) return 0;
    if (raw.startsWith("=")) {
      if (visiting.has(key)) return NaN;
      visiting.add(key);
      const s = evalFormula(raw.slice(1));
      visiting.delete(key);
      const n = parseFloat(s);
      return isFinite(n) ? n : 0;
    }
    const n = parseFloat(raw.replace(/,/g, ""));
    return isFinite(n) ? n : 0;
  };

  const expandArg = (arg: string): number[] => {
    const m = /^([A-Z]{1,2})(\d{1,4}):([A-Z]{1,2})(\d{1,4})$/.exec(arg.trim().toUpperCase());
    if (m) {
      const c1 = colIndex(m[1]), r1 = parseInt(m[2], 10) - 1;
      const c2 = colIndex(m[3]), r2 = parseInt(m[4], 10) - 1;
      const nums: number[] = [];
      for (let c = Math.min(c1, c2); c <= Math.max(c1, c2); c++)
        for (let r = Math.min(r1, r2); r <= Math.max(r1, r2); r++) {
          const v = (content.cells[cellKey(c, r)]?.v ?? "").trim();
          if (v !== "" && !v.startsWith("=")) { const n = parseFloat(v.replace(/,/g, "")); if (isFinite(n)) nums.push(n); }
          else if (v.startsWith("=")) nums.push(numOf(cellKey(c, r)));
        }
      return nums;
    }
    const t = arg.trim().toUpperCase();
    if (/^[A-Z]{1,2}\d{1,4}$/.test(t)) return [numOf(t)];
    const n = parseFloat(arg.replace(/,/g, ""));
    return isFinite(n) ? [n] : [];
  };

  const applyFn = (name: string, args: string): string => {
    const nums = args.split(/[;,]/).flatMap(expandArg);
    const f = name.toUpperCase();
    let v = 0;
    if (f === "SUM") v = nums.reduce((a, b) => a + b, 0);
    else if (f === "AVERAGE" || f === "AVG") v = nums.length ? nums.reduce((a, b) => a + b, 0) / nums.length : 0;
    else if (f === "MIN") v = nums.length ? Math.min(...nums) : 0;
    else if (f === "MAX") v = nums.length ? Math.max(...nums) : 0;
    else if (f === "COUNT" || f === "COUNTA") v = nums.length;
    else return "#NAME?";
    return String(Math.round(v * 1e6) / 1e6);
  };

  const evalFormula = (raw: string): string => {
    if (raw.trim().toUpperCase().startsWith("#CIRC")) return "#CIRC!";
    try {
      // function calls (no nested parentheses in args)
      let expr = raw;
      for (let guard = 0; guard < 8; guard++) {
        const m = /(SUM|AVERAGE|AVG|MIN|MAX|COUNT|COUNTA)\s*\(([^()]*?)\)/i.exec(expr);
        if (!m) break;
        expr = expr.slice(0, m.index) + applyFn(m[1], m[2]) + expr.slice(m.index + m[0].length);
      }
      // bare range outside a function → invalid
      if (/:/.test(expr.replace(/"[^"]*"/g, ""))) return "#ERR";
      // replace cell refs with their numeric value
      expr = expr.replace(/\b([A-Z]{1,2})(\d{1,4})\b/g, (_, c, r) => {
        const ci = colIndex(c), ri = parseInt(r, 10) - 1;
        if (ci < 0 || ci >= content.cols || ri < 0 || ri >= content.rows) return "0";
        return String(numOf(cellKey(ci, ri)));
      });
      // string literals stay for + concatenation → drop quotes for arithmetic only
      if (!/^[-+*/().,\d\s]*$/.test(expr.replace(/"[^"]*"/g, "0"))) return "#ERR";
      const fn = new Function(`"use strict";return (${expr.replace(/,(?=\s*[\d.)])/g, ",")}) || 0;`);
      const result = fn();
      return isFinite(Number(result)) ? String(Math.round(Number(result) * 1e6) / 1e6) : "#ERR";
    } catch {
      return "#ERR";
    }
  };

  for (let c = 0; c < content.cols; c++)
    for (let r = 0; r < content.rows; r++) {
      const key = cellKey(c, r);
      const raw = content.cells[key]?.v;
      if (raw === undefined || raw === "") continue;
      visiting.clear();
      out[key] = raw.startsWith("=") ? evalFormula(raw.slice(1)) : raw;
    }
  return out;
}

function SheetEditor({ file, onSaved, onDirtyChange }: EditorProps) {
  const seed = useMemo<SheetContent>(() => {
    try {
      const p = JSON.parse(file.contentJson);
      return { cols: Math.min(26, Math.max(6, p?.cols ?? 18)), rows: Math.min(200, Math.max(10, p?.rows ?? 40)), cells: p?.cells ?? {} };
    } catch { return { cols: 18, rows: 40, cells: {} }; }
  }, [file.contentJson]);
  const [content, setContent] = useState<SheetContent>(seed);
  const [active, setActive] = useState<{ c: number; r: number }>({ c: 0, r: 0 });
  const [editing, setEditing] = useState<{ c: number; r: number; draft: string } | null>(null);
  const gridRef = useRef<HTMLDivElement>(null);
  const cellInputRef = useRef<HTMLInputElement>(null);

  const build = useCallback(() => JSON.stringify(content), [content]);
  const { markDirty, doSave } = useAutosave(file, build, onSaved, onDirtyChange);
  const [saving, setSaving] = useState(false);

  const computed = useMemo(() => evaluateSheet(content), [content]);

  const setCell = (key: string, patch: Partial<Cell>) =>
    setContent((s) => ({ ...s, cells: { ...s.cells, [key]: { ...s.cells[key], ...patch } } }));

  const commitEdit = (move: "down" | "right" | "none" = "down") => {
    if (!editing) return;
    const key = cellKey(editing.c, editing.r);
    const v = editing.draft.trim();
    setContent((s) => {
      const cells = { ...s.cells };
      if (v === "") delete cells[key]; else cells[key] = { ...cells[key], v };
      return { ...s, cells };
    });
    markDirty();
    const next = move === "down" ? { c: editing.c, r: Math.min(content.rows - 1, editing.r + 1) }
      : move === "right" ? { c: Math.min(content.cols - 1, editing.c + 1), r: editing.r }
      : { c: editing.c, r: editing.r };
    setEditing(null);
    setActive(next);
  };

  const startEdit = (c: number, r: number, initial?: string) => {
    setEditing({ c, r, draft: initial ?? content.cells[cellKey(c, r)]?.v ?? "" });
    window.setTimeout(() => cellInputRef.current?.focus(), 0);
  };

  const onGridKey = (e: React.KeyboardEvent) => {
    if (editing) {
      if (e.key === "Enter") { e.preventDefault(); commitEdit("down"); }
      else if (e.key === "Tab") { e.preventDefault(); commitEdit("right"); }
      else if (e.key === "Escape") { e.preventDefault(); setEditing(null); }
      return;
    }
    const { c, r } = active;
    if (e.key === "ArrowDown") { e.preventDefault(); setActive({ c, r: Math.min(content.rows - 1, r + 1) }); }
    else if (e.key === "ArrowUp") { e.preventDefault(); setActive({ c, r: Math.max(0, r - 1) }); }
    else if (e.key === "ArrowLeft") { e.preventDefault(); setActive({ c: Math.max(0, c - 1), r }); }
    else if (e.key === "ArrowRight") { e.preventDefault(); setActive({ c: Math.min(content.cols - 1, c + 1), r }); }
    else if (e.key === "Enter" || e.key === "F2") { e.preventDefault(); startEdit(c, r); }
    else if (e.key === "Delete" || e.key === "Backspace") {
      e.preventDefault();
      const key = cellKey(c, r);
      setContent((s) => { const cells = { ...s.cells }; delete cells[key]; return { ...s, cells }; });
      markDirty();
    } else if (e.key.length === 1 && !e.ctrlKey && !e.metaKey) {
      e.preventDefault(); startEdit(c, r, e.key);
    }
  };

  const display = (key: string, raw: string | undefined, calc: string | undefined): string => {
    if (raw === undefined || raw === "") return "";
    if (raw.startsWith("=")) return calc ?? "";
    return raw;
  };
  const fmtDisplay = (key: string, shown: string): string => {
    const fmt = content.cells[key]?.fmt;
    const n = parseFloat(shown.replace(/,/g, ""));
    if (!fmt || !isFinite(n) || shown === "") return shown;
    if (fmt === "ghs") return `GH₵ ${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    if (fmt === "pct") return `${(n * 100).toLocaleString(undefined, { maximumFractionDigits: 1 })}%`;
    return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };

  const exportCsv = () => {
    const rows: string[][] = [];
    for (let r = 0; r < content.rows; r++) {
      const row: string[] = [];
      let any = false;
      for (let c = 0; c < content.cols; c++) {
        const key = cellKey(c, r);
        const shown = display(key, content.cells[key]?.v, computed[key]);
        if (shown !== "") any = true;
        row.push(shown);
      }
      if (any) rows.push(row);
    }
    const csv = rows.map((r) => r.map((x) => `"${x.replace(/"/g, '""')}"`).join(",")).join("\n");
    const a = document.createElement("a");
    a.href = URL.createObjectURL(new Blob([csv], { type: "text/csv" }));
    a.download = `${file.title}.csv`;
    a.click();
    URL.revokeObjectURL(a.href);
  };

  const activeKey = cellKey(active.c, active.r);
  const activeCell = content.cells[activeKey];
  const style = (key: string) => content.cells[key];

  return (
    <div className="flex flex-col h-full min-h-0">
      {/* formula bar + toolbar */}
      <div className="border-b bg-muted/40 px-2 py-1.5 space-y-1.5">
        <div className="flex flex-wrap items-center gap-1.5">
          <Button type="button" variant="ghost" size="icon" className={cn("size-8", activeCell?.b && "bg-primary/10")} title="Bold"
            onClick={() => { setCell(activeKey, { b: !activeCell?.b }); markDirty(); }}><Bold className="size-4" /></Button>
          <Button type="button" variant="ghost" size="icon" className={cn("size-8", activeCell?.i && "bg-primary/10")} title="Italic"
            onClick={() => { setCell(activeKey, { i: !activeCell?.i }); markDirty(); }}><Italic className="size-4" /></Button>
          <Select value={activeCell?.a ?? "left"} onValueChange={(v) => { setCell(activeKey, { a: v as any }); markDirty(); }}>
            <SelectTrigger className="h-8 w-24"><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem value="left"><AlignLeft className="size-3.5" /></SelectItem>
              <SelectItem value="center"><AlignCenter className="size-3.5" /></SelectItem>
              <SelectItem value="right"><AlignRight className="size-3.5" /></SelectItem>
            </SelectContent>
          </Select>
          <Select value={activeCell?.fmt ?? "plain"} onValueChange={(v) => { setCell(activeKey, { fmt: v === "plain" ? undefined : (v as any) }); markDirty(); }}>
            <SelectTrigger className="h-8 w-36"><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem value="plain">Plain</SelectItem>
              <SelectItem value="num">Number (2dp)</SelectItem>
              <SelectItem value="ghs">Ghana cedi (GH₵)</SelectItem>
              <SelectItem value="pct">Percent</SelectItem>
            </SelectContent>
          </Select>
          <span className="mx-1 h-5 w-px bg-border" />
          <Button type="button" variant="ghost" size="sm" className="h-8 text-xs"
            onClick={() => { setContent((s) => ({ ...s, rows: Math.min(200, s.rows + 10) })); markDirty(); }}>
            <Plus className="size-3.5 mr-0.5" />10 rows</Button>
          <Button type="button" variant="ghost" size="sm" className="h-8 text-xs"
            onClick={() => { setContent((s) => ({ ...s, cols: Math.min(26, s.cols + 2) })); markDirty(); }}>
            <Plus className="size-3.5 mr-0.5" />2 cols</Button>
          <div className="ml-auto flex items-center gap-1.5">
            <Button size="sm" variant="outline" onClick={exportCsv}><Download className="size-3.5 mr-1" /> CSV</Button>
            <Button size="sm" onClick={async () => { setSaving(true); markDirty(); await doSave(); setSaving(false); }} disabled={saving}>
              <Save className="size-3.5 mr-1" /> {saving ? "Saving…" : "Save"}
            </Button>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <span className="min-w-14 rounded border bg-background px-2 py-1 text-center text-xs font-semibold tabular-nums">{activeKey}</span>
          <Type className="size-3.5 text-muted-foreground shrink-0" />
          <input
            className="h-8 flex-1 rounded border bg-background px-2 text-sm font-mono"
            value={editing ? editing.draft : activeCell?.v ?? ""}
            placeholder="Type a value, or =SUM(A1:A10)"
            onChange={(e) => editing ? setEditing({ ...editing, draft: e.target.value }) : startEdit(active.c, active.r, e.target.value)}
            onKeyDown={(e) => {
              if (editing) { if (e.key === "Enter") { commitEdit("down"); } else if (e.key === "Escape") setEditing(null); }
              else if (e.key === "Enter") commitEdit("down");
            }}
          />
        </div>
      </div>
      {/* grid */}
      <div ref={gridRef} tabIndex={0} onKeyDown={onGridKey} className="flex-1 overflow-auto outline-none bg-background"
        onClick={(e) => { if (editing && (e.target as HTMLElement).tagName === "TABLE") commitEdit("none"); }}>
        <table className="border-collapse text-sm select-none" style={{ minWidth: content.cols * 88 + 44 }}>
          <thead>
            <tr>
              <th className="sticky top-0 left-0 z-20 w-10 border-b border-r bg-muted text-[10px] text-muted-foreground" />
              {Array.from({ length: content.cols }, (_, c) => (
                <th key={c} className={cn("sticky top-0 z-10 min-w-22 border-b border-r bg-muted px-2 py-1 text-[11px] font-semibold text-muted-foreground",
                  active.c === c && "bg-primary/10 text-primary")}>{colName(c)}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {Array.from({ length: content.rows }, (_, r) => (
              <tr key={r}>
                <th className={cn("sticky left-0 z-10 border-b border-r bg-muted px-1 py-0.5 text-center text-[10px] font-semibold text-muted-foreground",
                  active.r === r && "bg-primary/10 text-primary")}>{r + 1}</th>
                {Array.from({ length: content.cols }, (_, c) => {
                  const key = cellKey(c, r);
                  const cell = content.cells[key];
                  const isEdit = editing && editing.c === c && editing.r === r;
                  const shown = fmtDisplay(key, display(key, cell?.v, computed[key]));
                  return (
                    <td
                      key={c}
                      className={cn(
                        "relative border-b border-r px-1.5 py-0.5 max-w-44 truncate cursor-cell",
                        active.c === c && active.r === r && "outline outline-2 -outline-offset-2 outline-primary",
                        cell?.b && "font-bold", cell?.i && "italic",
                      )}
                      style={{ textAlign: cell?.a ?? "left" }}
                      onMouseDown={(e) => { if (!isEdit) { e.preventDefault(); setActive({ c, r }); gridRef.current?.focus(); } }}
                      onDoubleClick={() => startEdit(c, r)}
                    >
                      {isEdit ? (
                        <input
                          ref={cellInputRef}
                          className="absolute inset-0 z-10 w-full bg-background px-1.5 font-mono text-sm outline outline-2 outline-primary"
                          value={editing.draft}
                          onChange={(e) => setEditing({ ...editing, draft: e.target.value })}
                          onBlur={() => commitEdit("none")}
                          onKeyDown={(e) => {
                            if (e.key === "Enter") { e.preventDefault(); commitEdit("down"); }
                            else if (e.key === "Tab") { e.preventDefault(); commitEdit("right"); }
                            else if (e.key === "Escape") { e.preventDefault(); setEditing(null); }
                          }}
                        />
                      ) : (
                        <span className="block truncate">{shown}</span>
                      )}
                    </td>
                  );
                })}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}

/* ═══════════════════════════ SLIDE EDITOR ═══════════════════════════ */

type Slide = { title: string; body: string; bg: string; notes: string };
type SlideContent = { slides: Slide[]; theme: string };

const THEMES: Record<string, { name: string; bg: string; title: string; body: string }> = {
  kente: { name: "Kente gold", bg: "#0B3D2E", title: "#F5C518", body: "#F3EDDF" },
  independence: { name: "Independence red", bg: "#7E1B12", title: "#F5C518", body: "#FFF3E4" },
  light: { name: "Whiteboard", bg: "#FFFFFF", title: "#1F2937", body: "#374151" },
  chalk: { name: "Chalkboard", bg: "#22302B", title: "#EDE7D3", body: "#C9C4B4" },
};

function SlideEditor({ file, onSaved, onDirtyChange }: EditorProps) {
  const seed = useMemo<SlideContent>(() => {
    try {
      const p = JSON.parse(file.contentJson);
      return { slides: Array.isArray(p?.slides) && p.slides.length ? p.slides : [{ title: "", body: "", bg: "", notes: "" }], theme: p?.theme ?? "kente" };
    } catch { return { slides: [{ title: "", body: "", bg: "", notes: "" }], theme: "kente" }; }
  }, [file.contentJson]);
  const [content, setContent] = useState<SlideContent>(seed);
  const [idx, setIdx] = useState(0);
  const [present, setPresent] = useState(false);
  const [saving, setSaving] = useState(false);

  const build = useCallback(() => JSON.stringify(content), [content]);
  const { markDirty, doSave } = useAutosave(file, build, onSaved, onDirtyChange);

  const slide = content.slides[Math.min(idx, content.slides.length - 1)];
  const theme = THEMES[content.theme] ?? THEMES.kente;

  const patch = (p: Partial<Slide>) => setContent((s) => ({ ...s, slides: s.slides.map((x, i) => (i === idx ? { ...x, ...p } : x)) }));
  const addSlide = () => setContent((s) => ({ ...s, slides: [...s.slides.slice(0, idx + 1), { title: "", body: "", bg: "", notes: "" }, ...s.slides.slice(idx + 1)] }));
  const dupSlide = () => setContent((s) => ({ ...s, slides: [...s.slides.slice(0, idx + 1), { ...s.slides[idx] }, ...s.slides.slice(idx + 1)] }));
  const delSlide = () => setContent((s) => ({ ...s, slides: s.slides.filter((_, i) => i !== idx) }));
  const moveSlide = (dir: -1 | 1) => setContent((s) => {
    const j = idx + dir;
    if (j < 0 || j >= s.slides.length) return s;
    const slides = [...s.slides];
    [slides[idx], slides[j]] = [slides[j], slides[idx]];
    return { ...s, slides };
  });

  // present-mode keyboard nav
  useEffect(() => {
    if (!present) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") setPresent(false);
      if (e.key === "ArrowRight" || e.key === " " || e.key === "PageDown") setIdx((i) => Math.min(content.slides.length - 1, i + 1));
      if (e.key === "ArrowLeft" || e.key === "PageUp") setIdx((i) => Math.max(0, i - 1));
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [present, content.slides.length]);

  const canvas = (s: Slide, t: typeof theme, big = false) => (
    <div className={cn("flex h-full w-full flex-col justify-center gap-4 rounded-sm px-[8%] py-[10%] text-left", big && "shadow-inner")}
      style={{ background: s.bg || t.bg }}>
      <div className={cn("font-bold leading-tight", big ? "text-5xl" : "text-2xl")} style={{ color: t.title }}>
        {s.title || "Untitled slide"}
      </div>
      <div className={cn("whitespace-pre-wrap leading-relaxed", big ? "text-2xl" : "text-[11px]")} style={{ color: t.body }}>
        {s.body || ""}
      </div>
    </div>
  );

  if (present) {
    const s = content.slides[idx];
    return (
      <div className="fixed inset-0 z-50 bg-black flex flex-col" onClick={() => setIdx((i) => Math.min(content.slides.length - 1, i + 1))}>
        <div className="flex-1 flex items-center justify-center p-4">
          <div className="aspect-video w-full max-w-[1100px]">{canvas(s, theme, true)}</div>
        </div>
        <div className="flex items-center justify-center gap-4 pb-4 text-white">
          <Button variant="ghost" size="icon" className="text-white hover:bg-white/10" onClick={(e) => { e.stopPropagation(); setIdx(Math.max(0, idx - 1)); }}><ChevronLeft className="size-5" /></Button>
          <span className="text-sm tabular-nums">{idx + 1} / {content.slides.length}</span>
          <Button variant="ghost" size="icon" className="text-white hover:bg-white/10" onClick={(e) => { e.stopPropagation(); setIdx(Math.min(content.slides.length - 1, idx + 1)); }}><ChevronRight className="size-5" /></Button>
          <Button variant="outline" size="sm" className="text-white border-white/40" onClick={(e) => { e.stopPropagation(); setPresent(false); }}><X className="size-3.5 mr-1" /> End show (Esc)</Button>
        </div>
      </div>
    );
  }

  return (
    <div className="flex flex-col h-full min-h-0">
      {/* toolbar */}
      <div className="flex flex-wrap items-center gap-2 border-b bg-muted/40 px-3 py-1.5">
        <Select value={content.theme} onValueChange={(v) => { setContent((s) => ({ ...s, theme: v })); markDirty(); }}>
          <SelectTrigger className="h-8 w-40"><SelectValue /></SelectTrigger>
          <SelectContent>
            {Object.entries(THEMES).map(([k, t]) => <SelectItem key={k} value={k}>{t.name}</SelectItem>)}
          </SelectContent>
        </Select>
        <label className="flex items-center gap-1 text-xs text-muted-foreground" title="Custom background for this slide">
          Slide colour
          <input type="color" value={slide.bg || theme.bg} onChange={(e) => { patch({ bg: e.target.value }); markDirty(); }} className="size-6 cursor-pointer rounded border" />
        </label>
        <div className="ml-auto flex items-center gap-1.5">
          <Button size="sm" variant="outline" onClick={addSlide}><Plus className="size-3.5 mr-1" /> Slide</Button>
          <Button size="sm" onClick={async () => { setSaving(true); markDirty(); await doSave(); setSaving(false); }} disabled={saving}>
            <Save className="size-3.5 mr-1" /> {saving ? "Saving…" : "Save"}
          </Button>
        </div>
      </div>
      <div className="flex flex-1 min-h-0">
        {/* thumbnails */}
        <div className="w-40 shrink-0 overflow-y-auto border-r bg-muted/30 p-2 space-y-2">
          {content.slides.map((s, i) => (
            <button key={i} type="button" onClick={() => setIdx(i)}
              className={cn("block w-full overflow-hidden rounded border-2 text-left transition-all", i === idx ? "border-primary shadow" : "border-transparent hover:border-border")}>
              <div className="aspect-video w-full text-[6px] leading-tight overflow-hidden">{canvas(s, theme)}</div>
              <div className="truncate bg-background px-1.5 py-0.5 text-[10px] text-muted-foreground">{i + 1}. {s.title || "Untitled"}</div>
            </button>
          ))}
        </div>
        {/* editor */}
        <div className="flex flex-1 flex-col gap-3 overflow-y-auto p-4">
          <div className="mx-auto aspect-video w-full max-w-[720px] shadow-lg" />
          <div className="mx-auto w-full max-w-[720px] space-y-3">
            <div>
              <Label className="text-xs">Slide {idx + 1} title</Label>
              <Input className="mt-1 text-lg font-semibold" value={slide.title} placeholder="e.g. Term 2 schemes of learning"
                onChange={(e) => { patch({ title: e.target.value }); markDirty(); }} />
            </div>
            <div>
              <Label className="text-xs">Body — one bullet per line</Label>
              <Textarea className="mt-1 min-h-36" value={slide.body} placeholder={"Outline the topic\nKey points and examples\nClass activity"}
                onChange={(e) => { patch({ body: e.target.value }); markDirty(); }} />
            </div>
            <div>
              <Label className="text-xs">Speaker notes (private to you)</Label>
              <Textarea className="mt-1 min-h-20" value={slide.notes} placeholder="What to say while this slide is up…"
                onChange={(e) => { patch({ notes: e.target.value }); markDirty(); }} />
            </div>
            <div className="flex items-center gap-1.5">
              <Button size="sm" variant="outline" onClick={() => moveSlide(-1)} disabled={idx === 0}><ArrowUp className="size-3.5" /></Button>
              <Button size="sm" variant="outline" onClick={() => moveSlide(1)} disabled={idx === content.slides.length - 1}><ArrowDown className="size-3.5" /></Button>
              <Button size="sm" variant="outline" onClick={dupSlide}><Copy className="size-3.5 mr-1" /> Duplicate</Button>
              <Button size="sm" variant="outline" className="text-destructive" onClick={delSlide} disabled={content.slides.length === 1}>
                <Trash2 className="size-3.5 mr-1" /> Delete
              </Button>
              <Button size="sm" className="ml-auto" onClick={() => setPresent(true)}>
                <Play className="size-3.5 mr-1" /> Present
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export { DocEditor, SheetEditor, SlideEditor };

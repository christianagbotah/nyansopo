"use client";
// ── Nyansapo Office editors (wave 56 · wave 60 professional suite + AI) ──
// Three sibling editors behind the Office file manager:
//   • DocEditor   — rich-text word processor (contentEditable + execCommand)
//       wave 60 professional suite: horizontal ruler with draggable margins,
//       headers & footers, page numbers (true Word PAGE/NUMPAGES fields in the
//       .doc export), find & replace, tables, text colour & highlight, sub /
//       superscript, page breaks, live word/page counts
//   • SheetEditor — spreadsheet with real formula evaluation (SUM/AVG/MIN/MAX/
//       COUNT, ranges, arithmetic), cell styles & GH₵ formats
//       wave 60: insert/delete rows & columns at the cursor
//   • SlideEditor — presentation builder with Ghana-inspired themes,
//       speaker notes, fullscreen present mode and a printable deck export
// All three autosave into OfficeFile.contentJson, speak the same props, and
// (wave 60) carry a fully AI-assisted writing layer: continue / improve /
// grammar-fix / summarise / rewrite for documents, deck generation for slides
// and a natural-language formula helper for sheets.
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { apiPost, getHeaders } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useToast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import {
  Bold, Italic, Underline, Strikethrough, List, ListOrdered, AlignLeft, AlignCenter, AlignRight, AlignJustify,
  Undo2, Redo2, Minus, Eraser, Image as ImageIcon, Save, Printer, Download, Type, Link2,
  Plus, Trash2, Copy, ChevronLeft, ChevronRight, ArrowUp, ArrowDown, Play, X,
  Sparkles, Wand2, SpellCheck, Search, Table, CalendarDays, Highlighter, Subscript, Superscript,
  PanelTop, FileDown, Rows3, Columns3, Loader2, Replace, Palette, Languages, CheckCircle2,
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
    // latest.current.doSave (not the closure's doSave) — state updates are
    // async, so the doSave captured in THIS render would build stale content
    // for the very edit that scheduled the save (wave 60 fix).
    timerRef.current = window.setTimeout(() => void latest.current.doSave(), delay);
  };

  const latest = useRef({ doSave });
  useEffect(() => {
    latest.current.doSave = doSave;
  });

  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "s") { e.preventDefault(); dirtyRef.current = true; void latest.current.doSave(); }
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "f") { e.preventDefault(); window.dispatchEvent(new CustomEvent("nyansapo-doc-find")); }
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

/* ═══════════════════ shared export helpers (manager + editors) ═══════════════════
   Every file in the Office portal downloads in a format a Ghanaian school
   computer can actually open: Word .doc (with real header/footer/page-number
   fields), plain .html, .csv for workbooks and a printable deck .html. */

const esc = (s: string) => s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");

export function downloadBlob(name: string, mime: string, text: string) {
  const a = document.createElement("a");
  a.href = URL.createObjectURL(new Blob([text], { type: mime }));
  a.download = name;
  a.click();
  URL.revokeObjectURL(a.href);
}

type DocLayout = { marginLeftCm: number; marginRightCm: number; header: string; footer: string; pageNum: "none" | "plain" | "xofy" };
const DEFAULT_LAYOUT: DocLayout = { marginLeftCm: 2.54, marginRightCm: 2.54, header: "", footer: "", pageNum: "none" };

function parseDoc(contentJson: string): { html: string; layout: DocLayout } {
  try {
    const p = JSON.parse(contentJson);
    return { html: typeof p?.html === "string" ? p.html : "<p><br></p>", layout: { ...DEFAULT_LAYOUT, ...(p?.layout ?? {}) } };
  } catch { return { html: "<p><br></p>", layout: DEFAULT_LAYOUT }; }
}

const DOC_CSS = (l: DocLayout) => `
  body { font-family: Georgia, 'Times New Roman', serif; line-height: 1.7; color: #1f2937; margin: 0; }
  .page { width: 21cm; margin: 0 auto; padding: ${l.marginLeftCm}cm ${l.marginRightCm}cm 2cm; box-sizing: border-box; background: #fff; }
  img { max-width: 100%; }
  blockquote { border-left: 3px solid #ccc; margin-left: 0; padding-left: 16px; color: #555; }
  table { border-collapse: collapse; }
  td, th { border: 1px solid #999; padding: 6px 8px; }
  hr.page-break { border: none; border-top: 1px dashed #bbb; margin: 24px 0; }
  .doc-header { text-align: center; font-size: 0.85em; color: #6b7280; letter-spacing: 0.06em; text-transform: uppercase; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb; margin-bottom: 24px; }
  .doc-footer { display: flex; justify-content: space-between; align-items: center; font-size: 0.85em; color: #6b7280; margin-top: 32px; border-top: 1px solid #e5e7eb; padding-top: 8px; }
  @media print {
    body { background: #fff; }
    .doc-header { position: fixed; top: 0.6cm; left: ${l.marginLeftCm}cm; right: ${l.marginRightCm}cm; }
    .doc-footer { position: fixed; bottom: 0.6cm; left: ${l.marginLeftCm}cm; right: ${l.marginRightCm}cm; }
    hr.page-break { page-break-after: always; border: none; }
    @page { size: A4; margin: 2.4cm ${l.marginRightCm}cm 2.4cm ${l.marginLeftCm}cm; }
  }
`;

const footerHtml = (l: DocLayout) => {
  const pn = l.pageNum === "plain" ? `<span class="pn">1</span>` : l.pageNum === "xofy" ? `<span class="pn">1 of 1</span>` : "";
  return (l.footer || pn) ? `<div class="doc-footer"><span>${esc(l.footer)}</span>${pn ? `<span>${pn}</span>` : ""}</div>` : "";
};

/** Standalone, self-contained .html of a document (prints with repeating header/footer). */
export function docHtml(title: string, contentJson: string): string {
  const { html, layout } = parseDoc(contentJson);
  return `<!doctype html><html><head><meta charset="utf-8"><title>${esc(title)}</title><style>${DOC_CSS(layout)}</style></head>
<body><div class="page">${layout.header ? `<div class="doc-header">${esc(layout.header)}</div>` : ""}${html}${footerHtml(layout)}</div></body></html>`;
}

/** Word .doc (HTML flavour) with REAL header / footer / PAGE / NUMPAGES fields. */
export function docWordHtml(title: string, contentJson: string): string {
  const { html, layout } = parseDoc(contentJson);
  const footerInner = `${esc(layout.footer)}${layout.footer ? "&nbsp;&nbsp;—&nbsp;&nbsp;" : ""}${layout.pageNum === "plain"
    ? `Page <span style="mso-field-code: PAGE"></span>`
    : layout.pageNum === "xofy"
      ? `Page <span style="mso-field-code: PAGE"></span> of <span style="mso-field-code: NUMPAGES"></span>`
      : ""}`;
  return `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head><meta charset="utf-8"><title>${esc(title)}</title>
<!--[if gte mso 9]><xml><w:WordDocument><w:View>Print</w:View><w:Zoom>100</w:Zoom></w:WordDocument></xml><![endif]-->
<style>
@page { size: 21cm 29.7cm; mso-page-orientation: portrait; margin: 2.4cm ${layout.marginRightCm}cm 2.6cm ${layout.marginLeftCm}cm; mso-header-margin: 1cm; mso-footer-margin: 1cm; mso-header: h1; mso-footer: f1; }
div.mso-header { mso-element: header; text-align: center; font-size: 9pt; color: #6b7280; letter-spacing: 0.5pt; text-transform: uppercase; border-bottom: solid 0.5pt #d1d5db; padding-bottom: 2pt; }
div.mso-footer { mso-element: footer; text-align: center; font-size: 9pt; color: #6b7280; }
body { font-family: Georgia, 'Times New Roman', serif; line-height: 1.7; }
img { max-width: 100%; }
table { border-collapse: collapse; }
td, th { border: solid 0.5pt #808080; padding: 6px 8px; }
hr.page-break { page-break-after: always; border: none; }
</style></head>
<body>
<div class="mso-header" id="h1"><p>${esc(layout.header) || "&nbsp;"}</p></div>
<div class="mso-footer" id="f1"><p>${footerInner || "&nbsp;"}</p></div>
${html}
</body></html>`;
}

/** CSV of a workbook (computed values, same logic as the editor). */
export function sheetCsv(title: string, contentJson: string): string {
  const content = parseSheet(contentJson);
  const computed = evaluateSheet(content);
  const rows: string[][] = [];
  for (let r = 0; r < content.rows; r++) {
    const row: string[] = [];
    let any = false;
    for (let c = 0; c < content.cols; c++) {
      const key = cellKey(c, r);
      const raw = content.cells[key]?.v ?? "";
      const shown = raw === "" ? "" : raw.startsWith("=") ? computed[key] ?? "" : raw;
      if (shown !== "") any = true;
      row.push(shown);
    }
    if (any) rows.push(row);
  }
  return rows.map((r) => r.map((x) => `"${x.replace(/"/g, '""')}"`).join(",")).join("\n");
}

/** Printable .html of a slide deck — one page per slide, with speaker notes. */
export function slideHtml(title: string, contentJson: string): string {
  let slides: { title: string; body: string; bg: string; notes: string }[] = [];
  let theme = "kente";
  try {
    const p = JSON.parse(contentJson);
    slides = Array.isArray(p?.slides) ? p.slides : [];
    theme = p?.theme ?? "kente";
  } catch { /* blank deck */ }
  const t = THEMES[theme] ?? THEMES.kente;
  const pages = slides.map((s) => `
  <section class="slide" style="background:${s.bg || t.bg}">
    <h1 style="color:${t.title}">${esc(s.title || "Untitled slide")}</h1>
    <div class="body" style="color:${t.body}">${esc(s.body || "")}</div>
    ${s.notes ? `<div class="notes"><b>Speaker notes</b> — ${esc(s.notes)}</div>` : ""}
  </section>`).join("");
  return `<!doctype html><html><head><meta charset="utf-8"><title>${esc(title)}</title><style>
  body { margin: 0; background: #374151; font-family: Georgia, serif; }
  .slide { width: 21cm; height: 13.2cm; margin: 12px auto; padding: 1.6cm; box-sizing: border-box; page-break-after: always; display: flex; flex-direction: column; justify-content: center; border-radius: 4px; }
  h1 { font-size: 30pt; margin: 0 0 18pt; line-height: 1.15; }
  .body { white-space: pre-wrap; font-size: 15pt; line-height: 1.6; }
  .notes { margin-top: auto; padding-top: 10pt; border-top: 1px solid rgba(255,255,255,.35); font-size: 9.5pt; color: rgba(255,255,255,.85); font-family: Arial, sans-serif; }
  @media print { body { background: #fff; } .slide { margin: 0; border-radius: 0; } @page { size: A4 landscape; margin: 0; } }
  </style></head><body>${pages}</body></html>`;
}

/** One-click download for any office file, straight from the manager grid. */
export function exportOfficeFile(file: { kind: string; title: string; contentJson: string }) {
  const safe = file.title.replace(/[\\/:*?"<>|]/g, "-").slice(0, 80) || "untitled";
  if (file.kind === "DOC") {
    downloadBlob(`${safe}.doc`, "application/msword", docWordHtml(file.title, file.contentJson));
  } else if (file.kind === "SHEET") {
    downloadBlob(`${safe}.csv`, "text/csv", sheetCsv(file.title, file.contentJson));
  } else {
    downloadBlob(`${safe}.html`, "text/html", slideHtml(file.title, file.contentJson));
  }
}

/* ═══════════════════════════ AI assist plumbing ═══════════════════════════ */

async function officeAI(body: Record<string, unknown>): Promise<any> {
  const res = await fetch("/api/ai/office-assist", { method: "POST", headers: { ...getHeaders() }, body: JSON.stringify({ schoolId: getSchoolId(), ...body }) });
  const d = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(d.error ?? `AI request failed (${res.status})`);
  return d;
}

const paragraphsToHtml = (text: string): string =>
  text.split(/\n{2,}/).map((p) => `<p>${p.replace(/\n/g, "<br>")}</p>`).join("");

/* ═══════════════════════════ DOC EDITOR ═══════════════════════════ */

const exec = (cmd: string, val?: string) => {
  document.execCommand(cmd, false, val);
};

/* toolbar button shared by the doc editor */
function TBtn({ icon, label, onClick, active }: { icon: React.ReactNode; label: string; onClick: () => void; active?: boolean }) {
  return (
    <Button type="button" variant="ghost" size="icon" className={cn("size-8", active && "bg-primary/10")} title={label} aria-label={label}
      onMouseDown={(e) => e.preventDefault()} onClick={onClick}>{icon}</Button>
  );
}

/* ── horizontal ruler (wave 60 professional suite) ──
   A4 width, ticks every 0.5 cm, numbered each cm. The two margin handles
   drag to set the document's left/right margins (live page padding). */
const PAGE_W = 794; // px ≈ 21 cm at 96 dpi
const PXCM = PAGE_W / 21;

function Ruler({ leftCm, rightCm, onChange }: { leftCm: number; rightCm: number; onChange: (l: number, r: number) => void }) {
  const ref = useRef<HTMLDivElement>(null);
  const drag = useRef<"l" | "r" | null>(null);

  const onPointerDown = (which: "l" | "r") => (e: React.PointerEvent) => {
    e.preventDefault();
    drag.current = which;
    (e.currentTarget as HTMLElement).setPointerCapture(e.pointerId);
  };
  const onPointerMove = (e: React.PointerEvent) => {
    if (!drag.current || !ref.current) return;
    const rect = ref.current.getBoundingClientRect();
    const cm = Math.round(((e.clientX - rect.left) / PXCM) * 2) / 2; // snap to 0.5 cm
    if (drag.current === "l") onChange(Math.min(Math.max(0, cm), 21 - 1 - rightCm), rightCm);
    else onChange(leftCm, Math.min(Math.max(1, cm), 21 - leftCm));
  };
  const end = () => { drag.current = null; };

  return (
    <div
      ref={ref}
      role="ruler"
      aria-label="Document ruler — drag the markers to change margins"
      className="relative mx-auto select-none bg-muted/60 border rounded-t-sm"
      style={{ width: PAGE_W, height: 24 }}
      onPointerMove={onPointerMove}
      onPointerUp={end}
      onPointerCancel={end}
    >
      {/* shaded margin zones */}
      <div className="absolute inset-y-0 left-0 bg-muted" style={{ width: leftCm * PXCM }} />
      <div className="absolute inset-y-0 right-0 bg-muted" style={{ width: rightCm * PXCM }} />
      {/* ticks — small each 0.5 cm, tall each 1 cm */}
      {Array.from({ length: 43 }, (_, i) => (
        <div key={i} className="absolute bottom-0 w-px bg-border" style={{ left: (i / 2) * PXCM, height: i % 2 === 0 ? 10 : 5 }} />
      ))}
      {Array.from({ length: 22 }, (_, i) => (
        <div key={i} className="absolute bottom-[11px] -translate-x-1/2 text-[8px] leading-none text-muted-foreground tabular-nums" style={{ left: i * PXCM }}>{i}</div>
      ))}
      {/* draggable margin markers */}
      <div
        onPointerDown={onPointerDown("l")}
        title={`Left margin: ${leftCm.toFixed(1)} cm — drag`}
        className="absolute top-0 z-10 flex h-full w-3 cursor-ew-resize items-center justify-center"
        style={{ left: Math.max(0, leftCm * PXCM - 6) }}
      >
        <div className="h-full w-[3px] rounded bg-primary shadow" />
      </div>
      <div
        onPointerDown={onPointerDown("r")}
        title={`Right margin: ${rightCm.toFixed(1)} cm — drag`}
        className="absolute top-0 z-10 flex h-full w-3 cursor-ew-resize items-center justify-center"
        style={{ left: Math.min(PAGE_W - 3, (21 - rightCm) * PXCM - 6) }}
      >
        <div className="h-full w-[3px] rounded bg-primary shadow" />
      </div>
    </div>
  );
}

function DocEditor({ file, onSaved, onDirtyChange }: EditorProps) {
  const seed = useMemo(() => parseDoc(file.contentJson), [file.contentJson]);
  const ref = useRef<HTMLDivElement>(null);
  const [layout, setLayout] = useState<DocLayout>(seed.layout);
  const [dirty, setDirty] = useState(false);
  const [words, setWords] = useState(0);
  const [chars, setChars] = useState(0);
  const [paras, setParas] = useState(0);
  const [pages, setPages] = useState(1);
  const [saving, setSaving] = useState(false);
  const [findOpen, setFindOpen] = useState(false);
  const [layoutOpen, setLayoutOpen] = useState(false);
  const [aiOpen, setAiOpen] = useState(false);
  const [tableOpen, setTableOpen] = useState(false);
  const [tableRows, setTableRows] = useState(3);
  const [tableCols, setTableCols] = useState(3);
  const [findQ, setFindQ] = useState("");
  const [replaceQ, setReplaceQ] = useState("");
  const [findCount, setFindCount] = useState<number | null>(null);

  const build = useCallback(() => JSON.stringify({ html: ref.current?.innerHTML ?? "<p><br></p>", layout }), [layout]);
  const { markDirty, doSave } = useAutosave(file, build, onSaved, onDirtyChange);

  const countAll = () => {
    const t = (ref.current?.innerText ?? "").trim();
    setWords(t ? t.split(/\s+/).filter(Boolean).length : 0);
    setChars(t.length);
    setParas(ref.current?.querySelectorAll("p, li, h1, h2, h3, blockquote").length ?? 0);
    // rough print-page estimate: A4 content area ≈ 940 px at 96 dpi
    const h = ref.current?.scrollHeight ?? 0;
    setPages(Math.max(1, Math.ceil(h / 940)));
  };

  useEffect(() => {
    if (ref.current && !ref.current.innerHTML) ref.current.innerHTML = seed.html;
    const id = window.setTimeout(countAll, 0);
    const onFind = () => setFindOpen(true);
    window.addEventListener("nyansapo-doc-find", onFind);
    return () => { window.clearTimeout(id); window.removeEventListener("nyansapo-doc-find", onFind); };
  }, []);

  const onInput = () => { setDirty(true); markDirty(); countAll(); };

  const saveNow = async () => {
    setSaving(true);
    markDirty();
    await doSave();
    setSaving(false);
  };

  /* ── downloads & print ── */
  const contentJsonNow = () => JSON.stringify({ html: ref.current?.innerHTML ?? "<p><br></p>", layout });
  const downloadHtml = () => downloadBlob(`${file.title.replace(/[\\/:*?"<>|]/g, "-") || "document"}.html`, "text/html", docHtml(file.title, contentJsonNow()));
  const downloadDoc = () => downloadBlob(`${file.title.replace(/[\\/:*?"<>|]/g, "-") || "document"}.doc`, "application/msword", docWordHtml(file.title, contentJsonNow()));
  const printDoc = () => {
    const w = window.open("", "_blank");
    if (!w) return;
    w.document.write(docHtml(file.title, contentJsonNow()).replace("</body>", "<script>onload=()=>print()<\/script></body>"));
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

  const insertTable = () => {
    const r = Math.min(20, Math.max(1, tableRows));
    const c = Math.min(10, Math.max(1, tableCols));
    const cell = `<td style="border:1px solid #999;padding:6px 8px">&nbsp;</td>`;
    const html = `<table style="border-collapse:collapse;margin:8px 0"><tbody>${Array.from({ length: r }, () => `<tr>${cell.repeat(c)}</tr>`).join("")}</tbody></table><p><br></p>`;
    ref.current?.focus();
    exec("insertHTML", html);
    setTableOpen(false);
    onInput();
  };

  const insertPageBreak = () => {
    ref.current?.focus();
    exec("insertHTML", `<hr class="page-break"><p><br></p>`);
    onInput();
  };

  const insertDate = () => {
    ref.current?.focus();
    exec("insertHTML", new Date().toLocaleDateString(undefined, { day: "numeric", month: "long", year: "numeric" }));
    onInput();
  };

  /* ── find & replace (walks text nodes — formatting preserved) ── */
  const countMatches = (): number => {
    if (!ref.current || !findQ) return 0;
    return (ref.current.innerText.match(new RegExp(findQ.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"), "g")) ?? []).length;
  };
  const applyReplaceAll = (): number => {
    if (!ref.current || !findQ) return 0;
    const walker = document.createTreeWalker(ref.current, NodeFilter.SHOW_TEXT);
    const nodes: Text[] = [];
    while (walker.nextNode()) nodes.push(walker.currentNode as Text);
    let n = 0;
    for (const t of nodes) {
      if (!t.nodeValue || !t.nodeValue.includes(findQ)) continue;
      t.nodeValue = t.nodeValue.split(findQ).join(replaceQ);
      n++;
    }
    if (n) onInput();
    return n;
  };

  /* ── AI grammar-fix application (targeted, formatting kept) ── */
  const applyChanges = (changes: { from: string; to: string }[]): number => {
    if (!ref.current) return 0;
    const walker = document.createTreeWalker(ref.current, NodeFilter.SHOW_TEXT);
    const nodes: Text[] = [];
    while (walker.nextNode()) nodes.push(walker.currentNode as Text);
    let n = 0;
    for (const ch of changes) {
      if (!ch.from || typeof ch.to !== "string") continue;
      for (const t of nodes) {
        if (t.nodeValue && t.nodeValue.includes(ch.from)) {
          t.nodeValue = t.nodeValue.split(ch.from).join(ch.to);
          n++;
        }
      }
    }
    if (n) onInput();
    return n;
  };

  const footerPreview = layout.pageNum === "plain" ? "Page 1" : layout.pageNum === "xofy" ? "Page 1 of 1" : "";

  return (
    <div className="flex h-full min-h-0 flex-col">
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
        <TBtn icon={<Subscript className="size-4" />} label="Subscript" onClick={() => { exec("subscript"); onInput(); }} />
        <TBtn icon={<Superscript className="size-4" />} label="Superscript" onClick={() => { exec("superscript"); onInput(); }} />
        <label className="inline-flex size-8 cursor-pointer items-center justify-center rounded hover:bg-accent" title="Text colour">
          <Palette className="size-4" />
          <input type="color" className="size-0 opacity-0" onChange={(e) => { exec("styleWithCSS", "true"); exec("foreColor", e.target.value); onInput(); }} />
        </label>
        <label className="inline-flex size-8 cursor-pointer items-center justify-center rounded hover:bg-accent" title="Highlight">
          <Highlighter className="size-4" />
          <input type="color" defaultValue="#fef08a" className="size-0 opacity-0" onChange={(e) => { exec("styleWithCSS", "true"); exec("hiliteColor", e.target.value); onInput(); }} />
        </label>
        <span className="mx-1 h-5 w-px bg-border" />
        <TBtn icon={<List className="size-4" />} label="Bullet list" onClick={() => { exec("insertUnorderedList"); onInput(); }} />
        <TBtn icon={<ListOrdered className="size-4" />} label="Numbered list" onClick={() => { exec("insertOrderedList"); onInput(); }} />
        <TBtn icon={<AlignLeft className="size-4" />} label="Align left" onClick={() => { exec("justifyLeft"); onInput(); }} />
        <TBtn icon={<AlignCenter className="size-4" />} label="Center" onClick={() => { exec("justifyCenter"); onInput(); }} />
        <TBtn icon={<AlignRight className="size-4" />} label="Align right" onClick={() => { exec("justifyRight"); onInput(); }} />
        <TBtn icon={<AlignJustify className="size-4" />} label="Justify" onClick={() => { exec("justifyFull"); onInput(); }} />
        <span className="mx-1 h-5 w-px bg-border" />
        <TBtn icon={<Minus className="size-4" />} label="Horizontal rule" onClick={() => { exec("insertHorizontalRule"); onInput(); }} />
        <TBtn icon={<Table className="size-4" />} label="Insert table" onClick={() => setTableOpen(true)} />
        <TBtn icon={<FileDown className="size-4" />} label="Page break" onClick={insertPageBreak} />
        <TBtn icon={<CalendarDays className="size-4" />} label="Insert today's date" onClick={insertDate} />
        <TBtn icon={<Link2 className="size-4" />} label="Insert link" onClick={insertLink} />
        <label className="inline-flex cursor-pointer" title="Insert image">
          <ImageIcon className="size-4 mx-2" />
          <input type="file" accept="image/*" className="hidden" onChange={(e) => e.target.files?.[0] && insertImage(e.target.files[0]).then(onInput).catch(() => {})} />
        </label>
        <TBtn icon={<Eraser className="size-4" />} label="Clear formatting" onClick={() => { exec("removeFormat"); onInput(); }} />
        <span className="mx-1 h-5 w-px bg-border" />
        <Button type="button" size="sm" variant="outline" className="h-8 gap-1" onClick={() => setFindOpen(true)}>
          <Search className="size-3.5" /> Find &amp; replace
        </Button>
        <Button type="button" size="sm" variant="outline" className="h-8 gap-1" onClick={() => setLayoutOpen(true)}>
          <PanelTop className="size-3.5" /> Header &amp; footer
        </Button>
        <Button type="button" size="sm" className="h-8 gap-1 bg-primary" onClick={() => setAiOpen(true)}>
          <Sparkles className="size-3.5" /> AI assist
        </Button>
        <div className="ml-auto flex items-center gap-1.5">
          <span className="hidden text-xs text-muted-foreground md:inline">{words} words · ~{pages} page{pages > 1 ? "s" : ""} · autosaves</span>
          <Button size="sm" variant="outline" onClick={downloadHtml} title="Standalone web page"><Download className="size-3.5 mr-1" /> .html</Button>
          <Button size="sm" variant="outline" onClick={downloadDoc} title="Word document — keeps header, footer & page numbers"><FileDown className="size-3.5 mr-1" /> .doc</Button>
          <Button size="sm" variant="outline" onClick={printDoc}><Printer className="size-3.5 mr-1" /> Print</Button>
          <Button size="sm" onClick={saveNow} disabled={saving}><Save className="size-3.5 mr-1" /> {saving ? "Saving…" : dirty ? "Save" : "Saved"}</Button>
        </div>
      </div>

      {/* page — ruler + A4 sheet with header/footer zones */}
      <div className="flex-1 overflow-auto bg-neutral-200/70 p-4 dark:bg-neutral-900 lg:p-8">
        <Ruler
          leftCm={layout.marginLeftCm}
          rightCm={layout.marginRightCm}
          onChange={(l, r) => { setLayout((x) => ({ ...x, marginLeftCm: l, marginRightCm: r })); markDirty(); }}
        />
        <div
          className="mx-auto bg-white text-neutral-900 shadow-lg outline-none dark:bg-neutral-950 dark:text-neutral-100"
          style={{ width: PAGE_W, minHeight: 1000 }}
        >
          {/* header zone */}
          <button
            type="button"
            onClick={() => setLayoutOpen(true)}
            className={cn(
              "w-full border-b border-dashed px-8 pt-5 pb-2 text-center text-[11px] uppercase tracking-[0.12em] text-muted-foreground transition-colors hover:bg-muted/40",
              !layout.header && "italic"
            )}
            style={{ paddingLeft: `${layout.marginLeftCm}cm`, paddingRight: `${layout.marginRightCm}cm` }}
            title="Click to edit the header"
          >
            {layout.header || "header — click to set"}
          </button>
          {/* body */}
          <div
            ref={ref}
            contentEditable
            suppressContentEditableWarning
            onInput={onInput}
            className="min-h-[620px] py-8 outline-none"
            style={{ paddingLeft: `${layout.marginLeftCm}cm`, paddingRight: `${layout.marginRightCm}cm`, fontFamily: "Georgia, 'Times New Roman', serif", lineHeight: 1.7 }}
          />
          {/* footer zone */}
          <button
            type="button"
            onClick={() => setLayoutOpen(true)}
            className="flex w-full items-center justify-between border-t border-dashed px-8 pt-2 pb-5 text-[11px] text-muted-foreground transition-colors hover:bg-muted/40"
            style={{ paddingLeft: `${layout.marginLeftCm}cm`, paddingRight: `${layout.marginRightCm}cm` }}
            title="Click to edit the footer & page numbers"
          >
            <span className={cn("truncate", !layout.footer && "italic")}>{layout.footer || "footer — click to set"}</span>
            {footerPreview && <span className="tabular-nums">{footerPreview}</span>}
          </button>
        </div>
        {/* status bar */}
        <div className="mx-auto flex w-[794px] flex-wrap items-center gap-4 px-2 py-2 text-[11px] text-muted-foreground">
          <span>{words} words</span>
          <span>{chars} characters</span>
          <span>{paras} paragraphs</span>
          <span>≈ {pages} printed page{pages > 1 ? "s" : ""}</span>
          <span>margins {layout.marginLeftCm.toFixed(1)} cm / {layout.marginRightCm.toFixed(1)} cm</span>
          <span className="ml-auto">Page numbers live in the .doc export &amp; print</span>
        </div>
      </div>

      {/* ── header & footer / page layout dialog ── */}
      <Dialog open={layoutOpen} onOpenChange={setLayoutOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Header, footer &amp; page numbers</DialogTitle>
            <DialogDescription>
              The header and footer repeat on every printed page. Page numbers become live Word fields in the .doc download — “Page 3 of 5” updates itself as the document grows.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-1">
            <div>
              <Label className="text-xs">Header text (e.g. Nyansapo International School — Term 2 Newsletter)</Label>
              <Input className="mt-1" value={layout.header} maxLength={160} placeholder="Appears centred at the top of every page"
                onChange={(e) => { setLayout({ ...layout, header: e.target.value }); markDirty(); }} />
            </div>
            <div>
              <Label className="text-xs">Footer text (e.g. Prepared by the Headteacher's office)</Label>
              <Input className="mt-1" value={layout.footer} maxLength={160} placeholder="Appears at the bottom of every page"
                onChange={(e) => { setLayout({ ...layout, footer: e.target.value }); markDirty(); }} />
            </div>
            <div>
              <Label className="text-xs">Page numbers</Label>
              <Select value={layout.pageNum} onValueChange={(v) => { setLayout({ ...layout, pageNum: v as DocLayout["pageNum"] }); markDirty(); }}>
                <SelectTrigger className="mt-1"><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">None</SelectItem>
                  <SelectItem value="plain">Page 1, 2, 3…</SelectItem>
                  <SelectItem value="xofy">Page 1 of N</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <Label className="text-xs">Left margin (cm)</Label>
                <Input className="mt-1" type="number" min={0} max={10} step={0.5} value={layout.marginLeftCm}
                  onChange={(e) => { const v = Math.min(10, Math.max(0, Number(e.target.value) || 0)); setLayout({ ...layout, marginLeftCm: v }); markDirty(); }} />
              </div>
              <div>
                <Label className="text-xs">Right margin (cm)</Label>
                <Input className="mt-1" type="number" min={0} max={10} step={0.5} value={layout.marginRightCm}
                  onChange={(e) => { const v = Math.min(10, Math.max(0, Number(e.target.value) || 0)); setLayout({ ...layout, marginRightCm: v }); markDirty(); }} />
              </div>
            </div>
            <p className="text-xs text-muted-foreground">Tip: drag the markers on the ruler above the page to set margins by hand.</p>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => { setLayout(DEFAULT_LAYOUT); markDirty(); }}>Reset</Button>
            <Button onClick={() => setLayoutOpen(false)}>Done</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── find & replace dialog ── */}
      <Dialog open={findOpen} onOpenChange={(v) => { setFindOpen(v); if (!v) setFindCount(null); }}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Find &amp; replace</DialogTitle>
            <DialogDescription>Replacements keep all formatting — only the matching text changes.</DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-1">
            <div>
              <Label className="text-xs">Find</Label>
              <Input className="mt-1" value={findQ} onChange={(e) => { setFindQ(e.target.value); setFindCount(null); }} placeholder="e.g. acheivement" autoFocus />
            </div>
            <div>
              <Label className="text-xs">Replace with</Label>
              <Input className="mt-1" value={replaceQ} onChange={(e) => setReplaceQ(e.target.value)} placeholder="e.g. achievement" />
            </div>
            {findCount !== null && (
              <p className="text-xs text-muted-foreground">{findCount === 0 ? "No matches." : `${findCount} match${findCount > 1 ? "es" : ""} for “${findQ}”.`}</p>
            )}
          </div>
          <DialogFooter className="sm:justify-between">
            <Button variant="outline" onClick={() => setFindCount(countMatches())} disabled={!findQ.trim()}>
              <Search className="size-3.5 mr-1" /> Count matches
            </Button>
            <div className="flex gap-2">
              <Button variant="outline" onClick={() => { const n = applyReplaceAll(); setFindCount(0); setFindQ(""); setReplaceQ(""); setFindOpen(false); }} disabled={!findQ.trim()}>
                <Replace className="size-3.5 mr-1" /> Replace all
              </Button>
            </div>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── insert table dialog ── */}
      <Dialog open={tableOpen} onOpenChange={setTableOpen}>
        <DialogContent className="sm:max-w-xs">
          <DialogHeader>
            <DialogTitle>Insert a table</DialogTitle>
            <DialogDescription>Drops a bordered table at the cursor — type into the cells directly.</DialogDescription>
          </DialogHeader>
          <div className="grid grid-cols-2 gap-3 py-1">
            <div>
              <Label className="text-xs">Rows</Label>
              <Input className="mt-1" type="number" min={1} max={20} value={tableRows} onChange={(e) => setTableRows(Number(e.target.value) || 1)} />
            </div>
            <div>
              <Label className="text-xs">Columns</Label>
              <Input className="mt-1" type="number" min={1} max={10} value={tableCols} onChange={(e) => setTableCols(Number(e.target.value) || 1)} />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setTableOpen(false)}>Cancel</Button>
            <Button onClick={insertTable}>Insert table</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── AI writing assistant ── */}
      <AiDocPanel
        open={aiOpen}
        onOpenChange={setAiOpen}
        title={file.title}
        getText={() => (ref.current?.innerText ?? "").trim().slice(0, 12000)}
        hasText={() => (ref.current?.innerText ?? "").trim().length > 40}
        insertAtCursor={(html) => { ref.current?.focus(); exec("insertHTML", html); onInput(); }}
        replaceDoc={(html) => { if (ref.current) { ref.current.innerHTML = html; onInput(); } }}
        applyChanges={applyChanges}
      />
    </div>
  );
}

/* ── AI panel for documents — continue / improve / fix / summarise / rewrite ── */
function AiDocPanel({ open, onOpenChange, title, getText, hasText, insertAtCursor, replaceDoc, applyChanges }: {
  open: boolean;
  onOpenChange: (v: boolean) => void;
  title: string;
  getText: () => string;
  hasText: () => boolean;
  insertAtCursor: (html: string) => void;
  replaceDoc: (html: string) => void;
  applyChanges: (changes: { from: string; to: string }[]) => number;
}) {
  const { toast } = useToast();
  const [mode, setMode] = useState<"continue" | "improve" | "fix" | "summarize" | "instruction" | "translate">("continue");
  const [instruction, setInstruction] = useState("");
  const [lang, setLang] = useState("twi");
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<{ html?: string; text?: string; changes?: { from: string; to: string }[] } | null>(null);
  const [applied, setApplied] = useState<number | null>(null);

  const DOC_LANGS = [
    { value: "twi", label: "Twi" },
    { value: "ewe", label: "Ewe" },
    { value: "ga", label: "Ga" },
    { value: "hausa", label: "Hausa" },
    { value: "dagbani", label: "Dagbani" },
    { value: "pidgin", label: "Ghanaian Pidgin" },
    { value: "french", label: "French" },
  ];

  const MODES: { id: typeof mode; label: string; icon: React.ReactNode; hint: string }[] = [
    { id: "continue", label: "Continue writing", icon: <Wand2 className="size-4" />, hint: "Keep the document going in the same voice" },
    { id: "improve", label: "Improve writing", icon: <Sparkles className="size-4" />, hint: "Clearer, more professional rewrite" },
    { id: "fix", label: "Fix grammar & spelling", icon: <SpellCheck className="size-4" />, hint: "Accept or dismiss each correction" },
    { id: "summarize", label: "Summarise", icon: <Type className="size-4" />, hint: "A short summary of the whole document" },
    { id: "instruction", label: "Rewrite my way", icon: <Replace className="size-4" />, hint: "Tell the AI exactly what to change" },
    { id: "translate", label: "Translate", icon: <Languages className="size-4" />, hint: "Twi, Ewe, Ga, Hausa, Dagbani, Pidgin or French" },
  ];

  const run = async () => {
    if (mode !== "continue" && !hasText()) { toast({ title: "Write something first", description: "The AI needs a few sentences of document to work with.", variant: "destructive" }); return; }
    if (mode === "instruction" && !instruction.trim()) { toast({ title: "Describe the change you want", variant: "destructive" }); return; }
    setLoading(true);
    setResult(null);
    setApplied(null);
    try {
      const d = await officeAI({ mode, title, text: getText(), instruction: mode === "instruction" ? instruction.slice(0, 500) : undefined, language: mode === "translate" ? lang : undefined });
      if (mode === "continue" && d.text) {
        insertAtCursor(paragraphsToHtml(String(d.text)));
        toast({ title: "Continuation inserted", description: "Inserted at the cursor — edit as you like." });
        onOpenChange(false);
      } else if (mode === "fix") {
        const changes = Array.isArray(d.changes) ? d.changes.slice(0, 25) : [];
        setResult({ changes });
        if (!changes.length) toast({ title: "No corrections found", description: "The grammar and spelling look clean." });
      } else if (mode === "translate" && d.text) {
        setResult({ text: String(d.text) });
      } else {
        setResult({ html: String(d.html ?? "") });
      }
    } catch (e: any) {
      toast({ title: "AI assist failed", description: e.message, variant: "destructive" });
    } finally { setLoading(false); }
  };

  const applyFixes = () => {
    if (!result?.changes) return;
    const n = applyChanges(result.changes);
    setApplied(n);
    toast({ title: n ? `${n} correction${n > 1 ? "s" : ""} applied` : "Nothing to apply", description: n ? "The document now carries the corrections." : "The AI's suggestions no longer match the text exactly — review them below." });
  };

  /* wave 63 — per-correction accept / dismiss */
  const applyOne = (i: number) => {
    if (!result?.changes) return;
    const c = result.changes[i];
    const n = applyChanges([c]);
    if (n > 0) {
      setResult({ changes: result.changes.filter((_, j) => j !== i) });
      setApplied((a) => (a ?? 0) + n);
      toast({ title: "Correction applied" });
    } else {
      toast({ title: "Could not find that text anymore", description: "It may already be corrected — dismiss it instead.", variant: "destructive" });
    }
  };
  const dismissOne = (i: number) => {
    if (!result?.changes) return;
    setResult({ changes: result.changes.filter((_, j) => j !== i) });
  };

  return (
    <Dialog open={open} onOpenChange={(v) => { onOpenChange(v); if (!v) { setResult(null); setApplied(null); } }}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><Sparkles className="size-4 text-primary" /> AI writing assistant</DialogTitle>
          <DialogDescription>Write with the AI — it sees your document (up to 12,000 characters) and answers in kind.</DialogDescription>
        </DialogHeader>
        <div className="space-y-4 py-1">
          <div className="grid grid-cols-2 gap-2">
            {MODES.map((m) => (
              <button key={m.id} type="button" onClick={() => { setMode(m.id); setResult(null); setApplied(null); }}
                className={cn("flex items-start gap-2 rounded-lg border p-3 text-left text-xs transition-all hover:border-primary/50",
                  mode === m.id && "border-primary bg-primary/5 shadow-sm")}>
                <span className="mt-0.5 text-primary">{m.icon}</span>
                <span>
                  <span className="block font-medium">{m.label}</span>
                  <span className="text-muted-foreground">{m.hint}</span>
                </span>
              </button>
            ))}
          </div>
          {mode === "instruction" && (
            <div>
              <Label className="text-xs">How should the AI rewrite it?</Label>
              <Textarea className="mt-1" rows={3} value={instruction} onChange={(e) => setInstruction(e.target.value)}
                placeholder="e.g. Make it shorter and more formal, addressed to parents, mention the PTA meeting on Friday" />
            </div>
          )}
          {mode === "translate" && (
            <div>
              <Label className="text-xs">Translate the whole document into</Label>
              <Select value={lang} onValueChange={setLang}>
                <SelectTrigger className="mt-1 w-56"><SelectValue /></SelectTrigger>
                <SelectContent>{DOC_LANGS.map((l) => <SelectItem key={l.value} value={l.value}>{l.label}</SelectItem>)}</SelectContent>
              </Select>
              <p className="mt-1.5 text-[11px] text-muted-foreground">Structure, names and numbers are kept; the translation opens below — replace the document or copy it out.</p>
            </div>
 )}

          {loading && (
            <div className="flex items-center justify-center gap-2 rounded-lg border border-dashed p-6 text-sm text-muted-foreground">
              <Loader2 className="size-4 animate-spin" /> The AI is reading your document…
            </div>
          )}

          {result?.changes && (
            <div className="space-y-2">
              <div className="text-xs font-medium">Corrections proposed ({result.changes.length})</div>
              <div className="max-h-56 space-y-1.5 overflow-y-auto pr-1">
                {result.changes.map((c, i) => (
                  <div key={i} className="flex items-center gap-2 rounded border px-2.5 py-1.5 text-xs">
                    <span className="text-destructive line-through flex-1 truncate">{c.from}</span>
                    <span className="text-muted-foreground">→</span>
                    <span className="font-medium text-emerald-600 flex-1 truncate">{c.to}</span>
                    <span className="flex gap-1">
                      <Button type="button" size="sm" variant="ghost" className="h-6 px-1.5 text-[11px] text-emerald-600 hover:text-emerald-700" onClick={() => applyOne(i)} aria-label="Accept this correction">
                        <CheckCircle2 className="size-3.5" /> Fix
                      </Button>
                      <Button type="button" size="sm" variant="ghost" className="h-6 px-1.5 text-[11px] text-muted-foreground" onClick={() => dismissOne(i)} aria-label="Dismiss this correction">
                        <X className="size-3.5" />
                      </Button>
                    </span>
                  </div>
                ))}
                {!result.changes.length && <p className="text-xs text-muted-foreground">All corrections handled — the document reads clean.</p>}
              </div>
              {result.changes.length > 0 && (
                <div className="flex items-center gap-2">
                  <Button size="sm" onClick={applyFixes}><SpellCheck className="size-3.5 mr-1" /> Apply all remaining ({result.changes.length})</Button>
                  {(applied ?? 0) > 0 && <span className="text-xs text-muted-foreground">{applied} applied so far — accept or dismiss each one, or bulk-apply the rest.</span>}
                </div>
              )}
            </div>
          )}

          {result?.text !== undefined && result.text !== "" && (
            <div className="space-y-2">
              <div className="text-xs font-medium">Translation ({DOC_LANGS.find((l) => l.value === lang)?.label ?? lang})</div>
              <div
                className="max-h-64 overflow-y-auto whitespace-pre-wrap rounded-lg border bg-white p-4 text-sm text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100"
                style={{ fontFamily: "Georgia, serif" }}
              >
                {result.text}
              </div>
              <div className="flex flex-wrap gap-2">
                <Button size="sm" onClick={() => { replaceDoc(paragraphsToHtml(result.text!)); toast({ title: "Document replaced with the translation", description: "Undo (Ctrl+Z) brings the original back." }); onOpenChange(false); }}>
                  <Replace className="size-3.5 mr-1" /> Replace document
                </Button>
                <Button size="sm" variant="outline" onClick={() => { void navigator.clipboard?.writeText(result.text!); toast({ title: "Translation copied" }); }}>
                  <Copy className="size-3.5 mr-1" /> Copy
                </Button>
              </div>
            </div>
          )}

          {result?.html !== undefined && result.html !== "" && (
            <div className="space-y-2">
              <div className="text-xs font-medium">Preview</div>
              <div
                className="max-h-64 overflow-y-auto rounded-lg border bg-white p-4 text-sm text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100"
                style={{ fontFamily: "Georgia, serif" }}
                dangerouslySetInnerHTML={{ __html: result.html }}
              />
              <div className="flex gap-2">
                <Button size="sm" onClick={() => { replaceDoc(result.html!); toast({ title: "Document replaced", description: "Undo (Ctrl+Z) brings the old text back." }); onOpenChange(false); }}>
                  <Replace className="size-3.5 mr-1" /> Replace document
                </Button>
                <Button size="sm" variant="outline" onClick={() => { void navigator.clipboard?.writeText(result.html!); toast({ title: "Copied" }); }}>
                  <Copy className="size-3.5 mr-1" /> Copy
                </Button>
              </div>
            </div>
          )}
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Close</Button>
          <Button onClick={run} disabled={loading}>{loading ? "Thinking…" : "Run AI"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
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

function parseSheet(contentJson: string): SheetContent {
  try {
    const p = JSON.parse(contentJson);
    return { cols: Math.min(26, Math.max(6, p?.cols ?? 18)), rows: Math.min(200, Math.max(10, p?.rows ?? 40)), cells: p?.cells ?? {} };
  } catch { return { cols: 18, rows: 40, cells: {} }; }
}

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
  const seed = useMemo<SheetContent>(() => parseSheet(file.contentJson), [file.contentJson]);
  const [content, setContent] = useState<SheetContent>(seed);
  const [active, setActive] = useState<{ c: number; r: number }>({ c: 0, r: 0 });
  const [editing, setEditing] = useState<{ c: number; r: number; draft: string } | null>(null);
  const [aiOpen, setAiOpen] = useState(false);
  const gridRef = useRef<HTMLDivElement>(null);
  const cellInputRef = useRef<HTMLInputElement>(null);

  const build = useCallback(() => JSON.stringify(content), [content]);
  const { markDirty, doSave } = useAutosave(file, build, onSaved, onDirtyChange);
  const [saving, setSaving] = useState(false);

  const computed = useMemo(() => evaluateSheet(content), [content]);

  const setCell = (key: string, patch: Partial<Cell>) =>
    setContent((s) => ({ ...s, cells: { ...s.cells, [key]: { ...s.cells[key], ...patch } } }));

  /** insert / delete a row or column at a position, renumbering cell keys. */
  const shift = (axis: "row" | "col", delta: 1 | -1, at: number) =>
    setContent((s) => {
      const cells: Record<string, Cell> = {};
      for (const [key, cell] of Object.entries(s.cells)) {
        const m = /^([A-Z]{1,2})(\d{1,4})$/.exec(key);
        if (!m) { cells[key] = cell; continue; }
        const c = colIndex(m[1]);
        const r = parseInt(m[2], 10) - 1;
        let nc = c, nr = r;
        if (axis === "row" && r >= at) nr += delta;
        if (axis === "col" && c >= at) nc += delta;
        if (nr < 0 || nr >= s.rows || nc < 0 || nc >= s.cols) continue; // shifted off the grid
        cells[cellKey(nc, nr)] = cell;
      }
      const rows = axis === "row" ? Math.min(200, Math.max(10, s.rows + delta)) : s.rows;
      const cols = axis === "col" ? Math.min(26, Math.max(6, s.cols + delta)) : s.cols;
      return { ...s, cells, rows, cols };
    });

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

  const exportCsv = () => downloadBlob(`${file.title.replace(/[\\/:*?"<>|]/g, "-") || "workbook"}.csv`, "text/csv", sheetCsv(file.title, JSON.stringify(content)));

  const activeKey = cellKey(active.c, active.r);
  const activeCell = content.cells[activeKey];
  const style = (key: string) => content.cells[key];

  /** the header row + first data row of the current column (AI formula context). */
  const columnContext = () => {
    const headers: string[] = [];
    for (let c = Math.max(0, active.c - 3); c <= Math.min(content.cols - 1, active.c + 3); c++) {
      headers.push(`${colName(c)}: ${content.cells[cellKey(c, 0)]?.v ?? "(blank)"}`);
    }
    const samples: string[] = [];
    for (let r = 1; r <= Math.min(4, content.rows - 1); r++) {
      const v = content.cells[cellKey(active.c, r)]?.v;
      if (v) samples.push(`${cellKey(active.c, r)} = ${v}`);
    }
    return `Columns (header row): ${headers.join(" | ")}. Column ${colName(active.c)} sample values: ${samples.join(", ") || "(empty)"}. Active cell: ${activeKey}.`;
  };

  return (
    <div className="flex h-full min-h-0 flex-col">
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
          <Button type="button" variant="ghost" size="sm" className="h-8 gap-1 text-xs" title={`Insert a row above row ${active.r + 1}`}
            onClick={() => { shift("row", 1, active.r); markDirty(); }}><Rows3 className="size-3.5" /> + Row</Button>
          <Button type="button" variant="ghost" size="sm" className="h-8 gap-1 text-xs" title={`Delete row ${active.r + 1}`}
            onClick={() => { shift("row", -1, active.r); markDirty(); }}><Rows3 className="size-3.5 text-destructive" /> − Row</Button>
          <Button type="button" variant="ghost" size="sm" className="h-8 gap-1 text-xs" title={`Insert a column left of ${colName(active.c)}`}
            onClick={() => { shift("col", 1, active.c); markDirty(); }}><Columns3 className="size-3.5" /> + Col</Button>
          <Button type="button" variant="ghost" size="sm" className="h-8 gap-1 text-xs" title={`Delete column ${colName(active.c)}`}
            onClick={() => { shift("col", -1, active.c); markDirty(); }}><Columns3 className="size-3.5 text-destructive" /> − Col</Button>
          <Button type="button" variant="ghost" size="sm" className="h-8 gap-1 text-xs" title="Grow by 10 rows"
            onClick={() => { setContent((s) => ({ ...s, rows: Math.min(200, s.rows + 10) })); markDirty(); }}>
            <Plus className="size-3.5 mr-0.5" />10 rows</Button>
          <Button type="button" size="sm" className="h-8 gap-1" onClick={() => setAiOpen(true)}>
            <Sparkles className="size-3.5" /> AI formula
          </Button>
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

      {/* ── AI formula assistant ── */}
      <AiSheetPanel
        open={aiOpen}
        onOpenChange={setAiOpen}
        contextText={columnContext()}
        activeFormula={activeCell?.v?.startsWith("=") ? activeCell.v : ""}
        onFormula={(f) => { setCell(activeKey, { v: f }); markDirty(); }}
      />
    </div>
  );
}

/* ── AI panel for sheets — natural language → formula, and formula explain ── */
function AiSheetPanel({ open, onOpenChange, contextText, activeFormula, onFormula }: {
  open: boolean;
  onOpenChange: (v: boolean) => void;
  contextText: string;
  activeFormula: string;
  onFormula: (formula: string) => void;
}) {
  const { toast } = useToast();
  const [want, setWant] = useState("");
  const [loading, setLoading] = useState(false);
  const [answer, setAnswer] = useState<{ formula?: string; explanation?: string } | null>(null);

  const run = async (mode: "formula" | "explain") => {
    if (mode === "formula" && !want.trim()) { toast({ title: "Describe what you want to calculate", variant: "destructive" }); return; }
    if (mode === "explain" && !activeFormula) { toast({ title: "Select a cell with a formula first", variant: "destructive" }); return; }
    setLoading(true);
    setAnswer(null);
    try {
      const d = await officeAI({ mode, instruction: want.slice(0, 400), formula: activeFormula, text: contextText });
      setAnswer({ formula: d.formula ? String(d.formula) : undefined, explanation: d.explanation ? String(d.explanation) : undefined });
      if (mode === "formula" && !d.formula) toast({ title: "Couldn't build that formula", description: "Try naming the columns and the operation, e.g. 'total of the Amount column B2 to B40'." });
    } catch (e: any) {
      toast({ title: "AI assist failed", description: e.message, variant: "destructive" });
    } finally { setLoading(false); }
  };

  return (
    <Dialog open={open} onOpenChange={(v) => { onOpenChange(v); if (!v) setAnswer(null); }}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><Sparkles className="size-4 text-primary" /> AI formula assistant</DialogTitle>
          <DialogDescription>Describe the calculation in plain English — the AI writes the spreadsheet formula.</DialogDescription>
        </DialogHeader>
        <div className="space-y-4 py-1">
          <Textarea rows={3} value={want} onChange={(e) => setWant(e.target.value)}
            placeholder="e.g. Add up the fees paid in column B from row 2 to row 40, then take 5% off" />
          <p className="rounded border bg-muted/50 px-2.5 py-1.5 text-[11px] text-muted-foreground">{contextText}</p>
          {loading && <div className="flex items-center justify-center gap-2 rounded-lg border border-dashed p-4 text-sm text-muted-foreground"><Loader2 className="size-4 animate-spin" /> Thinking…</div>}
          {answer?.formula && (
            <div className="space-y-2">
              <div className="rounded-lg border bg-background p-3 font-mono text-sm font-semibold">{answer.formula}</div>
              {answer.explanation && <p className="text-xs text-muted-foreground">{answer.explanation}</p>}
              <Button size="sm" onClick={() => { onFormula(answer.formula!); toast({ title: `Formula placed in the active cell` }); onOpenChange(false); }}>
                <Replace className="size-3.5 mr-1" /> Put formula in active cell
              </Button>
            </div>
          )}
          {answer && !answer.formula && answer.explanation && (
            <div className="rounded-lg border bg-background p-3 text-sm">{answer.explanation}</div>
          )}
        </div>
        <DialogFooter className="sm:justify-between">
          <Button variant="outline" onClick={() => run("explain")} disabled={loading || !activeFormula}>Explain my formula</Button>
          <Button onClick={() => run("formula")} disabled={loading}>{loading ? "Thinking…" : "Write my formula"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
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
  const [aiOpen, setAiOpen] = useState(false);

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

  const exportDeck = () => downloadBlob(`${file.title.replace(/[\\/:*?"<>|]/g, "-") || "deck"}.html`, "text/html", slideHtml(file.title, JSON.stringify(content)));

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
    <div className="flex h-full min-h-0 flex-col">
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
        <Button type="button" size="sm" className="h-8 gap-1" onClick={() => setAiOpen(true)}>
          <Sparkles className="size-3.5" /> AI deck
        </Button>
        <div className="ml-auto flex items-center gap-1.5">
          <Button size="sm" variant="outline" onClick={addSlide}><Plus className="size-3.5 mr-1" /> Slide</Button>
          <Button size="sm" variant="outline" onClick={exportDeck}><Download className="size-3.5 mr-1" /> Export deck</Button>
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

      {/* ── AI deck generator ── */}
      <AiSlidePanel
        open={aiOpen}
        onOpenChange={setAiOpen}
        onApply={(slides, how) => {
          setContent((s) => ({ ...s, slides: how === "replace" ? slides : [...s.slides, ...slides] }));
          if (how === "replace") setIdx(0);
          markDirty();
        }}
      />
    </div>
  );
}

/* ── AI panel for slides — generate a deck from a topic ── */
function AiSlidePanel({ open, onOpenChange, onApply }: {
  open: boolean;
  onOpenChange: (v: boolean) => void;
  onApply: (slides: Slide[], how: "append" | "replace") => void;
}) {
  const { toast } = useToast();
  const [topic, setTopic] = useState("");
  const [audience, setAudience] = useState("JHS learners");
  const [count, setCount] = useState(6);
  const [loading, setLoading] = useState(false);
  const [deck, setDeck] = useState<Slide[] | null>(null);

  const run = async () => {
    if (!topic.trim()) { toast({ title: "What should the deck teach?", variant: "destructive" }); return; }
    setLoading(true);
    setDeck(null);
    try {
      const d = await officeAI({ mode: "deck", topic: topic.slice(0, 300), audience: audience.slice(0, 120), slideCount: count });
      const slides: Slide[] = (Array.isArray(d.slides) ? d.slides : [])
        .filter((s: any) => s && typeof s === "object")
        .slice(0, 12)
        .map((s: any) => ({ title: String(s.title ?? "").slice(0, 120), body: String(s.body ?? "").slice(0, 800), bg: "", notes: String(s.notes ?? "").slice(0, 600) }));
      if (!slides.length) throw new Error("The AI returned an empty deck — try a more specific topic");
      setDeck(slides);
    } catch (e: any) {
      toast({ title: "Deck generation failed", description: e.message, variant: "destructive" });
    } finally { setLoading(false); }
  };

  return (
    <Dialog open={open} onOpenChange={(v) => { onOpenChange(v); if (!v) setDeck(null); }}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><Sparkles className="size-4 text-primary" /> AI deck generator</DialogTitle>
          <DialogDescription>Name a topic — the AI drafts the whole teaching deck with bullets and speaker notes, in a Ghanaian classroom voice.</DialogDescription>
        </DialogHeader>
        <div className="space-y-4 py-1">
          <div>
            <Label className="text-xs">Topic</Label>
            <Input className="mt-1" value={topic} onChange={(e) => setTopic(e.target.value)} placeholder="e.g. Photosynthesis — B8 integrated science" />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <Label className="text-xs">Audience</Label>
              <Input className="mt-1" value={audience} onChange={(e) => setAudience(e.target.value)} placeholder="JHS learners, PTA meeting…" />
            </div>
            <div>
              <Label className="text-xs">Slides</Label>
              <Select value={String(count)} onValueChange={(v) => setCount(Number(v))}>
                <SelectTrigger className="mt-1"><SelectValue /></SelectTrigger>
                <SelectContent>{[4, 6, 8, 10].map((n) => <SelectItem key={n} value={String(n)}>{n} slides</SelectItem>)}</SelectContent>
              </Select>
            </div>
          </div>
          {loading && <div className="flex items-center justify-center gap-2 rounded-lg border border-dashed p-6 text-sm text-muted-foreground"><Loader2 className="size-4 animate-spin" /> Drafting the deck…</div>}
          {deck && (
            <div className="space-y-2">
              <div className="text-xs font-medium">Preview — {deck.length} slides</div>
              <div className="max-h-56 space-y-1.5 overflow-y-auto pr-1">
                {deck.map((s, i) => (
                  <div key={i} className="rounded border px-3 py-2 text-xs">
                    <div className="font-semibold">{i + 1}. {s.title}</div>
                    <div className="mt-0.5 whitespace-pre-wrap text-muted-foreground">{s.body.slice(0, 160)}{s.body.length > 160 ? "…" : ""}</div>
                  </div>
                ))}
              </div>
              <div className="flex gap-2">
                <Button size="sm" onClick={() => { onApply(deck, "replace"); toast({ title: "Deck replaced", description: `${deck.length} slides drafted.` }); onOpenChange(false); }}>
                  <Replace className="size-3.5 mr-1" /> Replace deck
                </Button>
                <Button size="sm" variant="outline" onClick={() => { onApply(deck, "append"); toast({ title: "Slides appended", description: `${deck.length} slides added at the end.` }); onOpenChange(false); }}>
                  <Plus className="size-3.5 mr-1" /> Append
                </Button>
              </div>
            </div>
          )}
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Close</Button>
          <Button onClick={run} disabled={loading}>{loading ? "Drafting…" : "Generate deck"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export { DocEditor, SheetEditor, SlideEditor };

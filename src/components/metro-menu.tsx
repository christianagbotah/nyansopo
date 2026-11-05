"use client";
import { useEffect, useMemo, useState } from "react";
import { cn } from "@/lib/utils";
import { X, Search, LayoutGrid, LayoutList, ChevronRight } from "lucide-react";
import { MODULE_LABELS, PAGE_SECTIONS, type PageEntry } from "@/lib/page-index";

/* ── Metro Start screen (iEduPro metro_menu.php parity — the Windows-8 Start
   menu CI3 users know). CI3 layout rules reproduced EXACTLY (metro_menu.php
   .metro-tile / .metro-tiles / @media 768px):
   • every tile is a FIXED 150×150 square (120×120 ≤768px), never fluid
   • icon 60px (45px mobile), label 12px semibold (10px mobile)
   • every group holds EXACTLY 6 tiles (2 columns × 3 rows, 10px gaps) —
     no section is longer or shorter than the others
   • groups flow as columns on a repeat(auto-fit, minmax(320px, 1fr)) grid
   • flat MS-Metro palette, uppercase group titles at 60% white,
     staggered scale-in animation sorted by on-screen position, live search.
   EXTENDED (wave 10): the search box now queries the FULL page catalogue —
   every tab of every module — so a user can virtually find any page in the
   app, plus an A–Z "All pages" browse mode. ── */

export type MetroTile = {
  id: string;
  label: string;
  icon: any;
  module: string;
  target?: any;
  color: string;
};
export type MetroSection = { title: string; tiles: MetroTile[] };

const SECTION_HUE: Record<string, string> = {
  "Dashboard & Quick Access": "#0078d4",
  "Students & People": "#008272",
  "Student Billing": "#5c2d91",
  "Collections & Cashier": "#107c10",
  "Daily Services & Transport": "#d83b01",
  "Academics & Exams": "#881798",
  "Finance & Communication": "#00b294",
  "System & Administration": "#5d5a58",
  "Reports & Analytics": "#ca5010",
  "Self-Service": "#ec008c",
};

export function MetroMenu({ sections, pages, current, currentTarget, onSelect, open, onOpenChange }: {
  sections: MetroSection[];
  pages: PageEntry[];
  current: string;
  currentTarget?: any;
  onSelect: (module: string, target?: any) => void;
  open: boolean;
  onOpenChange: (o: boolean) => void;
}) {
  const [query, setQuery] = useState("");
  const [browseAll, setBrowseAll] = useState(false);

  // Esc to close + lock body scroll while open (a Start screen takes over)
  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => { if (e.key === "Escape") onOpenChange(false); };
    window.addEventListener("keydown", onKey);
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => { window.removeEventListener("keydown", onKey); document.body.style.overflow = prev; };
  }, [open, onOpenChange]);

  const q = query.trim().toLowerCase();

  const visible = useMemo(() => {
    if (!q) return sections;
    return sections
      .map((s) => ({ ...s, tiles: s.tiles.filter((t) => t.label.toLowerCase().includes(q) || s.title.toLowerCase().includes(q)) }))
      .filter((s) => s.tiles.length > 0);
  }, [sections, q]);

  /* full-catalogue matches: label, section, module label or keywords */
  const pageHits = useMemo(() => {
    if (!q) return [];
    return pages.filter((p) => {
      const hay = [p.label, p.section, MODULE_LABELS[p.module] ?? p.module, ...(p.keywords ?? [])].join(" ").toLowerCase();
      return hay.includes(q);
    });
  }, [pages, q]);

  /* A–Z browse: every page, grouped by section in catalogue order */
  const browseGroups = useMemo(() => {
    return PAGE_SECTIONS
      .map((title) => ({ title, items: pages.filter((p) => p.section === title) }))
      .filter((g) => g.items.length > 0);
  }, [pages]);

  const showSearchResults = !!q;
  const showBrowse = browseAll && !q;

  // staggered tile-in delays sorted by on-screen position (CI3: +80ms each)
  const tileIndex = new Map<string, number>();
  let running = 0;
  for (const s of visible) for (const t of s.tiles) tileIndex.set(t.id, running++);

  if (!open) return null;

  const openPage = (p: PageEntry) => { onSelect(p.module, p.target); onOpenChange(false); };

  return (
    <div className="fixed inset-0 z-[90]" role="dialog" aria-label="Start menu">
      {/* backdrop */}
      <div className="absolute inset-0 bg-black/80 animate-in fade-in duration-200" onClick={() => onOpenChange(false)} />
      {/* start screen — slides in from the right, CI3 style */}
      <div className="absolute inset-0 overflow-y-auto nice-scroll metro-start-bg animate-in slide-in-from-right duration-300">
        <div className="mx-auto max-w-[1600px] px-6 sm:px-10 lg:px-20 pb-16">
          {/* header: Start + search + browse + close */}
          <div className="sticky top-0 z-10 flex flex-wrap items-center gap-4 py-6 sm:py-8 metro-start-bg">
            <h1 className="text-4xl sm:text-[42px] font-extralight text-white tracking-wide flex items-center gap-3">
              <LayoutGrid className="size-8 sm:size-9 opacity-80" strokeWidth={1.5} /> Start
            </h1>
            <div className="relative flex-1 min-w-[220px] max-w-md">
              <Search className="size-4 absolute left-3 top-1/2 -translate-y-1/2 text-white/50" />
              <input
                autoFocus
                value={query}
                onChange={(e) => { setQuery(e.target.value); if (e.target.value) setBrowseAll(false); }}
                placeholder="Search any page… (e.g. “receipts”, “opt out”, “SSNIT”)"
                className="w-full rounded-lg border border-white/20 bg-white/10 px-9 py-2.5 text-white placeholder:text-white/50 outline-none transition focus:border-sky-400/60 focus:bg-white/15"
              />
              {query && (
                <button className="absolute right-2.5 top-1/2 -translate-y-1/2 text-white/50 hover:text-white" onClick={() => setQuery("")} aria-label="Clear search">
                  <X className="size-4" />
                </button>
              )}
            </div>
            <button
              className={cn(
                "flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition-colors",
                browseAll ? "border-sky-400/70 bg-sky-400/20 text-white" : "border-white/20 bg-white/5 text-white/80 hover:bg-white/15 hover:text-white",
              )}
              onClick={() => { setBrowseAll(!browseAll); setQuery(""); }}
              title="Browse the full page catalogue"
            >
              <LayoutList className="size-4" />
              <span className="hidden sm:inline">All pages</span>
              <span className="sm:hidden">Pages</span>
              <span className="rounded bg-white/15 px-1.5 py-0.5 text-[10px] tabular-nums">{pages.length}</span>
            </button>
            <button
              className="ml-auto rounded-lg p-2 text-white/70 hover:bg-white/10 hover:text-white"
              onClick={() => onOpenChange(false)}
              title="Close (Esc)"
            >
              <X className="size-5" />
            </button>
          </div>

          {/* ── mode: search results ── */}
          {showSearchResults && (
            <>
              {/* page catalogue hits — this is the “find any page” layer */}
              {pageHits.length > 0 && (
                <section className="mb-8" aria-label="All pages">
                  <h2 className="mb-3 text-sm font-semibold uppercase tracking-[0.1em] text-white/60">
                    Pages · {pageHits.length} match{pageHits.length === 1 ? "" : "es"}
                  </h2>
                  <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {pageHits.slice(0, 48).map((p) => {
                      const Icon = p.icon;
                      const hue = SECTION_HUE[p.section] ?? "#0078d4";
                      const active = p.module === current && (
                        !p.target || (p.target.tab ? p.target.tab === currentTarget?.tab : !currentTarget?.tab)
                      );
                      return (
                        <button
                          key={p.id}
                          onClick={() => openPage(p)}
                          className={cn(
                            "group flex items-center gap-3 rounded-lg border border-white/15 bg-white/5 p-2.5 text-left transition-all hover:border-white/30 hover:bg-white/10 active:scale-[0.98]",
                            active && "ring-2 ring-white/80",
                          )}
                        >
                          <span className="grid size-9 shrink-0 place-items-center rounded-md" style={{ backgroundColor: hue }}>
                            <Icon className="size-4.5 text-white" strokeWidth={1.5} />
                          </span>
                          <span className="min-w-0 flex-1">
                            <span className="block truncate text-sm font-semibold text-white">{p.label}</span>
                            <span className="block truncate text-[11px] text-white/50">
                              {MODULE_LABELS[p.module] ?? p.module}{p.target?.tab ? " · deep link" : ""}
                            </span>
                          </span>
                          <ChevronRight className="size-4 shrink-0 text-white/30 transition-transform group-hover:translate-x-0.5" />
                        </button>
                      );
                    })}
                  </div>
                  {pageHits.length > 48 && (
                    <p className="mt-2 text-[11px] text-white/40">Showing the first 48 — refine the search to narrow it down.</p>
                  )}
                </section>
              )}

              {/* tile hits — the classic Start squares */}
              {visible.length > 0 && (
                <>
                  <h2 className="mb-3 text-sm font-semibold uppercase tracking-[0.1em] text-white/60">Quick tiles</h2>
                  <div className="grid grid-cols-[repeat(auto-fill,minmax(320px,1fr))] gap-[30px] max-md:grid-cols-[repeat(auto-fill,minmax(280px,1fr))] max-sm:grid-cols-1 max-w-[1360px] mx-auto">
                    {visible.map((s) => (
                      <section key={s.title} aria-label={s.title}>
                        <h3 className="mb-3 text-sm font-semibold uppercase tracking-[0.1em] text-white/60">{s.title}</h3>
                        <div className="grid grid-cols-[repeat(2,150px)] gap-2.5 max-md:grid-cols-[repeat(2,120px)] max-sm:grid-cols-[repeat(2,minmax(0,150px))] max-sm:justify-items-start">
                          {s.tiles.map((t) => (
                            <MetroTileButton key={t.id} t={t} current={current} currentTarget={currentTarget} onSelect={onSelect} onOpenChange={onOpenChange} delay={Math.min((tileIndex.get(t.id) ?? 0) * 80, 1600)} />
                          ))}
                        </div>
                      </section>
                    ))}
                  </div>
                </>
              )}

              {pageHits.length === 0 && visible.length === 0 && (
                <div className="rounded-xl border border-white/15 bg-white/5 p-8 text-center text-white/70">
                  Nothing matches <b className="text-white">“{query}”</b>. Try “receipt”, “attendance”, “discount”…
                </div>
              )}
            </>
          )}

          {/* ── mode: A–Z full catalogue browse ── */}
          {showBrowse && (
            <div className="space-y-8">
              {browseGroups.map((g) => (
                <section key={g.title} aria-label={g.title}>
                  <h2 className="mb-3 text-sm font-semibold uppercase tracking-[0.1em] text-white/60">
                    {g.title} <span className="ml-1 rounded bg-white/10 px-1.5 py-0.5 text-[10px] tabular-nums text-white/60">{g.items.length}</span>
                  </h2>
                  <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {g.items.map((p) => {
                      const Icon = p.icon;
                      const hue = SECTION_HUE[p.section] ?? "#0078d4";
                      return (
                        <button
                          key={p.id}
                          onClick={() => openPage(p)}
                          className="group flex items-center gap-3 rounded-lg border border-white/10 bg-white/5 p-2.5 text-left transition-all hover:border-white/30 hover:bg-white/10 active:scale-[0.98]"
                        >
                          <span className="grid size-8 shrink-0 place-items-center rounded-md" style={{ backgroundColor: hue }}>
                            <Icon className="size-4 text-white" strokeWidth={1.5} />
                          </span>
                          <span className="min-w-0 flex-1">
                            <span className="block truncate text-sm font-semibold text-white">{p.label}</span>
                            <span className="block truncate text-[11px] text-white/50">{MODULE_LABELS[p.module] ?? p.module}</span>
                          </span>
                          <ChevronRight className="size-4 shrink-0 text-white/30 transition-transform group-hover:translate-x-0.5" />
                        </button>
                      );
                    })}
                  </div>
                </section>
              ))}
            </div>
          )}

          {/* ── mode: default Start (tiles, CI3 exact) ── */}
          {!showSearchResults && !showBrowse && (
            <>
              {/* 9 groups × 3 columns = a perfectly uniform 3×3 metro wall; the 1360px
                  cap keeps the grid at 3 columns even on ultra-wide monitors so the
                  bottom row never strands empty columns (CI3 groups auto-fit wider) */}
              <div className="grid grid-cols-[repeat(auto-fill,minmax(320px,1fr))] gap-[30px] max-md:grid-cols-[repeat(auto-fill,minmax(280px,1fr))] max-sm:grid-cols-1 max-w-[1360px] mx-auto">
                {visible.map((s) => (
                  <section key={s.title} aria-label={s.title}>
                    <h2 className="mb-3 text-sm font-semibold uppercase tracking-[0.1em] text-white/60">{s.title}</h2>
                    {/* CI3 .metro-tiles: repeat(2, 150px), 10px gap — FIXED tile width */}
                    <div className="grid grid-cols-[repeat(2,150px)] gap-2.5 max-md:grid-cols-[repeat(2,120px)] max-sm:grid-cols-[repeat(2,minmax(0,150px))] max-sm:justify-items-start">
                      {s.tiles.map((t) => (
                        <MetroTileButton key={t.id} t={t} current={current} currentTarget={currentTarget} onSelect={onSelect} onOpenChange={onOpenChange} delay={Math.min((tileIndex.get(t.id) ?? 0) * 80, 1600)} />
                      ))}
                    </div>
                  </section>
                ))}
              </div>
              {visible.length === 0 && (
                <div className="rounded-xl border border-white/15 bg-white/5 p-8 text-center text-white/70">
                  No modules match <b className="text-white">“{query}”</b>.
                </div>
              )}
            </>
          )}

          <p className="mt-8 text-center text-[11px] text-white/40">
            {pages.length} pages · {sections.reduce((n, s) => n + s.tiles.length, 0)} quick tiles — search finds any page in the app. Press <kbd className="rounded border border-white/20 px-1">Esc</kbd> to return.
          </p>
        </div>
      </div>
    </div>
  );
}

function MetroTileButton({ t, current, currentTarget, onSelect, onOpenChange, delay }: {
  t: MetroTile;
  current: string;
  currentTarget?: any;
  onSelect: (module: string, target?: any) => void;
  onOpenChange: (o: boolean) => void;
  delay: number;
}) {
  const Icon = t.icon;
  const active = t.module === current && (
    !t.target || (t.target.tab ? t.target.tab === currentTarget?.tab : t.target.classroomId ? t.target.classroomId === currentTarget?.classroomId : !currentTarget?.tab && !currentTarget?.classroomId)
  );
  return (
    <button
      onClick={() => { onSelect(t.module, t.target); onOpenChange(false); }}
      style={{ backgroundColor: t.color, animationDelay: `${delay}ms` }}
      className={cn(
        "metro-tile group relative flex size-[150px] flex-col items-center justify-center gap-2 overflow-hidden p-[15px] text-white max-md:size-[120px] max-md:p-3",
        "transition-transform duration-150 hover:scale-[1.05] active:scale-[0.97]",
        active && "ring-2 ring-white/80 ring-offset-2 ring-offset-transparent",
      )}
      title={t.label}
    >
      <Icon className="size-[60px] opacity-90 transition-transform duration-200 group-hover:scale-110 max-md:size-[45px]" strokeWidth={1.4} />
      <span className="line-clamp-2 text-center text-xs font-semibold leading-tight max-md:mt-0 max-md:text-[10px]">{t.label}</span>
      {active && <span className="absolute right-2 top-2 size-2 rounded-full bg-white/80" />}
    </button>
  );
}

/** Floating edge trigger (CI3 parity: the launcher button pinned to the right edge). */
export function MetroEdgeTrigger({ onClick, hidden }: { onClick: () => void; hidden?: boolean }) {
  return (
    <button
      onClick={onClick}
      title="Start menu"
      className={cn(
        "fixed right-0 top-24 z-40 grid size-11 place-items-center rounded-l-xl border border-r-0 border-primary/40",
        "bg-primary text-primary-foreground shadow-lg transition-all duration-300 hover:right-2 hover:scale-105",
        hidden ? "pointer-events-none opacity-0" : "opacity-90",
      )}
    >
      <LayoutGrid className="size-5" />
    </button>
  );
}

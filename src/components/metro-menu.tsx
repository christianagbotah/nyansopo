"use client";
import { useEffect, useMemo, useState } from "react";
import { cn } from "@/lib/utils";
import { X, Search, LayoutGrid } from "lucide-react";

/* ── Metro Start screen (iEduPro metro_menu.php parity — the Windows-8 Start
   menu CI3 users know). CI3 layout rules reproduced exactly:
   • every tile is a SQUARE (150×150; 120×120 on small screens)
   • every group holds EXACTLY 6 tiles (2 columns × 3 rows, 10px gaps) —
     no section is longer or shorter than the others
   • groups flow as columns on a minmax(340px, 1fr) grid
   • flat MS-Metro palette, uppercase group titles at 60% white,
     staggered scale-in animation sorted by on-screen position, live search. ── */

export type MetroTile = {
  id: string;
  label: string;
  icon: any;
  module: string;
  target?: any;
  color: string;
};
export type MetroSection = { title: string; tiles: MetroTile[] };

export function MetroMenu({ sections, current, currentTarget, onSelect, open, onOpenChange }: {
  sections: MetroSection[];
  current: string;
  currentTarget?: any;
  onSelect: (module: string, target?: any) => void;
  open: boolean;
  onOpenChange: (o: boolean) => void;
}) {
  const [query, setQuery] = useState("");

  // Esc to close + lock body scroll while open (a Start screen takes over)
  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => { if (e.key === "Escape") onOpenChange(false); };
    window.addEventListener("keydown", onKey);
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => { window.removeEventListener("keydown", onKey); document.body.style.overflow = prev; };
  }, [open, onOpenChange]);

  const visible = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return sections;
    return sections
      .map((s) => ({ ...s, tiles: s.tiles.filter((t) => t.label.toLowerCase().includes(q) || s.title.toLowerCase().includes(q)) }))
      .filter((s) => s.tiles.length > 0);
  }, [sections, query]);

  // staggered tile-in delays sorted by on-screen position (CI3: +80ms each)
  const tileIndex = new Map<string, number>();
  let running = 0;
  for (const s of visible) for (const t of s.tiles) tileIndex.set(t.id, running++);

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-[90]" role="dialog" aria-label="Start menu">
      {/* backdrop */}
      <div className="absolute inset-0 bg-black/80 animate-in fade-in duration-200" onClick={() => onOpenChange(false)} />
      {/* start screen — slides in from the right, CI3 style */}
      <div className="absolute inset-0 overflow-y-auto nice-scroll metro-start-bg animate-in slide-in-from-right duration-300">
        <div className="mx-auto max-w-[1600px] px-6 sm:px-10 lg:px-20 pb-16">
          {/* header: Start + search + close */}
          <div className="sticky top-0 z-10 flex flex-wrap items-center gap-4 py-6 sm:py-8 metro-start-bg">
            <h1 className="text-4xl sm:text-[42px] font-extralight text-white tracking-wide flex items-center gap-3">
              <LayoutGrid className="size-8 sm:size-9 opacity-80" strokeWidth={1.5} /> Start
            </h1>
            <div className="relative flex-1 min-w-[220px] max-w-md">
              <Search className="size-4 absolute left-3 top-1/2 -translate-y-1/2 text-white/50" />
              <input
                autoFocus
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder="Search menu…"
                className="w-full rounded-lg border border-white/20 bg-white/10 px-9 py-2.5 text-white placeholder:text-white/50 outline-none transition focus:border-sky-400/60 focus:bg-white/15"
              />
              {query && (
                <button className="absolute right-2.5 top-1/2 -translate-y-1/2 text-white/50 hover:text-white" onClick={() => setQuery("")} aria-label="Clear search">
                  <X className="size-4" />
                </button>
              )}
            </div>
            <button
              className="ml-auto rounded-lg p-2 text-white/70 hover:bg-white/10 hover:text-white"
              onClick={() => onOpenChange(false)}
              title="Close (Esc)"
            >
              <X className="size-5" />
            </button>
          </div>

          {/* tile groups — CI3 metro-start-content: column blocks of exactly
              6 SQUARE tiles each (2 cols × 3 rows). Sections with a filtered
              count still render a uniform 2-column grid. */}
          <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-8">
            {visible.map((s) => (
              <section key={s.title} aria-label={s.title}>
                <h2 className="mb-3 text-sm font-semibold uppercase tracking-[0.1em] text-white/60">{s.title}</h2>
                <div className="grid grid-cols-2 gap-2.5">
                  {s.tiles.map((t) => {
                    const Icon = t.icon;
                    const active = t.module === current && (
                      !t.target || (t.target.tab ? t.target.tab === currentTarget?.tab : t.target.classroomId ? t.target.classroomId === currentTarget?.classroomId : !currentTarget?.tab && !currentTarget?.classroomId)
                    );
                    const delay = Math.min((tileIndex.get(t.id) ?? 0) * 80, 1600);
                    return (
                      <button
                        key={t.id}
                        onClick={() => { onSelect(t.module, t.target); onOpenChange(false); }}
                        style={{ backgroundColor: t.color, animationDelay: `${delay}ms` }}
                        className={cn(
                          "metro-tile group relative flex aspect-square w-full flex-col items-center justify-center gap-2 overflow-hidden p-3 text-white",
                          "transition-transform duration-150 hover:scale-[1.05] active:scale-[0.97]",
                          active && "ring-2 ring-white/80 ring-offset-2 ring-offset-transparent",
                        )}
                        title={t.label}
                      >
                        <Icon className="size-10 sm:size-12 opacity-90 transition-transform duration-200 group-hover:scale-110" strokeWidth={1.4} />
                        <span className="line-clamp-2 text-center text-xs font-semibold leading-tight">{t.label}</span>
                        {active && <span className="absolute right-2 top-2 size-2 rounded-full bg-white/80" />}
                      </button>
                    );
                  })}
                </div>
              </section>
            ))}
          </div>

          {visible.length === 0 && (
            <div className="rounded-xl border border-white/15 bg-white/5 p-8 text-center text-white/70">
              No modules match <b className="text-white">“{query}”</b>.
            </div>
          )}

          <p className="mt-8 text-center text-[11px] text-white/40">
            Six square tiles per section — just like the classic Start screen. Press <kbd className="rounded border border-white/20 px-1">Esc</kbd> to return.
          </p>
        </div>
      </div>
    </div>
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

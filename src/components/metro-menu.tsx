"use client";
import { useEffect, useMemo, useState } from "react";
import { cn } from "@/lib/utils";
import { X, Search, LayoutGrid } from "lucide-react";

/* ── Metro Start screen (iEduPro metro_menu.php parity — the Windows-8 Start
   menu CI3 users know): a full-screen overlay of colorful grouped tiles with
   live search, opened from the header Start button or the floating edge
   trigger. Tiles map 1:1 to the permission-gated sidebar modules. ── */

export type MetroItem = { id: string; label: string; icon: any; section?: string; target?: any };

// the CI3 metro tile palette (metro_menu.php .tile-*)
const PALETTE = [
  "#0078d4", "#107c10", "#d83b01", "#5c2d91", "#008272", "#ff8c00",
  "#00bcf2", "#00b294", "#e81123", "#ec008c", "#881798", "#5d5a58",
];
const colorOf = (id: string) => PALETTE[[...id].reduce((a, c) => a + c.charCodeAt(0), 0) % PALETTE.length];

export function MetroMenu({ items, current, onSelect, open, onOpenChange }: {
  items: MetroItem[];
  current: string;
  onSelect: (id: string, target?: any) => void;
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

  const groups = useMemo(() => {
    const q = query.trim().toLowerCase();
    const matched = q ? items.filter((i) => i.label.toLowerCase().includes(q) || (i.section ?? "").toLowerCase().includes(q)) : items;
    const out: { section: string; items: MetroItem[] }[] = [];
    for (const it of matched) {
      const last = out[out.length - 1];
      if (last && last.section === (it.section ?? "More")) last.items.push(it);
      else out.push({ section: it.section ?? "More", items: [it] });
    }
    return out;
  }, [items, query]);

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-[90]" role="dialog" aria-label="Start menu">
      {/* backdrop */}
      <div className="absolute inset-0 bg-black/80 animate-in fade-in duration-200" onClick={() => onOpenChange(false)} />
      {/* start screen */}
      <div className="absolute inset-0 overflow-y-auto nice-scroll metro-start-bg animate-in slide-in-from-right duration-300">
        <div className="mx-auto max-w-6xl px-4 sm:px-8 pb-16">
          {/* header: Start + search */}
          <div className="sticky top-0 z-10 flex flex-wrap items-center gap-4 py-5 sm:py-8 metro-start-bg">
            <h1 className="text-3xl sm:text-5xl font-extralight text-white tracking-wide flex items-center gap-3">
              <LayoutGrid className="size-7 sm:size-9 opacity-80" /> Start
            </h1>
            <div className="relative flex-1 min-w-[220px] max-w-md">
              <Search className="size-4 absolute left-3 top-1/2 -translate-y-1/2 text-white/50" />
              <input
                autoFocus
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder="Search modules…"
                className="w-full rounded-lg border border-white/20 bg-white/10 px-9 py-2.5 text-white placeholder:text-white/50 outline-none transition focus:border-white/50 focus:bg-white/15"
              />
              {query && (
                <button className="absolute right-2.5 top-1/2 -translate-y-1/2 text-white/50 hover:text-white" onClick={() => setQuery("")}>
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

          {/* tile groups — keyed by position (a section may appear in more than
              one band, e.g. Finance either side of Facilities) */}
          {groups.map((g, gi) => (
            <section key={`${g.section}-${gi}`} className="mb-8">
              <h2 className="text-[11px] font-light uppercase tracking-[0.2em] text-white/50 mb-3">{g.section}</h2>
              <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2.5">
                {g.items.map((it, i) => {
                  const Icon = it.icon;
                  const active = it.id === current;
                  return (
                    <button
                      key={it.id}
                      onClick={() => { onSelect(it.id, it.target); onOpenChange(false); }}
                      style={{ backgroundColor: colorOf(it.id), animationDelay: `${Math.min(i * 40, 400)}ms` }}
                      className={cn(
                        "metro-tile group relative flex h-28 sm:h-32 flex-col items-center justify-center gap-2 overflow-hidden rounded-xl p-3 text-white",
                        "transition-transform duration-150 hover:scale-[1.04] active:scale-[0.98]",
                        active && "ring-2 ring-white/70 ring-offset-2 ring-offset-transparent",
                      )}
                    >
                      <Icon className="size-8 sm:size-10 opacity-90 transition-transform duration-200 group-hover:scale-110" strokeWidth={1.5} />
                      <span className="text-[11px] sm:text-xs font-semibold leading-tight text-center line-clamp-2">{it.label}</span>
                      {active && <span className="absolute right-2 top-2 size-2 rounded-full bg-white/80" />}
                    </button>
                  );
                })}
              </div>
            </section>
          ))}

          {groups.length === 0 && (
            <div className="rounded-xl border border-white/15 bg-white/5 p-8 text-center text-white/70">
              No modules match <b className="text-white">“{query}”</b>.
            </div>
          )}

          <p className="mt-4 text-center text-[11px] text-white/40">
            Tiles mirror your permissions — everything you can open is on the screen. Press <kbd className="rounded border border-white/20 px-1">Esc</kbd> to return.
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

"use client";
/* Modern calendar picker — replaces every native <Input type="date|month|week">
   across the app (user request). The WHOLE field opens the picker (not just an
   icon), the popover carries a react-day-picker v9 calendar (date/week) or a
   month grid, with Today/This-week/This-month quick actions and inline clear.
   Value strings stay identical to the native inputs ("yyyy-MM-dd",
   "yyyy-MM", "2026-W45") so existing state and API payloads are untouched. */
import { useMemo, useState } from "react";
import { format, parse, startOfWeek } from "date-fns";
import { CalendarDays, ChevronLeft, ChevronRight, X } from "lucide-react";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Calendar } from "@/components/ui/calendar";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

export type DateFieldMode = "date" | "month" | "week";

const DATE_RE = /^(\d{4})-(\d{2})-(\d{2})/;
function parseDateValue(v?: string | null): Date | null {
  if (!v) return null;
  const m = DATE_RE.exec(v.trim());
  if (!m) return null;
  const d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
  return Number.isNaN(d.getTime()) ? null : d;
}
function parseMonthValue(v?: string | null): Date | null {
  if (!v) return null;
  const m = /^(\d{4})-(\d{2})/.exec(v.trim());
  if (!m) return null;
  return new Date(Number(m[1]), Number(m[2]) - 1, 1);
}
function parseWeekValue(v?: string | null): Date | null {
  if (!v) return null;
  const m = /^(\d{4})-W(\d{1,2})$/.exec(v.trim());
  if (!m) return null;
  const d = parse(`${m[1]}-W${String(Number(m[2])).padStart(2, "0")}`, "RRRR-'W'II", new Date());
  if (Number.isNaN(d.getTime())) return null;
  return startOfWeek(d, { weekStartsOn: 1 });
}
const pad2 = (n: number) => String(n).padStart(2, "0");

export function DateField({
  value,
  onChange,
  mode = "date",
  min,
  max,
  placeholder,
  className,
  disabled,
  "aria-label": ariaLabel,
}: {
  value?: string | null;
  onChange: (v: string | null) => void;
  mode?: DateFieldMode;
  /** inclusive lower bound, "yyyy-MM-dd" */
  min?: string;
  /** inclusive upper bound, "yyyy-MM-dd" */
  max?: string;
  placeholder?: string;
  className?: string;
  disabled?: boolean;
  "aria-label"?: string;
}) {
  const [open, setOpen] = useState(false);
  const minD = useMemo(() => parseDateValue(min), [min]);
  const maxD = useMemo(() => parseDateValue(max), [max]);

  const selected = useMemo(
    () => (mode === "month" ? parseMonthValue(value) : mode === "week" ? parseWeekValue(value) : parseDateValue(value)),
    [value, mode]
  );
  const [view, setView] = useState<Date>(selected ?? new Date());

  const label = useMemo(() => {
    if (!selected) return "";
    if (mode === "month") return format(selected, "MMMM yyyy");
    if (mode === "week") return `Week ${format(selected, "II")} · ${format(selected, "RRRR")}`;
    return format(selected, "d MMM yyyy");
  }, [selected, mode]);

  const defaultPh = mode === "month" ? "Select month" : mode === "week" ? "Select week" : "Select date";

  function pick(d: Date) {
    if (mode === "week") onChange(format(startOfWeek(d, { weekStartsOn: 1 }), "RRRR-'W'II"));
    else if (mode === "month") onChange(`${d.getFullYear()}-${pad2(d.getMonth() + 1)}`);
    else onChange(format(d, "yyyy-MM-dd"));
    setOpen(false);
  }
  function pickToday() {
    const now = new Date();
    if (mode === "week") onChange(format(startOfWeek(now, { weekStartsOn: 1 }), "RRRR-'W'II"));
    else if (mode === "month") onChange(`${now.getFullYear()}-${pad2(now.getMonth() + 1)}`);
    else onChange(format(now, "yyyy-MM-dd"));
    setOpen(false);
  }

  const calDisabled = useMemo(() => {
    if (!minD && !maxD) return undefined;
    const m: { before: Date; after?: Date } | { before?: Date; after: Date } = minD && maxD
      ? { before: minD, after: maxD }
      : minD ? { before: minD } : { after: maxD! };
    return m;
  }, [minD, maxD]);

  return (
    <Popover open={open} onOpenChange={(o) => { setOpen(o); if (o) setView(selected ?? new Date()); }}>
      <PopoverTrigger asChild>
        <button
          type="button"
          disabled={disabled}
          aria-label={ariaLabel}
          data-slot="date-field"
          className={cn(
            "group/tf dark:bg-input/30 border-input flex h-9 w-full min-w-0 items-center gap-2 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs outline-none transition-[color,box-shadow] md:text-sm",
            "text-left whitespace-nowrap",
            "focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]",
            "disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50",
            className
          )}
        >
          <CalendarDays className="size-3.5 shrink-0 text-muted-foreground" />
          {label ? (
            <span className="truncate">{label}</span>
          ) : (
            <span className="truncate text-muted-foreground">{placeholder ?? defaultPh}</span>
          )}
          {value && !disabled ? (
            <span
              role="button"
              tabIndex={0}
              aria-label="Clear"
              onClick={(e) => { e.preventDefault(); e.stopPropagation(); onChange(null); }}
              onKeyDown={(e) => { if (e.key === "Enter" || e.key === " ") { e.preventDefault(); e.stopPropagation(); onChange(null); } }}
              className="ml-auto shrink-0 rounded-sm p-0.5 text-muted-foreground opacity-0 transition-opacity group-hover/tf:opacity-100 hover:bg-accent hover:text-foreground"
            >
              <X className="size-3" />
            </span>
          ) : null}
        </button>
      </PopoverTrigger>
      <PopoverContent align="start" className="w-auto p-0">
        {mode === "month" ? (
          <MonthGrid
            value={value}
            min={min}
            max={max}
            onPick={pick}
            onClear={() => { onChange(null); setOpen(false); }}
          />
        ) : (
          <>
            <Calendar
              mode="single"
              weekStartsOn={1}
              month={view}
              onMonthChange={setView}
              selected={selected ?? undefined}
              onSelect={(d) => { if (!d) { onChange(null); return; } pick(d); }}
              disabled={calDisabled}
            />
            <div className="flex items-center justify-between gap-2 border-t p-1.5">
              <Button variant="ghost" size="sm" className="h-7 rounded-md px-2.5 text-xs" onClick={pickToday}>
                {mode === "week" ? "This week" : "Today"}
              </Button>
              {value ? (
                <Button variant="ghost" size="sm" className="h-7 rounded-md px-2.5 text-xs text-muted-foreground" onClick={() => { onChange(null); setOpen(false); }}>
                  Clear
                </Button>
              ) : null}
            </div>
          </>
        )}
      </PopoverContent>
    </Popover>
  );
}

/* ── month grid (12-cell year board — native month input replacement) ── */
function MonthGrid({
  value, min, max, onPick, onClear,
}: {
  value?: string | null;
  min?: string;
  max?: string;
  onPick: (d: Date) => void;
  onClear: () => void;
}) {
  const sel = parseMonthValue(value);
  const [year, setYear] = useState(sel?.getFullYear() ?? new Date().getFullYear());
  const idx = (y: number, m: number) => y * 12 + m; // m: 0-based
  const minIdx = min ? (() => { const d = parseDateValue(min); return d ? idx(d.getFullYear(), d.getMonth()) : -Infinity; })() : -Infinity;
  const maxIdx = max ? (() => { const d = parseDateValue(max); return d ? idx(d.getFullYear(), d.getMonth()) : Infinity; })() : Infinity;
  const selIdx = sel ? idx(sel.getFullYear(), sel.getMonth()) : -1;
  const MONTHS = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
  const thisMonth = new Date();

  return (
    <div className="w-[268px] p-3">
      <div className="mb-2 flex items-center justify-between">
        <Button variant="ghost" size="icon" className="size-7" aria-label="Previous year" onClick={() => setYear((y) => y - 1)}>
          <ChevronLeft className="size-4" />
        </Button>
        <div className="text-sm font-medium tabular-nums">{year}</div>
        <Button variant="ghost" size="icon" className="size-7" aria-label="Next year" onClick={() => setYear((y) => y + 1)}>
          <ChevronRight className="size-4" />
        </Button>
      </div>
      <div className="grid grid-cols-3 gap-1.5">
        {MONTHS.map((name, m) => {
          const i = idx(year, m);
          const isSel = i === selIdx;
          const off = i < minIdx || i > maxIdx;
          return (
            <button
              key={m}
              type="button"
              disabled={off}
              onClick={() => onPick(new Date(year, m, 1))}
              className={cn(
                "h-8 rounded-md text-sm transition-colors",
                isSel ? "bg-primary text-primary-foreground" : "hover:bg-accent",
                off && "cursor-not-allowed opacity-40 hover:bg-transparent"
              )}
            >
              {name}
            </button>
          );
        })}
      </div>
      <div className="mt-2 flex items-center justify-between gap-2 border-t pt-2">
        <Button variant="ghost" size="sm" className="h-7 rounded-md px-2.5 text-xs" onClick={() => { onPick(new Date(thisMonth.getFullYear(), thisMonth.getMonth(), 1)); }}>
          This month
        </Button>
        {value ? (
          <Button variant="ghost" size="sm" className="h-7 rounded-md px-2.5 text-xs text-muted-foreground" onClick={onClear}>
            Clear
          </Button>
        ) : null}
      </div>
    </div>
  );
}

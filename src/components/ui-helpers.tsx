"use client";
import { ReactNode } from "react";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { STATUS_COLORS } from "@/lib/format";
import { cn } from "@/lib/utils";
import { Filter, Search, X } from "lucide-react";

export function StatCard({ icon, label, value, sub, tone = "default" }: { icon?: ReactNode; label: string; value: ReactNode; sub?: ReactNode; tone?: "default" | "primary" | "gold" | "danger" }) {
  const tones = {
    default: "text-foreground",
    primary: "text-primary",
    gold: "text-[#a97c0b] dark:text-[#e5b83b]",
    danger: "text-destructive",
  };
  return (
    <Card>
      <CardContent className="pt-5 pb-5">
        <div className="flex items-center justify-between">
          <span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</span>
          {icon && <span className="text-muted-foreground">{icon}</span>}
        </div>
        <div className={cn("mt-2 text-2xl font-bold tabular-nums", tones[tone])}>{value}</div>
        {sub && <div className="mt-1 text-xs text-muted-foreground">{sub}</div>}
      </CardContent>
    </Card>
  );
}

export function StatusBadge({ status }: { status: string }) {
  const label = status.replace(/_/g, " ").toLowerCase().replace(/\b\w/g, (c) => c.toUpperCase());
  return <Badge variant={(STATUS_COLORS[status] ?? "outline") as any} className="capitalize">{label}</Badge>;
}

export function SeverityBadge({ severity }: { severity: string }) {
  const map: Record<string, string> = {
    LOW: "bg-emerald-600 text-white",
    MEDIUM: "bg-amber-500 text-white",
    HIGH: "bg-orange-600 text-white",
    CRITICAL: "bg-red-600 text-white",
  };
  return <span className={cn("text-[11px] font-semibold px-2 py-0.5 rounded-full text-white", map[severity] ?? "bg-gray-500")}>{severity}</span>;
}

export function ModuleHeader({ title, description, actions }: { title: string; description?: string; actions?: ReactNode }) {
  return (
    <div className="flex flex-wrap items-start justify-between gap-3 mb-5">
      <div>
        <h1 className="text-xl font-bold tracking-tight">{title}</h1>
        {description && <p className="text-sm text-muted-foreground mt-0.5 max-w-2xl">{description}</p>}
      </div>
      {actions && <div className="flex items-center gap-2 flex-wrap">{actions}</div>}
    </div>
  );
}

export function LoadingBlock({ label = "Loading…" }: { label?: string }) {
  return (
    <div className="flex items-center justify-center gap-2 py-16 text-muted-foreground">
      <span className="size-4 rounded-full border-2 border-current border-t-transparent animate-spin" />
      {label}
    </div>
  );
}

export function EmptyState({ icon, title, hint }: { icon?: ReactNode; title: string; hint?: string }) {
  return (
    <div className="text-center py-12">
      <div className="inline-grid place-items-center size-12 rounded-full bg-secondary text-secondary-foreground mb-3">{icon}</div>
      <div className="font-medium">{title}</div>
      {hint && <div className="text-sm text-muted-foreground mt-1">{hint}</div>}
    </div>
  );
}

export function RiskPill({ level, score }: { level: string; score?: number }) {
  const map: Record<string, string> = {
    LOW: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
    MODERATE: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300",
    HIGH: "bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300",
    CRITICAL: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300",
  };
  return <span className={cn("text-[11px] font-semibold px-2 py-0.5 rounded-full", map[level] ?? "")}>{level}{score !== undefined ? ` · ${score}` : ""}</span>;
}

// ── Filtering toolkit ─────────────────────────────────────────
// A consistent filter strip for list pages: labelled selects, search,
// an "active" indicator, result count and a one-tap reset.

export function FilterSelect({ label, value, onChange, options, placeholder }: {
  label: string; value: string; onChange: (v: string) => void;
  options: { value: string; label: string }[]; placeholder?: string;
}) {
  return (
    <div className="flex items-center gap-1.5">
      <span className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground whitespace-nowrap">{label}</span>
      <Select value={value} onValueChange={onChange}>
        <SelectTrigger className="h-8 w-auto min-w-28 text-xs"><SelectValue placeholder={placeholder ?? "All"} /></SelectTrigger>
        <SelectContent>
          {options.map((o) => <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>)}
        </SelectContent>
      </Select>
    </div>
  );
}

export function FilterSearch({ value, onChange, placeholder = "Search…", className }: {
  value: string; onChange: (v: string) => void; placeholder?: string; className?: string;
}) {
  return (
    <div className={cn("relative", className)}>
      <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 text-muted-foreground" />
      <Input value={value} onChange={(e) => onChange(e.target.value)} placeholder={placeholder} className="h-8 pl-8 text-xs w-52" />
    </div>
  );
}

export function FilterBar({ children, active, onClear, count, total, actions, label = "Filters" }: {
  children: ReactNode;
  /** true when any filter/search is applied — shows the reset button */
  active: boolean;
  onClear: () => void;
  /** "showing" count of filtered rows */
  count?: number;
  /** total rows before filtering */
  total?: number;
  /** right-aligned action slot (export, print, primary button…) */
  actions?: ReactNode;
  label?: string;
}) {
  return (
    <div className="flex flex-wrap items-center gap-2 rounded-lg border bg-card px-3 py-2">
      <span className="flex items-center gap-1 text-xs font-medium text-muted-foreground">
        <Filter className="size-3.5" />
        <span className="hidden sm:inline">{label}</span>
      </span>
      <div className="flex flex-wrap items-center gap-2 flex-1">{children}</div>
      {count !== undefined && total !== undefined && (
        <Badge variant="secondary" className="text-[10px] tabular-nums whitespace-nowrap">
          {count === total ? `${total}` : `${count} of ${total}`}
        </Badge>
      )}
      {active && (
        <Button size="sm" variant="ghost" className="h-7 gap-1 text-xs" onClick={onClear}>
          <X className="size-3" /> Reset
        </Button>
      )}
      {actions && <div className="flex items-center gap-1.5">{actions}</div>}
    </div>
  );
}

/** Download rows as CSV (client-side). Accepts header + row arrays. */
export function downloadCsv(filename: string, header: string[], rows: (string | number | null | undefined)[][]) {
  const esc = (v: string | number | null | undefined) => {
    const s = v == null ? "" : String(v);
    return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
  };
  const csv = [header, ...rows].map((r) => r.map(esc).join(",")).join("\n");
  const url = URL.createObjectURL(new Blob([csv], { type: "text/csv;charset=utf-8" }));
  const a = document.createElement("a");
  a.href = url; a.download = filename; a.click();
  URL.revokeObjectURL(url);
}

// ── Form grid toolkit ─────────────────────────────────────────
// Consistent dialog forms: one column on phones, two from sm, three on
// desktop — fields carry a label, optional hint and optional column span.

export function FormGrid({ children, className, cols = 3 }: { children: ReactNode; className?: string; cols?: 2 | 3 }) {
  return (
    <div className={cn(
      "grid gap-3 sm:grid-cols-2",
      cols === 3 ? "lg:grid-cols-3" : "",
      className,
    )}>{children}</div>
  );
}

export function FormField({ label, required, hint, span, children }: {
  label: string; required?: boolean; hint?: string; span?: 1 | 2 | 3; children: ReactNode;
}) {
  return (
    <div className={cn(
      "space-y-1.5",
      span === 2 && "sm:col-span-2",
      span === 3 && "sm:col-span-2 lg:col-span-3",
    )}>
      <Label className="text-xs font-medium">
        {label}{required && <span className="text-destructive ml-0.5">*</span>}
      </Label>
      {children}
      {hint && <p className="text-[11px] leading-snug text-muted-foreground">{hint}</p>}
    </div>
  );
}

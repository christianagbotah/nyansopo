"use client";
import { ReactNode } from "react";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { STATUS_COLORS } from "@/lib/format";
import { cn } from "@/lib/utils";

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

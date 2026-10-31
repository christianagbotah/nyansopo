"use client";
import { cn } from "@/lib/utils";

export function LogoMark({ className, size = 36 }: { className?: string; size?: number }) {
  return (
    <svg width={size} height={size} viewBox="0 0 48 48" fill="none" className={className} aria-hidden>
      <rect width="48" height="48" rx="10" fill="#0e5f3f" />
      <path d="M24 6 C 11 13, 11 35, 24 42 C 37 35, 37 13, 24 6 Z" stroke="#d9a521" strokeWidth="2.6" strokeLinecap="round" />
      <path d="M24 13 C 16.5 18.5, 16.5 29.5, 24 35 C 31.5 29.5, 31.5 18.5, 24 13 Z" stroke="#f5e6c4" strokeWidth="2.2" strokeLinecap="round" />
      <circle cx="24" cy="24" r="3.2" fill="#d9a521" />
    </svg>
  );
}

export function BrandWord({ className, compact = false }: { className?: string; compact?: boolean }) {
  return (
    <span className={cn("inline-flex items-center gap-2.5", className)}>
      <LogoMark size={compact ? 28 : 34} />
      <span className="leading-none">
        <span className="block font-bold tracking-tight text-xl">Nyansapo</span>
        {!compact && <span className="block text-[10px] uppercase tracking-[0.22em] text-muted-foreground mt-1">Wisdom · Knot · Cloud</span>}
      </span>
    </span>
  );
}

export function KenteStrip({ className }: { className?: string }) {
  return <div className={cn("kente-strip h-1.5 w-full", className)} aria-hidden />;
}

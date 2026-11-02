"use client";
// ── Notification bell — role-scoped, server-derived, click-to-deep-link ──
// Polls /api/notifications for the current school; unread state is a per-user
// "last read" timestamp in localStorage (server stays stateless). Every item
// is permission-gated server-side, so the bell only ever offers actions the
// signed-in role can actually perform.
import { useCallback, useEffect, useRef, useState } from "react";
import { Bell, Package, Percent, Banknote, NotebookPen, ShieldCheck, Inbox } from "lucide-react";
import { useStore } from "@/lib/store";
import { apiGet } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { cn } from "@/lib/utils";

type NotifItem = {
  id: string; kind: string; severity: "info" | "warning" | "danger";
  title: string; detail?: string; count: number;
  module?: string; tab?: string; at?: string;
};

const KIND_ICON: Record<string, any> = {
  approvals: ShieldCheck, discounts: Percent, records: Inbox,
  notes: NotebookPen, stock: Package, arrears: Banknote,
};
const SEV_DOT: Record<string, string> = {
  danger: "bg-red-500", warning: "bg-amber-500", info: "bg-sky-500",
};

function relTime(iso?: string): string {
  if (!iso) return "";
  const s = Math.max(0, (Date.now() - new Date(iso).getTime()) / 1000);
  if (s < 60) return "just now";
  if (s < 3600) return `${Math.floor(s / 60)} min ago`;
  if (s < 86400) return `${Math.floor(s / 3600)} h ago`;
  return `${Math.floor(s / 86400)} d ago`;
}

export function NotificationBell() {
  const { session, currentSchoolId, setModule } = useStore();
  const [items, setItems] = useState<NotifItem[]>([]);
  const [open, setOpen] = useState(false);
  const [lastRead, setLastRead] = useState<number>(() => {
    // read lazily — localStorage isn't available during SSR
    if (typeof window === "undefined" || !session?.id) return 0;
    return Number(localStorage.getItem(`nyansapo-notif-read-${session.id}`) ?? 0);
  });
  const seenSchool = useRef<string | null>(null);

  const load = useCallback(() => {
    if (!currentSchoolId || !session) return;
    apiGet<{ items: NotifItem[] }>(`/api/notifications?schoolId=${currentSchoolId}`)
      .then((r) => setItems(r.items ?? []))
      .catch(() => {/* offline / boot races — the bell stays quiet */});
  }, [currentSchoolId, session?.id]);

  useEffect(() => { load(); }, [load]);
  useEffect(() => {
    const t = setInterval(load, 60_000);
    const onFocus = () => load();
    window.addEventListener("focus", onFocus);
    return () => { clearInterval(t); window.removeEventListener("focus", onFocus); };
  }, [load]);
  // switching school re-arms the unread baseline so old counts don't stick
  useEffect(() => {
    if (currentSchoolId !== seenSchool.current) {
      seenSchool.current = currentSchoolId;
      if (typeof window !== "undefined" && session?.id) {
        setLastRead(Number(localStorage.getItem(`nyansapo-notif-read-${session.id}`) ?? 0));
      }
    }
  }, [currentSchoolId, session?.id]);

  const unread = items.filter((i) => (i.at ? new Date(i.at).getTime() : Date.now()) > lastRead).length;

  const markAll = () => {
    if (!session?.id) return;
    const now = Date.now();
    localStorage.setItem(`nyansapo-notif-read-${session.id}`, String(now));
    setLastRead(now);
  };

  const openItem = (i: NotifItem) => {
    markAll();
    if (i.module) setModule(i.module as any, i.tab ? { tab: i.tab } : undefined);
    setOpen(false);
  };

  // association officers and portal-only accounts have no school bell
  if (!session || !currentSchoolId) return null;

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button size="sm" variant="ghost" className="relative size-8 p-0" title="Notifications" aria-label={`Notifications${unread ? ` — ${unread} unread` : ""}`}>
          <Bell className="size-4.5" />
          {unread > 0 && (
            <span className={cn(
              "absolute -top-0.5 -right-0.5 min-w-4 h-4 px-1 rounded-full text-[10px] font-bold text-white flex items-center justify-center",
              items.some((i) => i.severity === "danger") ? "bg-red-600 pulse-dot" : "bg-red-500",
            )}>
              {unread > 9 ? "9+" : unread}
            </span>
          )}
        </Button>
      </PopoverTrigger>
      <PopoverContent align="end" className="w-88 max-w-[92vw] p-0">
        <div className="flex items-center justify-between px-4 py-3 border-b border-border">
          <div className="text-sm font-semibold">Notifications</div>
          {unread > 0 && (
            <Button size="sm" variant="ghost" className="h-7 text-xs text-muted-foreground" onClick={markAll}>
              Mark all read
            </Button>
          )}
        </div>
        <div className="max-h-96 overflow-y-auto nice-scroll">
          {items.length === 0 ? (
            <div className="px-4 py-10 text-center text-sm text-muted-foreground">
              <Inbox className="size-8 mx-auto mb-2 opacity-40" />
              You're all caught up — nothing needs your attention.
            </div>
          ) : (
            items.map((i) => {
              const Icon = KIND_ICON[i.kind] ?? Bell;
              const isUnread = (i.at ? new Date(i.at).getTime() : Date.now()) > lastRead;
              return (
                <button
                  key={i.id}
                  onClick={() => openItem(i)}
                  className={cn(
                    "w-full text-left px-4 py-3 flex gap-3 border-b border-border/60 last:border-b-0 transition-colors hover:bg-secondary/60",
                    isUnread && "bg-primary/[0.04]",
                  )}
                >
                  <div className={cn("mt-0.5 shrink-0 rounded-md p-1.5 h-fit", i.severity === "danger" ? "bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300" : i.severity === "warning" ? "bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300" : "bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300")}>
                    <Icon className="size-4" />
                  </div>
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <span className={cn("text-sm font-medium leading-snug", isUnread && "font-semibold")}>{i.title}</span>
                      <span className={cn("size-1.5 rounded-full shrink-0", SEV_DOT[i.severity], !isUnread && "opacity-30")} />
                    </div>
                    {i.detail && <div className="text-xs text-muted-foreground mt-0.5 line-clamp-2">{i.detail}</div>}
                    <div className="text-[10px] text-muted-foreground/70 mt-1">{relTime(i.at)}{i.tab ? ` · opens ${i.module} › ${i.tab}` : i.module ? ` · opens ${i.module}` : ""}</div>
                  </div>
                </button>
              );
            })
          )}
        </div>
      </PopoverContent>
    </Popover>
  );
}

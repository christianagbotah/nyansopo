"use client";
// ── Ctrl+K / ⌘K command palette ─────────────────────────────────────────
// Fuzzy-jumps across every module, child tab and class deep-link the current
// session may open (the same permission-gated nav tree from the sidebar),
// plus quick actions: sync queue, toggle theme, AI assistant, sign out.
import { useEffect, useMemo, useState } from "react";
import {
  CommandDialog, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList, CommandSeparator,
} from "@/components/ui/command";
import { RefreshCcw, Moon, Sun, LogOut, Search } from "lucide-react";
import { useStore, type ModuleId, type NavTarget } from "@/lib/store";
import { flushQueue } from "@/lib/api";
import { toast } from "@/hooks/use-toast";

type PaletteNavItem = { id: string; label: string; icon: any; children?: { id: string; label: string; target?: NavTarget; children?: { label: string; target?: NavTarget }[] }[] };

export function CommandPalette({
  items, open, setOpen, onToggleTheme, theme,
}: {
  items: PaletteNavItem[];
  open: boolean;
  setOpen: React.Dispatch<React.SetStateAction<boolean>>;
  onToggleTheme: () => void;
  theme: string;
}) {
  const { setModule, logout, queue, session } = useStore();

  // global shortcut
  useEffect(() => {
    const down = (e: KeyboardEvent) => {
      if ((e.key === "k" || e.key === "K") && (e.metaKey || e.ctrlKey)) {
        e.preventDefault();
        setOpen((v) => !v);
      }
    };
    document.addEventListener("keydown", down);
    return () => document.removeEventListener("keydown", down);
  }, [setOpen]);

  const run = (fn: () => void) => { setOpen(false); fn(); };
  const go = (m: string, t?: NavTarget) => run(() => setModule(m as ModuleId, t));

  // flatten class deep-links (students/classes grandchildren) once
  const classLinks = useMemo(() => {
    const out: { label: string; module: string; target?: NavTarget }[] = [];
    for (const item of items) {
      for (const child of item.children ?? []) {
        for (const g of child.children ?? []) {
          if (g.target?.classroomId) out.push({ label: `${g.label} · ${item.label}`, module: item.id, target: g.target });
        }
      }
    }
    return out;
  }, [items]);

  return (
    <CommandDialog open={open} onOpenChange={setOpen} className="max-w-xl">
      <CommandInput placeholder="Search modules, classes, actions…" />
      <CommandList>
        <CommandEmpty>No matches — try another word.</CommandEmpty>

        <CommandGroup heading="Modules">
          {items.map((item) => (
            <CommandItem key={item.id} value={`${item.label} ${item.id}`} onSelect={() => go(item.id)}>
              <item.icon className="size-4 text-muted-foreground" />
              <span>{item.label}</span>
            </CommandItem>
          ))}
        </CommandGroup>

        {classLinks.length > 0 && (
          <>
            <CommandSeparator />
            <CommandGroup heading="Jump to class">
              {classLinks.map((c) => (
                <CommandItem key={`${c.module}-${c.target?.classroomId}`} value={`${c.label} class`} onSelect={() => go(c.module, c.target)}>
                  <Search className="size-4 text-muted-foreground" />
                  <span>{c.label}</span>
                </CommandItem>
              ))}
            </CommandGroup>
          </>
        )}

        <>
          <CommandSeparator />
          <CommandGroup heading="Actions">
            {queue.length > 0 && (
              <CommandItem value="sync offline queue" onSelect={() => run(async () => {
                const res = await flushQueue();
                if (res.synced) toast({ title: `Synced ${res.synced} action${res.synced > 1 ? "s" : ""}` });
                if (res.failed) toast({ title: `${res.failed} failed`, description: res.messages.join(" · "), variant: "destructive" });
              })}>
                <RefreshCcw className="size-4 text-muted-foreground" />
                <span>Sync offline queue ({queue.length})</span>
              </CommandItem>
            )}
            <CommandItem value="toggle theme dark light" onSelect={() => run(onToggleTheme)}>
              {theme === "light" ? <Moon className="size-4 text-muted-foreground" /> : <Sun className="size-4 text-muted-foreground" />}
              <span>Switch to {theme === "light" ? "dark" : "light"} theme</span>
            </CommandItem>
            <CommandItem value="sign out logout" onSelect={() => run(() => { logout(); toast({ title: "Signed out" }); })}>
              <LogOut className="size-4 text-muted-foreground" />
              <span>Sign out</span>
            </CommandItem>
          </CommandGroup>
        </>
      </CommandList>
    </CommandDialog>
  );
}

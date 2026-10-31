"use client";
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore, hasSchoolScope, isAssociationRole, ROLE_LABELS, ModuleId } from "@/lib/store";
import { apiGet } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import {
  DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Sheet, SheetContent, SheetTitle, SheetTrigger } from "@/components/ui/sheet";
import { LogoMark, KenteStrip } from "@/components/brand";
import { cn } from "@/lib/utils";
import { initials } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { flushQueue } from "@/lib/api";
import { Dashboard } from "@/components/modules/dashboard";
import { Students } from "@/components/modules/students";
import { Admissions } from "@/components/modules/admissions";
import { Fees } from "@/components/modules/fees";
import { Attendance } from "@/components/modules/attendance";
import { Gradebook } from "@/components/modules/gradebook";
import { Classes } from "@/components/modules/classes";
import { Reports } from "@/components/modules/reports";
import { BranchConsole } from "@/components/modules/branch-console";
import { AiCenter } from "@/components/modules/ai-center";
import { AssociationPortal } from "@/components/modules/association-portal";
import { Settings } from "@/components/modules/settings";
import { DailyServices } from "@/components/modules/daily-services";
import { Transportation } from "@/components/modules/transport";
import { SmsCenter } from "@/components/modules/sms-center";
import { Payroll } from "@/components/modules/payroll";
import { Inventory } from "@/components/modules/inventory";
import { Finance } from "@/components/modules/finance";
import {
  LayoutDashboard, Users, Banknote, CalendarCheck, ClipboardList, GraduationCap, ArrowLeftRight, Building2,
  BrainCircuit, Settings as SettingsIcon, Landmark, Menu, CloudOff, Cloud, RefreshCcw, LogOut, Moon, Sun,
  Download, ChevronDown, School, MessageSquareText, UtensilsCrossed, Bus, Smartphone, Calculator, Package, PieChart, LayoutList,
} from "lucide-react";

type NavItem = { id: ModuleId; label: string; icon: any; section?: string };

const SCHOOL_NAV: NavItem[] = [
  { id: "dashboard", label: "Dashboard", icon: LayoutDashboard, section: "Overview" },
  { id: "students", label: "Students", icon: Users, section: "People" },
  { id: "admissions", label: "Admissions & Transfers", icon: ArrowLeftRight, section: "People" },
  { id: "fees", label: "Fees & Billing", icon: Banknote, section: "Finance" },
  { id: "daily", label: "Daily Services & Tuck", icon: UtensilsCrossed, section: "Finance" },
  { id: "transport", label: "Transportation", icon: Bus, section: "Finance" },
  { id: "payroll", label: "HR & Payroll", icon: Calculator, section: "Finance" },
  { id: "inventory", label: "Inventory & Stores", icon: Package, section: "Finance" },
  { id: "finance", label: "Finance & Analytics", icon: PieChart, section: "Finance" },
  { id: "attendance", label: "Attendance", icon: CalendarCheck, section: "Academic" },
  { id: "classes", label: "Classes & Timetable", icon: LayoutList, section: "Academic" },
  { id: "gradebook", label: "Gradebook", icon: ClipboardList, section: "Academic" },
  { id: "reports", label: "Exams & Reports", icon: GraduationCap, section: "Academic" },
  { id: "sms", label: "SMS Centre", icon: Smartphone, section: "Communication" },
  { id: "ai", label: "AI Assistant", icon: BrainCircuit, section: "Support" },
  { id: "settings", label: "Settings", icon: SettingsIcon, section: "Support" },
];

function NavList({ items, current, onSelect }: { items: NavItem[]; current: ModuleId; onSelect: (m: ModuleId) => void }) {
  // group consecutive items by section
  const groups: { section?: string; items: NavItem[] }[] = [];
  for (const item of items) {
    const last = groups[groups.length - 1];
    if (last && last.section === item.section) last.items.push(item);
    else groups.push({ section: item.section, items: [item] });
  }
  return (
    <nav className="flex-1 space-y-1 px-3 py-4 overflow-y-auto nice-scroll">
      {groups.map((g, gi) => (
        <div key={gi}>
          {g.section && (
            <div className="pt-3 pb-1.5 px-3 text-[10px] font-semibold uppercase tracking-widest text-sidebar-foreground/40">{g.section}</div>
          )}
          {g.items.map((item) => (
            <button
              key={item.id}
              onClick={() => onSelect(item.id)}
              className={cn(
                "w-full flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors text-left",
                current === item.id ? "bg-sidebar-accent text-sidebar-accent-foreground shadow-sm" : "text-sidebar-foreground/70 hover:bg-sidebar-accent/60 hover:text-sidebar-accent-foreground"
              )}
            >
              <item.icon className="size-4.5 shrink-0" />
              <span className="truncate">{item.label}</span>
              {item.id === "admissions" && <Badge className="ml-auto bg-red-600 text-[10px] px-1.5">demo</Badge>}
            </button>
          ))}
        </div>
      ))}
    </nav>
  );
}

export function AppShell() {
  const { session, schools, currentSchoolId, setCurrentSchool, module, setModule, online, queue, syncing, logout, aiOpen, setAiOpen } = useStore();
  const [theme, setTheme] = useState<"light" | "dark">(() => {
    if (typeof window === "undefined") return "light";
    return ((localStorage.getItem("theme") as "light" | "dark") ?? "light");
  });
  const [installEvent, setInstallEvent] = useState<any>(null);
  const [navOpen, setNavOpen] = useState(false);

  const currentSchool = useMemo(() => schools.find((s) => s.id === currentSchoolId) ?? null, [schools, currentSchoolId]);
  const schoolScope = hasSchoolScope(session?.role);
  const assocScope = isAssociationRole(session?.role);
  const isGroupOwner = session?.role === "GROUP_OWNER";

  useEffect(() => {
    document.documentElement.classList.toggle("dark", theme === "dark");
    localStorage.setItem("theme", theme);
  }, [theme]);

  useEffect(() => {
    const handler = (e: any) => { e.preventDefault(); setInstallEvent(e); };
    window.addEventListener("beforeinstallprompt", handler);
    return () => window.removeEventListener("beforeinstallprompt", handler);
  }, []);

  function toggleTheme() {
    setTheme(theme === "light" ? "dark" : "light");
  }

  const navItems = useMemo(() => {
    const items: NavItem[] = [];
    if (assocScope) items.push({ id: "association", label: "Association Portal", icon: Landmark });
    if (schoolScope) {
      items.push(...SCHOOL_NAV);
      if (isGroupOwner) {
        // insert branch console after reports
        const idx = items.findIndex((i) => i.id === "reports");
        items.splice(idx + 1, 0, { id: "branches", label: "Branch Console", icon: Building2 });
      }
    }
    return items;
  }, [assocScope, schoolScope, isGroupOwner]);

  async function manualSync() {
    if (!queue.length) { toast({ title: "Nothing to sync", description: "Your queue is empty." }); return; }
    const res = await flushQueue();
    if (res.synced) toast({ title: `Synced ${res.synced} action${res.synced > 1 ? "s" : ""}` });
    if (res.failed) toast({ title: `${res.failed} failed`, description: res.messages.join(" · "), variant: "destructive" });
  }

  return (
    <div className="min-h-screen flex flex-col bg-background">
      {/* ── top bar ── */}
      <header className="sticky top-0 z-40 bg-card border-b border-border">
        <div className="h-14 px-3 sm:px-5 flex items-center gap-3">
          {/* mobile nav */}
          <Sheet open={navOpen} onOpenChange={setNavOpen}>
            <SheetTrigger asChild>
              <Button variant="ghost" size="icon" className="lg:hidden"><Menu className="size-5" /></Button>
            </SheetTrigger>
            <SheetContent side="left" className="p-0 w-72 bg-sidebar text-sidebar-foreground flex flex-col border-r-0">
              <SheetTitle className="sr-only">Navigation</SheetTitle>
              <div className="p-4 flex items-center gap-2.5 border-b border-sidebar-border">
                <LogoMark size={30} />
                <div>
                  <div className="font-bold leading-none">Nyansapo</div>
                  <div className="text-[10px] uppercase tracking-widest text-sidebar-foreground/60 mt-1">{ROLE_LABELS[session?.role ?? ""] ?? "Staff"}</div>
                </div>
              </div>
              <NavList items={navItems} current={module} onSelect={(m) => { setModule(m); setNavOpen(false); }} />
              <div className="p-3 border-t border-sidebar-border text-xs text-sidebar-foreground/60">
                {online ? <span className="flex items-center gap-1.5"><Cloud className="size-3.5 text-emerald-400" /> Online</span> : <span className="flex items-center gap-1.5"><CloudOff className="size-3.5 text-amber-400" /> Offline — {queue.length} queued</span>}
              </div>
            </SheetContent>
          </Sheet>

          {/* school / scope switcher */}
          <div className="flex items-center gap-2 min-w-0">
            <School className="size-4.5 text-muted-foreground hidden sm:block shrink-0" />
            {schools.length > 1 ? (
              <Select value={currentSchoolId ?? schools[0]?.id ?? ""} onValueChange={setCurrentSchool}>
                <SelectTrigger className="h-9 w-auto max-w-[260px] gap-1.5 font-medium">
                  <SelectValue placeholder="Select school" />
                </SelectTrigger>
                <SelectContent>
                  {schools.map((s) => (
                    <SelectItem key={s.id} value={s.id}>
                      <span className="flex items-center gap-2">
                        <span className="size-2 rounded-full shrink-0" style={{ background: s.logoColor }} />
                        <span className="truncate">{s.name}</span>
                        {s.isBranchMain && <Badge variant="secondary" className="text-[9px] px-1">MAIN</Badge>}
                      </span>
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            ) : assocScope ? (
              <div className="text-sm font-medium truncate">{session?.title ?? "Association Portal"}</div>
            ) : (
              currentSchool && (
                <div className="flex items-center gap-2 min-w-0">
                  <span className="size-2.5 rounded-full shrink-0" style={{ background: currentSchool.logoColor }} />
                  <span className="text-sm font-medium truncate">{currentSchool.name}</span>
                  <Badge variant="outline" className="hidden md:inline-flex shrink-0">{currentSchool.curriculum}</Badge>
                </div>
              )
            )}
          </div>

          <div className="ml-auto flex items-center gap-1.5">
            {/* offline / sync */}
            {online ? (
              queue.length > 0 ? (
                <Button size="sm" variant="outline" className="gap-1.5 h-8" onClick={manualSync} disabled={syncing}>
                  <RefreshCcw className={cn("size-3.5", syncing && "animate-spin")} /> Sync {queue.length}
                </Button>
              ) : (
                <Badge variant="secondary" className="hidden sm:inline-flex gap-1.5 text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950 border border-emerald-200 dark:border-emerald-800">
                  <span className="size-1.5 rounded-full bg-emerald-500 pulse-dot" /> Online
                </Badge>
              )
            ) : (
              <Badge className="gap-1.5 bg-amber-500 hover:bg-amber-500 text-white">
                <CloudOff className="size-3.5" /> Offline{queue.length ? ` · ${queue.length} queued` : ""}
              </Badge>
            )}

            {/* install PWA */}
            {installEvent && (
              <Button size="sm" variant="outline" className="gap-1.5 h-8" onClick={() => { installEvent.prompt(); setInstallEvent(null); }}>
                <Download className="size-3.5" /> Install app
              </Button>
            )}

            {/* AI quick open */}
            {schoolScope && (
              <Button size="sm" variant="ghost" className="size-8 p-0" title="AI Assistant" onClick={() => setModule("ai")}>
                <MessageSquareText className="size-4.5" />
              </Button>
            )}

            {/* theme */}
            <Button size="sm" variant="ghost" className="size-8 p-0" onClick={toggleTheme} title="Toggle theme">
              {theme === "light" ? <Moon className="size-4.5" /> : <Sun className="size-4.5" />}
            </Button>

            {/* user */}
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <button className="flex items-center gap-2 rounded-full pl-1 pr-2 py-1 hover:bg-secondary/70 transition-colors">
                  <Avatar className="size-8">
                    <AvatarFallback className="bg-primary text-primary-foreground text-xs font-semibold">{initials(session?.fullName ?? "?")}</AvatarFallback>
                  </Avatar>
                  <ChevronDown className="size-3.5 text-muted-foreground hidden sm:block" />
                </button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="w-64">
                <DropdownMenuLabel>
                  <div className="font-semibold">{session?.fullName}</div>
                  <div className="text-xs text-muted-foreground font-normal">{session?.title ?? ROLE_LABELS[session?.role ?? ""]}</div>
                  <div className="text-xs text-muted-foreground font-normal">{session?.email}</div>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem onClick={() => { logout(); toast({ title: "Signed out", description: "Medaase — see you soon." }); }}>
                  <LogOut className="size-4" /> Sign out
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>
        <KenteStrip />
      </header>

      {/* ── body ── */}
      <div className="flex-1 flex min-h-0">
        {/* desktop sidebar */}
        <aside className="hidden lg:flex w-64 flex-col bg-sidebar text-sidebar-foreground sticky top-[61px] self-start h-[calc(100vh-61px)]">
          <div className="p-4 flex items-center gap-2.5 border-b border-sidebar-border">
            <LogoMark size={30} />
            <div>
              <div className="font-bold leading-none text-sidebar-foreground">Nyansapo</div>
              <div className="text-[10px] uppercase tracking-widest text-sidebar-foreground/60 mt-1">{ROLE_LABELS[session?.role ?? ""] ?? "Staff"}</div>
            </div>
          </div>
          <NavList items={navItems} current={module} onSelect={setModule} />
          <div className="p-3 border-t border-sidebar-border text-[11px] text-sidebar-foreground/60 leading-relaxed">
            {currentSchool ? (
              <div>
                <div className="font-medium text-sidebar-foreground/80">{currentSchool.town}, {currentSchool.region}</div>
                <div>{currentSchool.studentsOnRoll} learners · {currentSchool.curriculum} pathway</div>
              </div>
            ) : assocScope ? (
              <div>{session?.title}</div>
            ) : null}
          </div>
        </aside>

        {/* module content */}
        <main className="flex-1 min-w-0 p-4 sm:p-6">
          <div className="mx-auto max-w-7xl">
            {module === "association" && assocScope ? <AssociationPortal /> :
             module === "students" && schoolScope ? <Students /> :
             module === "admissions" && schoolScope ? <Admissions /> :
             module === "fees" && schoolScope ? <Fees /> :
             module === "daily" && schoolScope ? <DailyServices /> :
             module === "transport" && schoolScope ? <Transportation /> :
             module === "sms" && schoolScope ? <SmsCenter /> :
             module === "payroll" && schoolScope ? <Payroll /> :
             module === "inventory" && schoolScope ? <Inventory /> :
             module === "finance" && schoolScope ? <Finance /> :
             module === "attendance" && schoolScope ? <Attendance /> :
             module === "classes" && schoolScope ? <Classes /> :
             module === "gradebook" && schoolScope ? <Gradebook /> :
             module === "reports" && schoolScope ? <Reports /> :
             module === "branches" && schoolScope ? <BranchConsole /> :
             module === "ai" && schoolScope ? <AiCenter /> :
             module === "settings" && schoolScope ? <Settings /> :
             schoolScope ? <Dashboard /> :
             assocScope ? <AssociationPortal /> : <Dashboard />}
          </div>
        </main>
      </div>
    </div>
  );
}

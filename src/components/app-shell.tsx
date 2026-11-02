"use client";
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore, hasSchoolScope, isAssociationRole, ROLE_LABELS, ModuleId, NavTarget } from "@/lib/store";
import { canModule, canAny, ROLE_LEVELS, ROLE_LEVEL_LABELS } from "@/lib/rbac";
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
import { Curriculum } from "@/components/modules/curriculum";
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
import { LibraryPortal } from "@/components/modules/library-portal";
import { OnlineExams } from "@/components/modules/online-exams";
import { RbacConsole } from "@/components/modules/rbac-console";
import { CashierConsole } from "@/components/modules/cashier-console";
import { ConductorPortal } from "@/components/modules/conductor-portal";
import { StaffPortal } from "@/components/modules/staff-portal";
import { ParentPortal } from "@/components/modules/parent-portal";
import { StudentPortal } from "@/components/modules/student-portal";
import { Boarding } from "@/components/modules/boarding";
import {
  LayoutDashboard, Users, Banknote, CalendarCheck, ClipboardList, GraduationCap, ArrowLeftRight, Building2,
  BrainCircuit, Settings as SettingsIcon, Landmark, Menu, CloudOff, Cloud, RefreshCcw, LogOut, Moon, Sun,
  Download, ChevronDown, ChevronRight, School, MessageSquareText, UtensilsCrossed, Bus, Smartphone, Calculator, Package, PieChart, LayoutList,
  BookOpen, ShieldCheck, Receipt, Route, UserRound, Home, Backpack, Laptop, BedDouble, NotebookPen,
} from "lucide-react";

type NavLeaf = { id: string; label: string; icon?: any; target?: NavTarget };
type NavChild = NavLeaf & { children?: NavLeaf[] };
type NavItem = { id: ModuleId; label: string; icon: any; section?: string; children?: NavChild[] };

/* permission-based navigation (RBAC): each module is gated by the session's
   effective permissions — role defaults plus any per-user overrides from the
   Users & Access console. iEduPro admin levels 1–5 map onto the same rules. */
const SCHOOL_NAV: NavItem[] = [
  { id: "dashboard", label: "Dashboard", icon: LayoutDashboard, section: "Overview" },
  { id: "cashier", label: "Cashier Console", icon: Receipt, section: "Overview" },
  { id: "conductor", label: "Conductor Portal", icon: Route, section: "Overview" },
  { id: "students", label: "Students", icon: Users, section: "People" },
  { id: "admissions", label: "Admissions & Transfers", icon: ArrowLeftRight, section: "People" },
  { id: "fees", label: "Fees & Billing", icon: Banknote, section: "Finance" },
  { id: "daily", label: "Daily Services & Tuck", icon: UtensilsCrossed, section: "Finance" },
  { id: "transport", label: "Transportation", icon: Bus, section: "Finance" },
  { id: "boarding", label: "Boarding Houses", icon: BedDouble, section: "Facilities" },
  { id: "payroll", label: "HR & Payroll", icon: Calculator, section: "Finance" },
  { id: "inventory", label: "Inventory & Stores", icon: Package, section: "Finance" },
  { id: "finance", label: "Finance & Analytics", icon: PieChart, section: "Finance" },
  { id: "attendance", label: "Attendance", icon: CalendarCheck, section: "Academic" },
  { id: "classes", label: "Classes & Timetable", icon: LayoutList, section: "Academic" },
  { id: "gradebook", label: "Gradebook", icon: ClipboardList, section: "Academic" },
  { id: "curriculum", label: "Curriculum & Notes", icon: NotebookPen, section: "Academic" },
  { id: "reports", label: "Exams & Reports", icon: GraduationCap, section: "Academic" },
  { id: "exams", label: "Online Exams", icon: Laptop, section: "Academic" },
  { id: "library", label: "Library", icon: BookOpen, section: "Academic" },
  { id: "sms", label: "SMS Centre", icon: Smartphone, section: "Communication" },
  { id: "ai", label: "AI Assistant", icon: BrainCircuit, section: "Support" },
  { id: "staffhub", label: "My Staff Portal", icon: UserRound, section: "Support" },
  { id: "settings", label: "Settings", icon: SettingsIcon, section: "Support" },
  { id: "rbac", label: "Users & Access", icon: ShieldCheck, section: "Support" },
];

const PORTAL_NAV: NavItem[] = [
  { id: "portal", label: "Parent Portal", icon: Home, section: "My Children" },
  { id: "campus", label: "My Learning", icon: Backpack, section: "My Record" },
];

/* iEduPro level bands — classroom gradeLevel → the CI3 class groups
   (CRECHE / NURSERY / KG / BASIC / JHS …), in the canonical order. */
type LevelBand = { key: string; label: string; match: (grade: string, stage?: string | null) => boolean };
const LEVEL_BANDS: LevelBand[] = [
  { key: "CRECHE", label: "Creche & Nursery", match: (g) => /^(CRECHE|NURSERY|CR|PLAY)/i.test(g) || /^B0/.test(g) },
  { key: "KG", label: "Kindergarten (KG)", match: (g, s) => /KG/i.test(g) || /kindergarten/i.test(s ?? "") },
  { key: "PRIMARY", label: "Primary (B1–B6)", match: (g) => /^B[1-6]$/.test(g.trim()) || /^YEAR [1-6]$|^Y[1-6]$/i.test(g.trim()) || /^PYP [1-6]$/i.test(g.trim()) },
  { key: "JHS", label: "Junior High (B7–B9)", match: (g) => /^B[7-9]$/.test(g.trim()) || /^JHS/i.test(g) },
  { key: "SENIOR", label: "Senior (Y10+)", match: (g) => /^YEAR (1[0-3])$|^Y1[0-3]$/i.test(g.trim()) || /MYP [4-5]/i.test(g.trim()) },
];
const bandOf = (grade: string, stage?: string | null): LevelBand =>
  LEVEL_BANDS.find((b) => b.match(grade, stage)) ?? { key: "OTHER", label: "Other classes", match: () => false };

function levelGroups(classrooms: { id: string; name: string; grade: string; stage?: string | null }[]): { band: string; label: string; classes: { id: string; name: string }[] }[] {
  const byBand = new Map<string, { label: string; classes: { id: string; name: string }[] }>();
  for (const c of [...classrooms].sort((a, b) => a.name.localeCompare(b.name, undefined, { numeric: true }))) {
    const band = bandOf(c.grade, c.stage);
    const e = byBand.get(band.key) ?? { label: band.label, classes: [] };
    e.classes.push({ id: c.id, name: c.name });
    byBand.set(band.key, e);
  }
  // canonical CI3 order: Creche → KG → Primary → JHS → Senior → other
  const rank = (key: string) => (LEVEL_BANDS.findIndex((b) => b.key === key) + 1) || (LEVEL_BANDS.length + 1);
  return [...byBand.entries()]
    .sort((a, b) => rank(a[0]) - rank(b[0]))
    .map(([band, v]) => ({ band, label: v.label, classes: v.classes }));
}

function NavList({ items, current, target, onSelect }: { items: NavItem[]; current: ModuleId; target: NavTarget; onSelect: (m: ModuleId, t?: NavTarget) => void }) {
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
            <NavNode key={item.id} item={item} current={current} target={target} onSelect={onSelect} />
          ))}
        </div>
      ))}
    </nav>
  );
}

/* one top-level entry — renders its children (level 2) and grandchildren
   (level 3) as collapsible branches, iEduPro navigation.php style:
   parent → child menu → per-class grandchild links */
function NavNode({ item, current, target, onSelect }: { item: NavItem; current: ModuleId; target: NavTarget; onSelect: (m: ModuleId, t?: NavTarget) => void }) {
  /* tri-state open: null = follow the active branch (auto-expand on deep
     links), true/false = the user's explicit choice */
  const [open, setOpen] = useState<boolean | null>(null);
  const branchContainsActive = !!item.children?.some(
    (c) => (current === item.id && (!c.target || (!c.target.tab && !c.target.classroomId))) ||
      (current === item.id && c.target && ((c.target.tab && target?.tab === c.target.tab) || (c.target.classroomId && target?.classroomId === c.target.classroomId))) ||
      c.children?.some((g) => current === item.id && g.target?.classroomId && target?.classroomId === g.target.classroomId),
  );
  const expanded = open ?? branchContainsActive;
  const isPlainLink = !item.children?.length;
  return (
    <div>
      <button
        onClick={() => (isPlainLink ? onSelect(item.id) : setOpen(!expanded))}
        aria-expanded={isPlainLink ? undefined : expanded}
        className={cn(
          "w-full flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors text-left",
          current === item.id && (!target || !item.children?.some((c) => c.target?.tab || c.target?.classroomId) || branchContainsActive === false && isPlainLink)
            ? "bg-sidebar-accent text-sidebar-accent-foreground shadow-sm"
            : "text-sidebar-foreground/70 hover:bg-sidebar-accent/60 hover:text-sidebar-accent-foreground",
          current === item.id && isPlainLink && "bg-sidebar-accent text-sidebar-accent-foreground shadow-sm",
        )}
      >
        <item.icon className="size-4.5 shrink-0" />
        <span className="truncate">{item.label}</span>
        {item.id === "admissions" && <Badge className="ml-auto bg-red-600 text-[10px] px-1.5">demo</Badge>}
        {!isPlainLink && (
          <ChevronRight className={cn("ml-auto size-3.5 shrink-0 transition-transform", expanded && "rotate-90")} />
        )}
      </button>
      {expanded && !!item.children?.length && (
        <ul className="mt-0.5 mb-1 ml-4 space-y-0.5 border-l border-sidebar-border/60 pl-2">
          {item.children.map((c) =>
            c.children?.length ? (
              <li key={c.id}>
                <div className="px-2 pt-1.5 pb-0.5 text-[10px] font-semibold uppercase tracking-wider text-sidebar-foreground/40">{c.label}</div>
                <ul className="space-y-0.5">
                  {c.children.map((g) => (
                    <li key={g.id}>
                      <button
                        onClick={() => onSelect(item.id, g.target)}
                        className={cn(
                          "w-full text-left rounded-md px-2.5 py-1.5 text-[12.5px] transition-colors truncate",
                          current === item.id && target?.classroomId === g.target?.classroomId && g.target?.classroomId
                            ? "bg-sidebar-accent text-sidebar-accent-foreground font-medium"
                            : "text-sidebar-foreground/60 hover:bg-sidebar-accent/50 hover:text-sidebar-accent-foreground",
                        )}
                        title={g.label}
                      >
                        {g.label}
                      </button>
                    </li>
                  ))}
                </ul>
              </li>
            ) : (
              <li key={c.id}>
                <button
                  onClick={() => onSelect(item.id, c.target)}
                  className={cn(
                    "w-full text-left rounded-md px-2.5 py-1.5 text-[13px] transition-colors truncate flex items-center gap-2",
                    current === item.id && ((!c.target && !target) || (c.target?.tab && target?.tab === c.target.tab) || (!c.target?.tab && !c.target?.classroomId && !target))
                      ? "bg-sidebar-accent text-sidebar-accent-foreground font-medium"
                      : "text-sidebar-foreground/70 hover:bg-sidebar-accent/50 hover:text-sidebar-accent-foreground",
                  )}
                  title={c.label}
                >
                  {c.icon && <c.icon className="size-3.5 shrink-0" />}
                  <span className="truncate">{c.label}</span>
                </button>
              </li>
            ),
          )}
        </ul>
      )}
    </div>
  );
}

export function AppShell() {
  const { session, schools, currentSchoolId, setCurrentSchool, module, navTarget, setModule, online, queue, syncing, logout, setAiOpen } = useStore();
  const [theme, setTheme] = useState<"light" | "dark">(() => {
    if (typeof window === "undefined") return "light";
    return ((localStorage.getItem("theme") as "light" | "dark") ?? "light");
  });
  const [installEvent, setInstallEvent] = useState<any>(null);
  const [navOpen, setNavOpen] = useState(false);
  /* iEduPro nested navigation: class directory for the level bands
     (Students → level → class; Classes → level → class timetable). Loaded
     from the settings resource of the active school. */
  const [classrooms, setClassrooms] = useState<{ id: string; name: string; grade: string; stage?: string | null }[]>([]);

  useEffect(() => {
    if (!currentSchoolId || !session) return;
    if (!(canModule(session.role, session.permissions, "students") || canModule(session.role, session.permissions, "classes"))) return;
    apiGet<any>(`/api/school/${currentSchoolId}/settings`)
      .then((d) => setClassrooms(d.classrooms ?? []))
      .catch(() => setClassrooms([]));
  }, [currentSchoolId, session?.id, session?.role, session?.permissions]);

  const currentSchool = useMemo(() => schools.find((s) => s.id === currentSchoolId) ?? null, [schools, currentSchoolId]);
  const assocScope = isAssociationRole(session?.role);
  const isGroupOwner = session?.role === "GROUP_OWNER";
  const roleLevel = session ? ROLE_LEVELS[session.role] : undefined;

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

    // ── dynamic iEduPro-style children ──
    const classGroups = levelGroups(classrooms);
    const studentChildren: NavChild[] = [
      { id: "students-all", label: "Directory — all learners", icon: LayoutList },
      ...classGroups.map<NavChild>((g) => ({
        id: `students-${g.band}`,
        label: `${g.label} (${g.classes.length})`,
        children: g.classes.map((c) => ({ id: `students-${c.id}`, label: c.name, target: { classroomId: c.id } })),
      })),
    ];
    const classChildren: NavChild[] = [
      { id: "classes-all", label: "All classes & timetable", icon: LayoutList },
      ...classGroups.map<NavChild>((g) => ({
        id: `classes-${g.band}`,
        label: `${g.label} (${g.classes.length})`,
        children: g.classes.map((c) => ({ id: `classes-${c.id}`, label: c.name, target: { classroomId: c.id } })),
      })),
    ];
    const isTeacher = session?.role === "TEACHER";
    const dailyChildren: NavChild[] = [
      { id: "daily-pos", label: "Counter / POS", target: { tab: "pos" } },
      { id: "daily-wallets", label: "Wallets", target: { tab: "wallets" } },
      { id: "daily-ledger", label: "Ledger & arrears", target: { tab: "ledger" } },
      ...(isTeacher ? [] : [
        { id: "daily-services", label: "Services & enrolment", target: { tab: "services" } },
        { id: "daily-rates", label: "Fee rates", target: { tab: "rates" } },
        { id: "daily-recon", label: "Reconciliation", target: { tab: "recon" } },
      ]),
      { id: "daily-trend", label: "14-day trend", target: { tab: "trend" } },
    ];
    const feesChildren: NavChild[] = [
      { id: "fees-invoices", label: "Invoices", target: { tab: "invoices" } },
      { id: "fees-receipts", label: "Receipts", target: { tab: "receipts" } },
      { id: "fees-structures", label: "Fee structures", target: { tab: "structures" } },
      { id: "fees-discounts", label: "Discounts", target: { tab: "discounts" } },
      { id: "fees-arrears", label: "Arrears chase-list", target: { tab: "arrears" } },
    ];

    // boarding (iEduPro navigation.php FACILITIES): houses → dorms → beds + reports
    const boardingChildren: NavChild[] = [
      { id: "boarding-houses", label: "Boarding houses", target: { tab: "houses" } },
      { id: "boarding-dorms", label: "Dormitories", target: { tab: "dorms" } },
      { id: "boarding-beds", label: "Beds", target: { tab: "beds" } },
      { id: "boarding-assign", label: "Student assignment", target: { tab: "assign" } },
      { id: "boarding-reports", label: "Occupancy reports", target: { tab: "reports" } },
    ];

    // curriculum (iEduPro STUDENTS section: lesson notes, syllabus, study material)
    const curriculumChildren: NavChild[] = [
      { id: "curriculum-notes", label: "Lesson notes", target: { tab: "notes" } },
      { id: "curriculum-templates", label: "Note templates", target: { tab: "templates" } },
      { id: "curriculum-syllabus", label: "Scheme of learning", target: { tab: "syllabus" } },
      { id: "curriculum-materials", label: "Study material", target: { tab: "materials" } },
      { id: "curriculum-completion", label: "Completion dashboard", target: { tab: "completion" } },
    ];

    // permission-gated school modules
    for (const item of SCHOOL_NAV) {
      if (item.id === "branches") continue;
      if (!canModule(session?.role, session?.permissions, item.id)) continue;
      const enriched: NavItem = { ...item };
      if (item.id === "students" && classGroups.length) enriched.children = studentChildren;
      if (item.id === "classes" && classGroups.length) enriched.children = classChildren;
      if (item.id === "daily") enriched.children = dailyChildren;
      if (item.id === "fees") enriched.children = feesChildren;
      if (item.id === "boarding") enriched.children = boardingChildren;
      if (item.id === "curriculum") enriched.children = curriculumChildren;
      items.push(enriched);
    }
    // proprietor branch console
    if (isGroupOwner) {
      const idx = items.findIndex((i) => i.id === "reports");
      if (idx >= 0) items.splice(idx + 1, 0, { id: "branches", label: "Branch Console", icon: Building2, section: "Academic" });
    }
    // parent & learner portals
    for (const item of PORTAL_NAV) {
      if (canModule(session?.role, session?.permissions, item.id)) items.push(item);
    }
    return items;
  }, [assocScope, isGroupOwner, session?.role, session?.permissions, classrooms]);

  /* guard: never sit on a module outside the role's reach (deep links, stale store) */
  useEffect(() => {
    if (!navItems.length) return;
    if (!navItems.some((i) => i.id === module)) setModule(navItems[0].id);
  }, [navItems, module, setModule]);

  const show = useCallback((m: string) => navItems.some((i) => i.id === m), [navItems]);

  async function manualSync() {
    if (!queue.length) { toast({ title: "Nothing to sync", description: "Your queue is empty." }); return; }
    const res = await flushQueue();
    if (res.synced) toast({ title: `Synced ${res.synced} action${res.synced > 1 ? "s" : ""}` });
    if (res.failed) toast({ title: `${res.failed} failed`, description: res.messages.join(" · "), variant: "destructive" });
  }

  const roleLabel = session ? (ROLE_LABELS[session.role] ?? session.role) : "Staff";

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
                  <div className="text-[10px] uppercase tracking-widest text-sidebar-foreground/60 mt-1">{roleLabel}</div>
                </div>
              </div>
              <NavList items={navItems} current={module} target={navTarget} onSelect={(m, t) => { setModule(m, t); setNavOpen(false); }} />
              <div className="p-3 border-t border-sidebar-border text-xs text-sidebar-foreground/60">
                {online ? <span className="flex items-center gap-1.5"><Cloud className="size-3.5 text-emerald-400" /> Online</span> : <span className="flex items-center gap-1.5"><CloudOff className="size-3.5 text-amber-400" /> Offline — {queue.length} queued</span>}
              </div>
            </SheetContent>
          </Sheet>

          {/* school / scope switcher */}
          <div className="flex items-center gap-2 min-w-0">
            <School className="size-4.5 text-muted-foreground hidden sm:block shrink-0" />
            {schools.length > 1 ? (
              (() => {
                // fully-controlled value: fall back when the stored id isn't in the list
                const activeId = schools.some((s) => s.id === currentSchoolId) ? (currentSchoolId as string) : (schools[0]?.id ?? "");
                return (
                  <Select value={activeId} onValueChange={setCurrentSchool}>
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
                );
              })()
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
            {/* admin level chip */}
            {roleLevel !== undefined && (
              <Badge variant="secondary" className="hidden md:inline-flex gap-1 text-[11px]">
                <ShieldCheck className="size-3" /> {ROLE_LEVEL_LABELS[roleLevel]}
              </Badge>
            )}

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
            {canAny(session?.permissions, ["ai.use"]) && (
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
                  <div className="text-xs text-muted-foreground font-normal">{roleLabel}{roleLevel !== undefined ? ` · ${ROLE_LEVEL_LABELS[roleLevel]}` : ""}</div>
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
              <div className="text-[10px] uppercase tracking-widest text-sidebar-foreground/60 mt-1">{roleLabel}</div>
            </div>
          </div>
          <NavList items={navItems} current={module} target={navTarget} onSelect={setModule} />
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

        {/* module content — permission-guarded */}
        <main className="flex-1 min-w-0 p-4 sm:p-6">
          <div className="mx-auto max-w-7xl">
            {module === "association" && assocScope ? <AssociationPortal /> :
             module === "students" && show("students") ? <Students /> :
             module === "admissions" && show("admissions") ? <Admissions /> :
             module === "fees" && show("fees") ? <Fees /> :
             module === "daily" && show("daily") ? <DailyServices /> :
             module === "transport" && show("transport") ? <Transportation /> :
             module === "sms" && show("sms") ? <SmsCenter /> :
             module === "payroll" && show("payroll") ? <Payroll /> :
             module === "inventory" && show("inventory") ? <Inventory /> :
             module === "finance" && show("finance") ? <Finance /> :
             module === "attendance" && show("attendance") ? <Attendance /> :
             module === "classes" && show("classes") ? <Classes /> :
             module === "gradebook" && show("gradebook") ? <Gradebook /> :
             module === "curriculum" && show("curriculum") ? <Curriculum /> :
             module === "reports" && show("reports") ? <Reports /> :
             module === "exams" && show("exams") ? <OnlineExams /> :
             module === "library" && show("library") ? <LibraryPortal /> :
             module === "boarding" && show("boarding") ? <Boarding /> :
             module === "rbac" && show("rbac") ? <RbacConsole /> :
             module === "cashier" && show("cashier") ? <CashierConsole /> :
             module === "conductor" && show("conductor") ? <ConductorPortal /> :
             module === "staffhub" && show("staffhub") ? <StaffPortal /> :
             module === "portal" && show("portal") ? <ParentPortal /> :
             module === "campus" && show("campus") ? <StudentPortal /> :
             module === "branches" && show("branches") ? <BranchConsole /> :
             module === "ai" && show("ai") ? <AiCenter /> :
             module === "settings" && show("settings") ? <Settings /> :
             show("dashboard") ? <Dashboard /> :
             navItems.length ? (
               (() => {
                 const First = navItems[0];
                 return First.id === "portal" ? <ParentPortal /> :
                   First.id === "campus" ? <StudentPortal /> :
                   First.id === "staffhub" ? <StaffPortal /> :
                   First.id === "conductor" ? <ConductorPortal /> :
                   First.id === "cashier" ? <CashierConsole /> :
                   First.id === "library" ? <LibraryPortal /> : <Dashboard />;
               })()
             ) : <Dashboard />}
          </div>
        </main>
      </div>
    </div>
  );
}

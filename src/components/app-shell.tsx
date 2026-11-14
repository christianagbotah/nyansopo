"use client";
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore, hasSchoolScope, isAssociationRole, ROLE_LABELS, ModuleId, NavTarget } from "@/lib/store";
import { canModule, canAny, gateRole, ROLE_LEVELS, ROLE_LEVEL_LABELS } from "@/lib/rbac";
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
import { NotificationBell } from "@/components/notification-bell";
import { CommandPalette } from "@/components/command-palette";
import { MetroMenu, MetroEdgeTrigger } from "@/components/metro-menu";
import { PAGE_INDEX, type PageEntry } from "@/lib/page-index";
import { applyThemeToDOM, type SchoolTheme } from "@/lib/theme";
import { sortClassrooms } from "@/lib/classes";
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
import { MomoPayments } from "@/components/modules/momo-payments";
import { Transportation } from "@/components/modules/transport";
import { SmsCenter } from "@/components/modules/sms-center";
import { Accounts } from "@/components/modules/accounts";
import { Budgets } from "@/components/modules/budgets";
import { Results } from "@/components/modules/results";
import { Messages } from "@/components/modules/messages";
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
import { Discipline } from "@/components/modules/discipline";
import { Health } from "@/components/modules/health";
import { Clubs } from "@/components/modules/clubs";
import { StaffDaily } from "@/components/modules/staff-daily";
import { FrontOffice } from "@/components/modules/front-office";
import { Certificates } from "@/components/modules/certificates";
import {
  LayoutDashboard, Users, Banknote, CalendarCheck, ClipboardList, GraduationCap, ArrowLeftRight, Building2,
  BrainCircuit, Settings as SettingsIcon, Landmark, Menu, CloudOff, Cloud, RefreshCcw, LogOut, Moon, Sun,
  Download, ChevronDown, ChevronRight, School, MessageSquareText, UtensilsCrossed, Bus, Smartphone, Calculator, Package, PieChart, LayoutList,
  BookOpen, ShieldCheck, Receipt, Route, UserRound, Home, Backpack, Laptop, BedDouble, NotebookPen, Search, LayoutGrid, SmartphoneNfc,
  QrCode, UserPlus, Printer, Wallet, BarChart3, CalendarDays, HandCoins, FileWarning, ListChecks, ScanLine, ClipboardPen, Percent,
  PanelLeftClose, PanelLeftOpen, Palette, SlidersHorizontal, Archive, FileText, BookMarked, Target, PiggyBank, Award,
  MessagesSquare, Landmark as LedgerIcon, DatabaseBackup, Gavel, HeartPulse, Stethoscope, Ruler, Trophy, Footprints,
  DoorOpen, CalendarClock, FileBadge,
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
  { id: "staffduty", label: "Staff Attendance & Duty", icon: Footprints, section: "People" },
  { id: "frontoffice", label: "Front Office", icon: DoorOpen, section: "People" },
  { id: "fees", label: "Fees & Billing", icon: Banknote, section: "Finance" },
  { id: "momo", label: "MoMo & Payment Links", icon: SmartphoneNfc, section: "Finance" },
  { id: "daily", label: "Daily Services & Tuck", icon: UtensilsCrossed, section: "Finance" },
  { id: "transport", label: "Transportation", icon: Bus, section: "Finance" },
  { id: "boarding", label: "Boarding Houses", icon: BedDouble, section: "Facilities" },
  { id: "payroll", label: "HR & Payroll", icon: Calculator, section: "Finance" },
  { id: "inventory", label: "Inventory & Stores", icon: Package, section: "Finance" },
  { id: "finance", label: "Finance & Analytics", icon: PieChart, section: "Finance" },
  { id: "accounts", label: "Accounts & Ledger", icon: LedgerIcon, section: "Finance" },
  { id: "budget", label: "Budgets & Fiscal Years", icon: PiggyBank, section: "Finance" },
  { id: "attendance", label: "Attendance", icon: CalendarCheck, section: "Academic" },
  { id: "discipline", label: "Discipline & Conduct", icon: Gavel, section: "Academic" },
  { id: "health", label: "Health & Sick Bay", icon: HeartPulse, section: "Academic" },
  { id: "clubs", label: "Clubs & Societies", icon: Trophy, section: "Academic" },
  { id: "classes", label: "Classes & Timetable", icon: LayoutList, section: "Academic" },
  { id: "gradebook", label: "Gradebook", icon: ClipboardList, section: "Academic" },
  { id: "results", label: "Results & Report Cards", icon: Award, section: "Academic" },
  { id: "certificates", label: "Certificates & Documents", icon: FileBadge, section: "Academic" },
  { id: "curriculum", label: "Curriculum & Notes", icon: NotebookPen, section: "Academic" },
  { id: "reports", label: "Exams & Reports", icon: GraduationCap, section: "Academic" },
  { id: "exams", label: "Online Exams", icon: Laptop, section: "Academic" },
  { id: "library", label: "Library", icon: BookOpen, section: "Academic" },
  { id: "sms", label: "SMS Centre", icon: Smartphone, section: "Communication" },
  { id: "messages", label: "Messages", icon: MessagesSquare, section: "Communication" },
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
  // CI3 getFullClassList ordering: Creche → Nursery → KG → Basic 1..6 → JHS → int'l
  for (const c of sortClassrooms(classrooms.map((x) => ({ ...x, gradeLevel: x.grade })))) {
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
  const { session, schools, currentSchoolId, setCurrentSchool, module, navTarget, cashierTab, setModule, online, queue, syncing, logout, setAiOpen } = useStore();
  const [theme, setTheme] = useState<"light" | "dark">(() => {
    if (typeof window === "undefined") return "light";
    return ((localStorage.getItem("theme") as "light" | "dark") ?? "light");
  });
  const [installEvent, setInstallEvent] = useState<any>(null);
  const [navOpen, setNavOpen] = useState(false);
  const [paletteOpen, setPaletteOpen] = useState(false);
  const [metroOpen, setMetroOpen] = useState(false);
  /* sidebar rail: "auto" collapses only on the fee collection portal
     (Cashier › Collection portal — the CI3 portal is a full-width console),
     "open"/"closed" is the user's manual pin — persisted so the workspace
     stays how they left it */
  const [rail, setRail] = useState<"auto" | "open" | "closed">(() => {
    if (typeof window === "undefined") return "auto";
    return ((localStorage.getItem("sidebar-rail") as "auto" | "open" | "closed") ?? "auto");
  });
  useEffect(() => { localStorage.setItem("sidebar-rail", rail); }, [rail]);
  const railCollapsed = rail === "closed" || (rail === "auto" && module === "cashier" && cashierTab === "collect");
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
  const roleLevel = session ? (session.roleLevel ?? ROLE_LEVELS[session.role]) : undefined;
  /* module-gating role: custom backoffice roles behave like SCHOOL_ADMIN for
     the hard role gates (dashboard) — everything else is permission-list-gated */
  const gatingRole = gateRole(session);

  useEffect(() => {
    document.documentElement.classList.toggle("dark", theme === "dark");
    localStorage.setItem("theme", theme);
  }, [theme]);

  /* ── school theming (CI3 app_theme parity): load the school's colours and
     paint the whole shell — top bar, sidebar, buttons, modal headers, charts.
     Falls back to the Nyansapo brand when no theme row exists. ── */
  useEffect(() => {
    if (!currentSchoolId || !session) return;
    apiGet<{ theme: SchoolTheme }>(`/api/school/${currentSchoolId}/ops/theme`)
      .then((d) => { if (d?.theme) applyThemeToDOM(d.theme); })
      .catch(() => null);
  }, [currentSchoolId, session?.id]);

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
        { id: "daily-meals", label: "Meals & opt-ins", target: { tab: "meals" } },
        { id: "daily-rates", label: "Fee rates", target: { tab: "rates" } },
        { id: "daily-recon", label: "Reconciliation", target: { tab: "recon" } },
      ]),
      { id: "daily-trend", label: "14-day trend", target: { tab: "trend" } },
    ];
    /* CI3 student_payment.php tabs — Bill items / Create invoices / Bulk manage */
    const feesChildren: NavChild[] = [
      { id: "fees-bill-items", label: "Bill items", target: { tab: "bill-items" } },
      { id: "fees-create", label: "Create invoices", target: { tab: "create" } },
      { id: "fees-invoices", label: "Bulk manage invoices", target: { tab: "invoices" } },
      { id: "fees-receipts", label: "Receipts", target: { tab: "receipts" } },
      { id: "fees-structures", label: "Fee structures", target: { tab: "structures" } },
      { id: "fees-discounts", label: "Discounts", target: { tab: "discounts" } },
      { id: "fees-arrears", label: "Arrears chase-list", target: { tab: "arrears" } },
      { id: "fees-credits", label: "Credits", target: { tab: "credits" } },
      { id: "fees-approvals", label: "Modification approvals", target: { tab: "approvals" } },
    ];
    /* CI3 cashier sidebar: portal → collections → summary → handover → receipts */
    const cashierChildren: NavChild[] = [
      { id: "cashier-portal", label: "Fee collection portal", target: { tab: "portal" } },
      { id: "cashier-collections", label: "My collections", target: { tab: "collections" } },
      { id: "cashier-summary", label: "Daily summary", target: { tab: "summary" } },
      { id: "cashier-receipts", label: "Print receipts", target: { tab: "receipts" } },
      { id: "cashier-ledger", label: "Student ledger", target: { tab: "ledger" } },
    ];
    /* CI3 attendance sidebar: dashboard / mark / report / scanner */
    const attendanceChildren: NavChild[] = [
      { id: "att-register", label: "Mark attendance", target: { tab: "register" } },
      { id: "att-analytics", label: "Analytics & trends", target: { tab: "analytics" } },
      { id: "att-monthly", label: "Monthly report", target: { tab: "monthly" } },
      { id: "att-scanner", label: "Barcode scanner", target: { tab: "scanner" } },
    ];
    /* discipline sidebar: the conduct register, commendations wall,
       learner profiles and analytics (iEduPro discipline parity) */
    const disciplineChildren: NavChild[] = [
      { id: "disc-register", label: "Conduct register", target: { tab: "register" } },
      { id: "disc-commendations", label: "Commendations", target: { tab: "commendations" } },
      { id: "disc-profiles", label: "Learner profiles", target: { tab: "profiles" } },
      { id: "disc-analytics", label: "Conduct analytics", target: { tab: "analytics" } },
    ];
    /* health sidebar: the sick-bay register, SHEP screenings, learner health
       profiles and wellness analytics (wave 26 clinic parity) */
    const healthChildren: NavChild[] = [
      { id: "health-sickbay", label: "Sick-bay register", target: { tab: "sickbay" } },
      { id: "health-screenings", label: "Screenings", target: { tab: "screenings" } },
      { id: "health-profiles", label: "Health profiles", target: { tab: "profiles" } },
      { id: "health-analytics", label: "Wellness analytics", target: { tab: "analytics" } },
    ];
    /* clubs sidebar: the club register, meetings, events, learner
       co-curricular profiles and participation analytics (wave 27) */
    const clubsChildren: NavChild[] = [
      { id: "clubs-register", label: "Club register", target: { tab: "register" } },
      { id: "clubs-meetings", label: "Meeting register", target: { tab: "meetings" } },
      { id: "clubs-events", label: "Competitions & events", target: { tab: "events" } },
      { id: "clubs-profiles", label: "Co-curricular profiles", target: { tab: "profiles" } },
      { id: "clubs-analytics", label: "Participation analytics", target: { tab: "analytics" } },
    ];
    /* staff attendance & duty sidebar (wave 28 — the staff movement book) */
    const staffdutyChildren: NavChild[] = [
      { id: "staffduty-register", label: "Daily staff register", target: { tab: "register" } },
      { id: "staffduty-leave", label: "Leave ledger", target: { tab: "leave" } },
      { id: "staffduty-roster", label: "Duty roster", target: { tab: "roster" } },
      { id: "staffduty-movement", label: "Movement book", target: { tab: "movement" } },
      { id: "staffduty-cover", label: "Substitution cover", target: { tab: "cover" } },
      { id: "staffduty-analytics", label: "Staff ops analytics", target: { tab: "analytics" } },
    ];
    const frontofficeChildren: NavChild[] = [
      { id: "frontoffice-board", label: "Front desk board", target: { tab: "board" } },
      { id: "frontoffice-visitors", label: "Visitors book", target: { tab: "visitors" } },
      { id: "frontoffice-appointments", label: "Appointments book", target: { tab: "appointments" } },
      { id: "frontoffice-calls", label: "Phone call log", target: { tab: "calls" } },
      { id: "frontoffice-postal", label: "Postal register", target: { tab: "postal" } },
      { id: "frontoffice-events", label: "School events", target: { tab: "events" } },
    ];
    /* certificates sidebar (wave 30): the issue register, the issue desk and
       the A4 document formats (leaving certificate, testimonial, letters) */
    const certificatesChildren: NavChild[] = [
      { id: "cert-register", label: "Issue register", target: { tab: "register" } },
      { id: "cert-issue", label: "Issue a document", target: { tab: "issue" } },
      { id: "cert-formats", label: "Document formats", target: { tab: "formats" } },
    ];
    /* CI3 settings sidebar: general / theme / sms / permissions / payment */
    const settingsChildren: NavChild[] = [
      { id: "set-system", label: "System settings", target: { tab: "system" } },
      { id: "set-branding", label: "Branding & logos", target: { tab: "branding" } },
      { id: "set-academic", label: "Calendar & terms", target: { tab: "calendar" } },
      { id: "set-classes", label: "Classes", target: { tab: "classes" } },
      { id: "set-subjects", label: "Subjects", target: { tab: "subjects" } },
      { id: "set-grading", label: "Grading scale", target: { tab: "grading" } },
      { id: "set-statutory", label: "Statutory (SSNIT) rates", target: { tab: "statutory" } },
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
      if (!canModule(gatingRole, session?.permissions, item.id)) continue;
      const enriched: NavItem = { ...item };
      if (item.id === "students" && classGroups.length) enriched.children = studentChildren;
      if (item.id === "classes" && classGroups.length) enriched.children = classChildren;
      if (item.id === "daily") enriched.children = dailyChildren;
      if (item.id === "fees") enriched.children = feesChildren;
      if (item.id === "cashier") enriched.children = cashierChildren;
      if (item.id === "attendance") enriched.children = attendanceChildren;
      if (item.id === "discipline") enriched.children = disciplineChildren;
      if (item.id === "health") enriched.children = healthChildren;
      if (item.id === "clubs") enriched.children = clubsChildren;
      if (item.id === "staffduty") enriched.children = staffdutyChildren;
      if (item.id === "frontoffice") enriched.children = frontofficeChildren;
      if (item.id === "certificates") enriched.children = certificatesChildren;
      if (item.id === "settings") enriched.children = settingsChildren;
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
      if (canModule(gatingRole, session?.permissions, item.id)) items.push(item);
    }
    return items;
  }, [assocScope, isGroupOwner, session?.role, session?.permissions, classrooms]);

  /* guard: never sit on a module outside the role's reach (deep links, stale store) */
  useEffect(() => {
    if (!navItems.length) return;
    if (!navItems.some((i) => i.id === module)) setModule(navItems[0].id);
  }, [navItems, module, setModule]);

  /* ── Metro Start sections (CI3 metro_menu.php): every section holds EXACTLY
     6 square tiles — candidates are permission-filtered, first 6 allowed win,
     and a section with fewer than 6 reachable tiles is skipped entirely so no
     section is ever longer or shorter than another. ── */
  /* ── Metro page catalogue (wave 10): every page in the app, permission-filtered
     for this session — searched by the Start screen so a user can find any page. ── */
  const metroPages = useMemo(() => {
    const role = gateRole(session);
    const perms = session?.permissions;
    return PAGE_INDEX.filter((p) => {
      if (p.roles) {
        // hard role lists gate the REAL role (PARENT/STUDENT/GROUP_OWNER/association);
        // a custom backoffice role never matches portal lists
        if (p.roles.includes("PARENT") || p.roles.includes("STUDENT") || p.roles.includes("GROUP_OWNER") || p.roles.some((r) => r.startsWith("ASSOCIATION_"))) {
          return p.roles.includes(session?.role as string);
        }
        return false;
      }
      if (!canModule(role, perms, p.module)) return false;
      if (p.perm && !canAny(perms, p.perm)) return false;
      return true;
    }) as PageEntry[];
  }, [session, gatingRole]);

  const metroSections = useMemo(() => {
    const open = (m: string) => canModule(gatingRole, session?.permissions, m);
    const canFees = open("fees");
    const canDaily = open("daily");
    const canCashier = open("cashier");
    // the MS-Metro flat palette (metro_menu.php .tile-*)
    const C = { blue: "#0078d4", green: "#107c10", orange: "#d83b01", purple: "#5c2d91", teal: "#008272", amber: "#ff8c00", cyan: "#00bcf2", emerald: "#00b294", red: "#e81123", pink: "#ec008c", violet: "#881798", gray: "#5d5a58" };
    const defs: { title: string; tiles: { key: string; label: string; icon: any; module: string; target?: any; color: string; when?: boolean }[] }[] = [
      {
        title: "Dashboard & Quick Access",
        tiles: [
          { key: "m-dash", label: "Dashboard", icon: LayoutDashboard, module: "dashboard", color: C.blue, when: open("dashboard") },
          { key: "m-cashier", label: "Cashier Console", icon: Receipt, module: "cashier", target: { tab: "portal" }, color: C.green, when: canCashier },
          { key: "m-pos", label: "Collect Daily Fees", icon: UtensilsCrossed, module: "daily", target: { tab: "pos" }, color: C.teal, when: canDaily },
          { key: "m-mark", label: "Mark Attendance", icon: CalendarCheck, module: "attendance", target: { tab: "register" }, color: C.amber, when: open("attendance") },
          { key: "m-inv", label: "Invoices", icon: Banknote, module: "fees", target: { tab: "invoices" }, color: C.purple, when: canFees },
          { key: "m-sms", label: "Compose SMS", icon: Smartphone, module: "sms", color: C.cyan, when: open("sms") },
          { key: "m-conductor", label: "Conductor Portal", icon: Route, module: "conductor", color: C.emerald, when: open("conductor") },
          { key: "m-wallets", label: "Wallets", icon: Wallet, module: "daily", target: { tab: "wallets" }, color: C.cyan, when: canDaily },
          { key: "m-attan", label: "Attendance Trends", icon: BarChart3, module: "attendance", target: { tab: "analytics" }, color: C.violet, when: open("attendance") },
        ],
      },
      {
        title: "Students & People",
        tiles: [
          { key: "m-students", label: "All Students", icon: Users, module: "students", color: C.blue, when: open("students") },
          { key: "m-admit", label: "Admit Student", icon: UserPlus, module: "admissions", color: C.green, when: open("admissions") },
          { key: "m-scan", label: "QR Scanner", icon: QrCode, module: "students", target: { tab: "scanner" }, color: C.orange, when: open("students") },
          { key: "m-staff", label: "Staff Portal", icon: UserRound, module: "staffhub", color: C.purple, when: open("staffhub") },
          { key: "m-rbac", label: "Users & Access", icon: ShieldCheck, module: "rbac", color: C.red, when: open("rbac") },
          { key: "m-classes", label: "Classes", icon: LayoutList, module: "classes", color: C.teal, when: open("classes") },
          { key: "m-payroll", label: "HR & Payroll", icon: Calculator, module: "payroll", color: C.violet, when: open("payroll") },
          { key: "m-notes", label: "Lesson Notes", icon: NotebookPen, module: "curriculum", target: { tab: "notes" }, color: C.cyan, when: open("curriculum") },
          { key: "m-monthly", label: "Monthly Register", icon: CalendarDays, module: "attendance", target: { tab: "monthly" }, color: C.amber, when: open("attendance") },
        ],
      },
      {
        title: "Student Billing",
        tiles: [
          { key: "m-bill", label: "Bill Items", icon: ListChecks, module: "fees", target: { tab: "bill-items" }, color: C.blue, when: canFees },
          { key: "m-create", label: "Create Invoices", icon: ClipboardPen, module: "fees", target: { tab: "create" }, color: C.green, when: canFees },
          { key: "m-bulk", label: "Bulk Manage", icon: FileWarning, module: "fees", target: { tab: "invoices" }, color: C.orange, when: canFees },
          { key: "m-receipts", label: "Receipts", icon: Printer, module: "fees", target: { tab: "receipts" }, color: C.purple, when: canFees },
          { key: "m-struct", label: "Fee Structure", icon: Banknote, module: "fees", target: { tab: "structures" }, color: C.teal, when: canFees },
          { key: "m-disc", label: "Discounts", icon: Percent, module: "fees", target: { tab: "discounts" }, color: C.pink, when: canFees },
          { key: "m-credits", label: "Student Credits", icon: Wallet, module: "fees", target: { tab: "credits" }, color: C.emerald, when: canFees },
        ],
      },
      {
        title: "Collections & Cashier",
        tiles: [
          { key: "m-portal", label: "Collection Portal", icon: Receipt, module: "cashier", target: { tab: "portal" }, color: C.green, when: canCashier },
          { key: "m-mycoll", label: "My Collections", icon: HandCoins, module: "cashier", target: { tab: "collections" }, color: C.blue, when: canCashier },
          { key: "m-summary", label: "Daily Summary", icon: BarChart3, module: "cashier", target: { tab: "summary" }, color: C.purple, when: canCashier },
          { key: "m-printr", label: "Print Receipts", icon: Printer, module: "cashier", target: { tab: "receipts" }, color: C.orange, when: canCashier },
          { key: "m-momo", label: "MoMo & Links", icon: SmartphoneNfc, module: "momo", color: C.cyan, when: open("momo") },
          { key: "m-arrears", label: "Arrears Chase", icon: FileWarning, module: "fees", target: { tab: "arrears" }, color: C.red, when: canFees },
          { key: "m-trend", label: "14-Day Trend", icon: BarChart3, module: "daily", target: { tab: "trend" }, color: C.amber, when: canDaily },
        ],
      },
      {
        title: "Daily Services & Transport",
        tiles: [
          { key: "m-wallets", label: "Wallets", icon: Wallet, module: "daily", target: { tab: "wallets" }, color: C.blue, when: canDaily },
          { key: "m-ledger", label: "Ledger & Arrears", icon: ListChecks, module: "daily", target: { tab: "ledger" }, color: C.green, when: canDaily },
          { key: "m-services", label: "Services & Enrolment", icon: UtensilsCrossed, module: "daily", target: { tab: "services" }, color: C.teal, when: canDaily },
          { key: "m-rates", label: "Fee Rates", icon: HandCoins, module: "daily", target: { tab: "rates" }, color: C.amber, when: canDaily },
          { key: "m-recon", label: "Reconciliation", icon: BarChart3, module: "daily", target: { tab: "recon" }, color: C.purple, when: canDaily },
          { key: "m-transport", label: "Transport", icon: Bus, module: "transport", color: C.cyan, when: open("transport") },
          { key: "m-boarding", label: "Boarding", icon: BedDouble, module: "boarding", color: C.violet, when: open("boarding") },
          { key: "m-trend2", label: "14-Day Trend", icon: BarChart3, module: "daily", target: { tab: "trend" }, color: C.pink, when: canDaily },
          { key: "m-scanatt", label: "Barcode Scanner", icon: ScanLine, module: "attendance", target: { tab: "scanner" }, color: C.gray, when: open("attendance") },
        ],
      },
      {
        title: "Academics & Exams",
        tiles: [
          { key: "m-grade", label: "Manage Marks", icon: ClipboardList, module: "gradebook", color: C.blue, when: open("gradebook") },
          { key: "m-curr", label: "Lesson Notes", icon: NotebookPen, module: "curriculum", target: { tab: "notes" }, color: C.green, when: open("curriculum") },
          { key: "m-reports", label: "Reports & Marksheets", icon: GraduationCap, module: "reports", color: C.purple, when: open("reports") },
          { key: "m-discipline", label: "Discipline", icon: Gavel, module: "discipline", color: C.red, when: open("discipline") },
          { key: "m-sickbay", label: "Sick Bay", icon: Stethoscope, module: "health", target: { tab: "sickbay" }, color: C.red, when: open("health") },
          { key: "m-screenings", label: "Screenings", icon: Ruler, module: "health", target: { tab: "screenings" }, color: C.teal, when: open("health") },
          { key: "m-exams", label: "Online Exams", icon: Laptop, module: "exams", color: C.teal, when: open("exams") },
          { key: "m-library", label: "Library", icon: BookOpen, module: "library", color: C.orange, when: open("library") },
          { key: "m-tt", label: "Timetable", icon: CalendarDays, module: "classes", target: { tab: "timetable" }, color: C.cyan, when: open("classes") },
          { key: "m-syll", label: "Syllabus", icon: BookOpen, module: "curriculum", target: { tab: "syllabus" }, color: C.pink, when: open("curriculum") },
        ],
      },
      {
        title: "Finance & Communication",
        tiles: [
          { key: "m-finance", label: "Finance Analytics", icon: PieChart, module: "finance", color: C.green, when: open("finance") },
          { key: "m-inventory", label: "Point of Sale", icon: Package, module: "inventory", color: C.blue, when: open("inventory") },
          { key: "m-payroll2", label: "Pay Salaries", icon: Calculator, module: "payroll", color: C.violet, when: open("payroll") },
          { key: "m-smsc", label: "SMS Automation", icon: MessageSquareText, module: "sms", target: { tab: "automations" }, color: C.cyan, when: open("sms") },
          { key: "m-ai", label: "AI Assistant", icon: BrainCircuit, module: "ai", color: C.purple, when: open("ai") },
          { key: "m-settings", label: "System Settings", icon: SettingsIcon, module: "settings", target: { tab: "system" }, color: C.gray, when: open("settings") },
          { key: "m-cashier-ledger", label: "Student Ledger", icon: ListChecks, module: "cashier", target: { tab: "ledger" }, color: C.teal, when: canCashier },
        ],
      },
      {
        title: "System & Administration",
        tiles: [
          { key: "m-profile", label: "School Profile", icon: School, module: "settings", target: { tab: "profile" }, color: C.blue, when: open("settings") },
          { key: "m-branding", label: "Branding & Logos", icon: Palette, module: "settings", target: { tab: "branding" }, color: C.green, when: open("settings") },
          { key: "m-calendar", label: "Calendar & Terms", icon: CalendarDays, module: "settings", target: { tab: "calendar" }, color: C.teal, when: open("settings") },
          { key: "m-subjects", label: "Subjects", icon: BookOpen, module: "settings", target: { tab: "subjects" }, color: C.amber, when: open("settings") },
          { key: "m-grading", label: "Grading Scale", icon: SlidersHorizontal, module: "settings", target: { tab: "grading" }, color: C.purple, when: open("settings") },
          { key: "m-statutory", label: "Statutory Rates", icon: Landmark, module: "settings", target: { tab: "statutory" }, color: C.gray, when: open("settings") },
        ],
      },
      {
        title: "Reports & Analytics",
        tiles: [
          { key: "m-tcards", label: "Terminal Reports", icon: GraduationCap, module: "reports", target: { tab: "cards" }, color: C.blue, when: open("reports") },
          { key: "m-cumulative", label: "Cumulative Records", icon: Archive, module: "reports", target: { tab: "cumulative" }, color: C.green, when: open("reports") },
          { key: "m-bece", label: "BECE Papers", icon: FileText, module: "reports", target: { tab: "papers" }, color: C.orange, when: open("reports") },
          { key: "m-gb-analytics", label: "Gradebook Analytics", icon: BarChart3, module: "gradebook", target: { tab: "analytics" }, color: C.purple, when: open("gradebook") },
          { key: "m-framework", label: "Curriculum Framework", icon: BookMarked, module: "curriculum", target: { tab: "framework" }, color: C.teal, when: open("curriculum") },
          { key: "m-completion", label: "Completion Dashboard", icon: Target, module: "curriculum", target: { tab: "completion" }, color: C.cyan, when: open("curriculum") },
        ],
      },
    ];
    return defs
      .map((d) => ({ title: d.title, tiles: d.tiles.filter((t) => t.when !== false).slice(0, 6) }))
      .filter((d) => d.tiles.length === 6)
      .map((d) => ({
        title: d.title,
        tiles: d.tiles.map(({ key, label, icon, module: m, target, color }) => ({ id: key, label, icon, module: m, target, color })),
      }));
  }, [gatingRole, session?.permissions]);

  // plain function — the React compiler memoizes it; a manual useCallback here
  // tripped preserve-manual-memoization (navItems is already memoized above)
  const show = (m: string) => navItems.some((i) => i.id === m);

  async function manualSync() {
    if (!queue.length) { toast({ title: "Nothing to sync", description: "Your queue is empty." }); return; }
    const res = await flushQueue();
    if (res.synced) toast({ title: `Synced ${res.synced} action${res.synced > 1 ? "s" : ""}` });
    if (res.failed) toast({ title: `${res.failed} failed`, description: res.messages.join(" · "), variant: "destructive" });
  }

  const roleLabel = session ? (session.roleLabel ?? ROLE_LABELS[session.role] ?? session.role) : "Staff";

  return (
    <div className="min-h-screen flex flex-col bg-background">
      {/* ── top bar ── */}
      <header className="theme-topbar sticky top-0 z-40 bg-card border-b border-border">
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
              <Button size="sm" variant="outline" className="gap-1.5 h-8 text-xs" onClick={() => { installEvent.prompt(); setInstallEvent(null); }}>
                <Download className="size-3.5" /> <span className="hidden sm:inline">Install app</span>
              </Button>
            )}

            {/* notification centre */}
            <NotificationBell />

            {/* command palette trigger (also Ctrl+K / ⌘K anywhere) */}
            <Button
              size="sm" variant="outline"
              className="gap-1.5 h-8 text-muted-foreground w-8 sm:w-auto sm:px-2.5"
              title="Search (Ctrl+K)"
              onClick={() => setPaletteOpen(true)}
            >
              <Search className="size-3.5" />
              <span className="hidden sm:inline text-xs">Search</span>
              <kbd className="hidden md:inline-flex items-center gap-0.5 rounded border bg-muted px-1 font-sans text-[10px] font-medium text-muted-foreground">⌘K</kbd>
            </Button>

            {/* Metro Start screen (iEduPro metro_menu parity) */}
            <Button
              size="sm" variant="outline"
              className="gap-1.5 h-8 px-2.5"
              title="Start menu — all modules (Esc to close)"
              onClick={() => setMetroOpen(true)}
            >
              <LayoutGrid className="size-4" />
              <span className="hidden sm:inline text-xs font-medium">Start</span>
            </Button>

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
        {/* desktop sidebar — collapses to an icon rail only on the fee collection
            portal (Cashier › Collection portal) or when the user pins it closed;
            the pin button lives in the rail header */}
        <aside className={cn(
          "theme-sidebar hidden lg:flex flex-col bg-sidebar text-sidebar-foreground sticky top-[61px] self-start h-[calc(100vh-61px)] transition-all duration-200",
          railCollapsed ? "w-14" : "w-64",
        )}>
          <div className={cn("p-3 flex items-center gap-2.5 border-b border-sidebar-border", railCollapsed && "flex-col px-2")}>
            <LogoMark size={30} />
            {!railCollapsed && (
              <div className="min-w-0">
                <div className="font-bold leading-none text-sidebar-foreground">Nyansapo</div>
                <div className="text-[10px] uppercase tracking-widest text-sidebar-foreground/60 mt-1">{roleLabel}</div>
              </div>
            )}
            <button
              type="button"
              title={railCollapsed ? "Expand sidebar" : "Collapse sidebar (auto-collapses on the collection portal)"}
              onClick={() => setRail(railCollapsed ? "open" : "closed")}
              className={cn("grid place-items-center rounded-md p-1.5 text-sidebar-foreground/60 hover:bg-sidebar-accent hover:text-sidebar-foreground transition-colors", !railCollapsed && "ml-auto")}
            >
              {railCollapsed ? <PanelLeftOpen className="size-5" /> : <PanelLeftClose className="size-5" />}
            </button>
          </div>
          {railCollapsed ? (
            <nav className="flex-1 flex flex-col items-center gap-0.5 py-3 overflow-y-auto nice-scroll">
              {navItems.map((item) => {
                const Icon = item.icon;
                return (
                  <button key={item.id} type="button" title={item.label}
                    onClick={() => setModule(item.id)}
                    className={cn(
                      "grid place-items-center size-10 rounded-lg transition-colors",
                      module === item.id ? "bg-sidebar-accent text-sidebar-accent-foreground shadow-sm" : "text-sidebar-foreground/60 hover:bg-sidebar-accent/60 hover:text-sidebar-foreground",
                    )}>
                    {Icon && <Icon className="size-6" />}
                  </button>
                );
              })}
            </nav>
          ) : (
            <>
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
            </>
          )}
        </aside>

        {/* module content — permission-guarded */}
        <main className="flex-1 min-w-0 p-4 sm:p-6">
          <div className="mx-auto max-w-7xl">
            {module === "association" && assocScope ? <AssociationPortal /> :
             module === "students" && show("students") ? <Students /> :
             module === "admissions" && show("admissions") ? <Admissions /> :
             module === "fees" && show("fees") ? <Fees /> :
             module === "momo" && show("momo") ? <MomoPayments /> :
             module === "daily" && show("daily") ? <DailyServices /> :
             module === "transport" && show("transport") ? <Transportation /> :
             module === "sms" && show("sms") ? <SmsCenter /> :
             module === "accounts" && show("accounts") ? <Accounts /> :
             module === "budget" && show("budget") ? <Budgets /> :
             module === "messages" && show("messages") ? <Messages /> :
             module === "payroll" && show("payroll") ? <Payroll /> :
             module === "inventory" && show("inventory") ? <Inventory /> :
             module === "finance" && show("finance") ? <Finance /> :
             module === "attendance" && show("attendance") ? <Attendance /> :
             module === "discipline" && show("discipline") ? <Discipline /> :
             module === "health" && show("health") ? <Health /> :
             module === "clubs" && show("clubs") ? <Clubs /> :
             module === "staffduty" && show("staffduty") ? <StaffDaily /> :
             module === "frontoffice" && show("frontoffice") ? <FrontOffice /> :
             module === "certificates" && show("certificates") ? <Certificates /> :
             module === "classes" && show("classes") ? <Classes /> :
             module === "gradebook" && show("gradebook") ? <Gradebook /> :
             module === "results" && show("results") ? <Results /> :
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

      {/* ⌘K command palette (modules, classes, quick actions) */}
      <CommandPalette
        items={navItems}
        open={paletteOpen}
        setOpen={setPaletteOpen}
        onToggleTheme={toggleTheme}
        theme={theme}
      />

      {/* Metro Start screen (iEduPro metro_menu parity) — colorful tile grid of
          every module the session may open, with live search. Conditionally
          mounted so each open starts fresh. */}
      {metroOpen && (
        <MetroMenu
          sections={metroSections}
          pages={metroPages}
          current={module}
          currentTarget={navTarget}
          onSelect={(id, t) => setModule(id as ModuleId, t)}
          open={metroOpen}
          onOpenChange={setMetroOpen}
        />
      )}
      {/* floating edge launcher (CI3's pinned Start trigger) */}
      {!metroOpen && navItems.length > 1 && <MetroEdgeTrigger onClick={() => setMetroOpen(true)} />}
    </div>
  );
}

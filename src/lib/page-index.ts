// ─────────────────────────────────────────────────────────────
// PAGE INDEX — every reachable page in the app (metro Start search)
//
// The metro Start screen tiles are a curated 6-per-section snapshot;
// this index is the FULL catalogue: every tab of every module, each
// carrying its deep-link target, permission gate and search keywords.
// The MetroMenu searches it live ("virtually search for whatever
// page he wants to visit") and offers an A–Z browse of all pages.
// ─────────────────────────────────────────────────────────────
import {
  LayoutDashboard, Users, UserPlus, QrCode, ListChecks, ClipboardPen, FileWarning, Printer, Banknote,
  Percent, Wallet, Receipt, HandCoins, BarChart3, SmartphoneNfc, UtensilsCrossed, Coffee, Droplets, Bus,
  BookOpen, CalendarCheck, CalendarDays, ScanLine, ClipboardList, NotebookPen, GraduationCap, Laptop,
  BedDouble, Calculator, Package, PieChart, Settings as SettingsIcon, ShieldCheck, BrainCircuit,
  Landmark, Building2, Route, Smartphone, MessageSquareText, FileSpreadsheet, BadgeCheck, Layers,
  Armchair, BookMarked, Network, UsersRound, HeartPulse, ScrollText, Wrench, Timer, Stethoscope,
  Flag, Trees, Sparkles, Scale, FileSearch, ShoppingBasket, GalleryHorizontal, Rows3, Banknote as Bank, Palette,
} from "lucide-react";

export type PageEntry = {
  id: string;
  label: string;
  section: string;
  module: string;
  target?: any;
  /** any-of permission gate in addition to the module gate */
  perm?: string[];
  /** hard role gate (portal pages) */
  roles?: string[];
  icon: any;
  keywords?: string[];
};

const S = {
  quick: "Dashboard & Quick Access",
  people: "Students & People",
  billing: "Student Billing",
  cashier: "Collections & Cashier",
  daily: "Daily Services & Transport",
  academic: "Academics & Exams",
  finance: "Finance & Communication",
  system: "System & Administration",
};

export const PAGE_INDEX: PageEntry[] = [
  // ── Dashboard & Quick Access ──
  { id: "pg-dash", label: "Dashboard", section: S.quick, module: "dashboard", icon: LayoutDashboard, keywords: ["home", "overview", "kpi"] },
  { id: "pg-branches", label: "Branch Console", section: S.quick, module: "branches", roles: ["GROUP_OWNER"], icon: Building2, keywords: ["proprietor", "schools", "group"] },
  { id: "pg-assoc", label: "Association Portal", section: S.quick, module: "association", roles: ["ASSOCIATION_NATIONAL", "ASSOCIATION_REGIONAL", "ASSOCIATION_DISTRICT", "ASSOCIATION_ZONAL", "SYSTEM_ADMIN"], icon: Landmark, keywords: ["network", "union", "gba"] },

  // ── Students & People ──
  { id: "pg-students", label: "Student Directory", section: S.people, module: "students", target: { tab: "roster" }, perm: ["students.view"], icon: Users, keywords: ["learners", "pupils", "register", "names"] },
  { id: "pg-idcards", label: "ID Cards", section: S.people, module: "students", target: { tab: "idcards" }, perm: ["students.view"], icon: QrCode, keywords: ["identity", "badge", "print", "card"] },
  { id: "pg-admit", label: "Admit Student", section: S.people, module: "admissions", target: { tab: "admit" }, perm: ["admissions.manage"], icon: UserPlus, keywords: ["enrol", "enroll", "new", "registration", "form"] },
  { id: "pg-bulk-admit", label: "Bulk Admission", section: S.people, module: "admissions", target: { tab: "bulk" }, perm: ["admissions.manage"], icon: UsersRound, keywords: ["csv", "import", "many"] },
  { id: "pg-inquiries", label: "Admission Enquiries", section: S.people, module: "admissions", target: { tab: "inquiries" }, perm: ["admissions.manage"], icon: MessageSquareText, keywords: ["prospective", "enquiry", "enquiry"] },
  { id: "pg-adm-registry", label: "Admissions Registry", section: S.people, module: "admissions", target: { tab: "registry" }, perm: ["admissions.manage"], icon: ScrollText, keywords: ["history", "leavers", "intake"] },
  { id: "pg-adm-requests", label: "Admission Requests", section: S.people, module: "admissions", target: { tab: "requests" }, perm: ["admissions.manage"], icon: FileSearch, keywords: ["online", "applications"] },
  { id: "pg-adm-transfers", label: "Transfer Requests", section: S.people, module: "admissions", target: { tab: "transfers" }, perm: ["admissions.manage"], icon: Rows3, keywords: ["moving", "incoming", "leaving"] },
  { id: "pg-staffhub", label: "Staff Self-Service", section: S.people, module: "staffhub", perm: ["staffhub.self"], icon: BadgeCheck, keywords: ["my portal", "leave", "payslip"] },
  { id: "pg-rbac", label: "Users & Access", section: S.people, module: "rbac", target: { tab: "users" }, perm: ["rbac.manage"], icon: ShieldCheck, keywords: ["accounts", "logins", "passwords", "staff accounts"] },
  { id: "pg-roles", label: "Roles & Permissions", section: S.people, module: "rbac", target: { tab: "roles" }, perm: ["rbac.manage"], icon: ShieldCheck, keywords: ["access control", "custom role", "cashier permissions", "acl", "grant"] },

  // ── Student Billing (invoices / termly) ──
  { id: "pg-bill-items", label: "Bill Items", section: S.billing, module: "fees", target: { tab: "bill-items" }, perm: ["fees.view"], icon: ListChecks, keywords: ["fee items", "catalogue", "tuition"] },
  { id: "pg-create-inv", label: "Create Invoices", section: S.billing, module: "fees", target: { tab: "create" }, perm: ["fees.manage"], icon: ClipboardPen, keywords: ["bill", "invoice", "charge", "termly"] },
  { id: "pg-invoices", label: "Bulk Manage Invoices", section: S.billing, module: "fees", target: { tab: "invoices" }, perm: ["fees.view"], icon: FileWarning, keywords: ["mass", "write off", "overdue", "bulk"] },
  { id: "pg-inv-receipts", label: "Invoice Receipts", section: S.billing, module: "fees", target: { tab: "receipts" }, perm: ["fees.view"], icon: Printer, keywords: ["print", "thermal", "80mm"] },
  { id: "pg-structures", label: "Fee Structures", section: S.billing, module: "fees", target: { tab: "structures" }, perm: ["fees.view"], icon: Banknote, keywords: ["class fees", "template", "amounts"] },
  { id: "pg-discounts", label: "Discounts", section: S.billing, module: "fees", target: { tab: "discounts" }, perm: ["discounts.manage"], icon: Percent, keywords: ["scholarship", "bursary", "subsidy", "waiver"] },
  { id: "pg-arrears", label: "Arrears Chase-List", section: S.billing, module: "fees", target: { tab: "arrears" }, perm: ["fees.view"], icon: FileWarning, keywords: ["owing", "debtors", "balance", "chase"] },
  { id: "pg-credits", label: "Student Credits", section: S.billing, module: "fees", target: { tab: "credits" }, perm: ["fees.view"], icon: Wallet, keywords: ["prepaid", "advance", "topup", "wallet"] },
  { id: "pg-mod-approvals", label: "Modification Approvals", section: S.billing, module: "fees", target: { tab: "approvals" }, perm: ["fees.view"], icon: Scale, keywords: ["edit receipt", "amend", "level 1"] },

  // ── Collections & Cashier ──
  { id: "pg-collect", label: "Fee Collection Portal", section: S.cashier, module: "cashier", target: { tab: "collect" }, perm: ["fees.collect", "daily.collect"], icon: Receipt, keywords: ["pos", "take payment", "counter", "collect fees", "cashier portal"] },
  { id: "pg-cashier-dash", label: "Collections Overview", section: S.cashier, module: "cashier", target: { tab: "portal" }, perm: ["cashier.reports"], icon: BarChart3, keywords: ["takings", "kpi", "today"] },
  { id: "pg-my-collections", label: "My Collections", section: S.cashier, module: "cashier", target: { tab: "collections" }, perm: ["cashier.reports"], icon: HandCoins, keywords: ["my receipts", "report", "csv"] },
  { id: "pg-daily-summary", label: "Daily Summary", section: S.cashier, module: "cashier", target: { tab: "summary" }, perm: ["cashier.reports"], icon: ClipboardList, keywords: ["handover", "close of day", "sign off"] },
  { id: "pg-cashier-receipts", label: "Print Receipts", section: S.cashier, module: "cashier", target: { tab: "receipts" }, perm: ["cashier.reports"], icon: Printer, keywords: ["reprint", "80mm", "thermal"] },
  { id: "pg-student-ledger", label: "Student Ledger", section: S.cashier, module: "cashier", target: { tab: "ledger" }, perm: ["cashier.reports"], icon: ListChecks, keywords: ["statement", "history", "transactions"] },
  { id: "pg-momo", label: "MoMo & Payment Links", section: S.cashier, module: "momo", perm: ["payments.momo"], icon: SmartphoneNfc, keywords: ["hubtel", "paystack", "request", "link", "mtn", "telecel"] },
  { id: "pg-conductor", label: "Conductor Portal", section: S.cashier, module: "conductor", perm: ["transport.conduct"], icon: Route, keywords: ["bus", "boarding", "morning"] },

  // ── Daily Services & Transport ──
  { id: "pg-pos", label: "Daily Fees Counter", section: S.daily, module: "daily", target: { tab: "pos" }, perm: ["daily.collect"], icon: UtensilsCrossed, keywords: ["pos", "collect daily", "lunch money", "feeding", "cashier"] },
  { id: "pg-wallets", label: "Wallets & Top-Ups", section: S.daily, module: "daily", target: { tab: "wallets" }, perm: ["daily.view"], icon: Wallet, keywords: ["prepaid", "credit", "advance", "transport wallet"] },
  { id: "pg-daily-ledger", label: "Ledger & Arrears", section: S.daily, module: "daily", target: { tab: "ledger" }, perm: ["daily.view"], icon: ListChecks, keywords: ["owing", "outstanding", "debtors"] },
  { id: "pg-services", label: "Services & Enrolment", section: S.daily, module: "daily", target: { tab: "services" }, perm: ["daily.manage"], icon: UtensilsCrossed, keywords: ["lunch", "breakfast", "water", "extra classes", "configure"] },
  { id: "pg-meals", label: "Meals & Opt-Ins", section: S.daily, module: "daily", target: { tab: "meals" }, perm: ["daily.manage"], icon: Coffee, keywords: ["non-eater", "opt out", "skip", "dining", "approve"] },
  { id: "pg-rates", label: "Daily Fee Rates", section: S.daily, module: "daily", target: { tab: "rates" }, perm: ["daily.manage"], icon: HandCoins, keywords: ["price", "class rate", "per class"] },
  { id: "pg-recon", label: "Reconciliation", section: S.daily, module: "daily", target: { tab: "recon" }, perm: ["daily.manage"], icon: BarChart3, keywords: ["collections vs charges", "audit"] },
  { id: "pg-trend", label: "14-Day Trend", section: S.daily, module: "daily", target: { tab: "trend" }, perm: ["daily.view"], icon: BarChart3, keywords: ["chart", "statistics"] },
  { id: "pg-transport", label: "Transport", section: S.daily, module: "transport", perm: ["transport.view"], icon: Bus, keywords: ["routes", "bus", "fleet", "vehicles", "assignment"] },
  { id: "pg-boarding", label: "Boarding", section: S.daily, module: "boarding", target: { tab: "houses" }, perm: ["boarding.view"], icon: BedDouble, keywords: ["dormitory", "hostel", "beds"] },

  // ── Academics & Exams ──
  { id: "pg-mark", label: "Mark Attendance", section: S.academic, module: "attendance", target: { tab: "register" }, perm: ["attendance.take"], icon: CalendarCheck, keywords: ["register", "roll call", "present", "absent"] },
  { id: "pg-att-analytics", label: "Attendance Analytics", section: S.academic, module: "attendance", target: { tab: "analytics" }, perm: ["attendance.view"], icon: BarChart3, keywords: ["trends", "dashboard", "sick"] },
  { id: "pg-att-monthly", label: "Monthly Register", section: S.academic, module: "attendance", target: { tab: "monthly" }, perm: ["attendance.view"], icon: CalendarDays, keywords: ["grid", "month", "report"] },
  { id: "pg-att-scanner", label: "Barcode Scanner", section: S.academic, module: "attendance", target: { tab: "scanner" }, perm: ["attendance.view"], icon: ScanLine, keywords: ["camera", "qr", "id card"] },
  { id: "pg-classes", label: "Classes & Timetable", section: S.academic, module: "classes", perm: ["classes.view"], icon: BookOpen, keywords: ["streams", "sections", "periods"] },
  { id: "pg-marks", label: "Enter Marks", section: S.academic, module: "gradebook", target: { tab: "assessments" }, perm: ["gradebook.enter"], icon: ClipboardList, keywords: ["gradebook", "scores", "assessment"] },
  { id: "pg-portfolio", label: "Portfolio Assessment", section: S.academic, module: "gradebook", target: { tab: "portfolio" }, perm: ["gradebook.enter"], icon: GalleryHorizontal, keywords: ["project", "rubric"] },
  { id: "pg-sba", label: "SBA & Basal", section: S.academic, module: "gradebook", target: { tab: "sba" }, perm: ["gradebook.enter"], icon: Layers, keywords: ["basal", "creche", "continous"] },
  { id: "pg-creche", label: "Early Years Marks", section: S.academic, module: "gradebook", target: { tab: "creche" }, perm: ["gradebook.enter"], icon: HeartPulse, keywords: ["kindergarten", "kg", "nursery", "montessori"] },
  { id: "pg-gb-analytics", label: "Gradebook Analytics", section: S.academic, module: "gradebook", target: { tab: "analytics" }, perm: ["gradebook.view"], icon: BarChart3, keywords: ["subject performance", "class average"] },
  { id: "pg-notes", label: "Lesson Notes", section: S.academic, module: "curriculum", target: { tab: "notes" }, perm: ["curriculum.manage"], icon: NotebookPen, keywords: ["ges", "strand", "indicator", "scheme of learning", "weekly notes"] },
  { id: "pg-note-review", label: "Review Lesson Notes", section: S.academic, module: "curriculum", target: { tab: "review" }, perm: ["curriculum.review"], icon: BadgeCheck, keywords: ["endorse", "hod", "return"] },
  { id: "pg-templates", label: "Note Templates", section: S.academic, module: "curriculum", target: { tab: "templates" }, perm: ["curriculum.manage"], icon: FileSpreadsheet, keywords: ["reusable", "format"] },
  { id: "pg-syllabus", label: "Scheme of Learning", section: S.academic, module: "curriculum", target: { tab: "syllabus" }, perm: ["curriculum.view"], icon: BookMarked, keywords: ["syllabus", "topics", "week"] },
  { id: "pg-materials", label: "Study Materials", section: S.academic, module: "curriculum", target: { tab: "materials" }, perm: ["curriculum.view"], icon: BookOpen, keywords: ["uploads", "documents", "resources"] },
  { id: "pg-completion", label: "Completion Dashboard", section: S.academic, module: "curriculum", target: { tab: "completion" }, perm: ["curriculum.view"], icon: Timer, keywords: ["coverage", " pace", "progress"] },
  { id: "pg-framework", label: "Curriculum Framework", section: S.academic, module: "curriculum", target: { tab: "framework" }, perm: ["curriculum.view"], icon: Network, keywords: ["strands", "sub strands", "content standards", "indicators", "nacca", "cambridge", "ib"] },
  { id: "pg-reports", label: "Terminal Reports", section: S.academic, module: "reports", target: { tab: "cards" }, perm: ["reports.view"], icon: GraduationCap, keywords: ["report card", "terminal", "bece"] },
  { id: "pg-cumulative", label: "Cumulative Records", section: S.academic, module: "reports", target: { tab: "cumulative" }, perm: ["reports.view"], icon: ScrollText, keywords: ["cum record", "history"] },
  { id: "pg-papers", label: "BECE Papers", section: S.academic, module: "reports", target: { tab: "papers" }, perm: ["reports.view"], icon: Flag, keywords: ["bece", "mock", "questions"] },
  { id: "pg-exams", label: "Online Exams", section: S.academic, module: "exams", perm: ["exams.online"], icon: Laptop, keywords: ["quiz", "test", "cbt"] },
  { id: "pg-library", label: "Library", section: S.academic, module: "library", perm: ["library.view"], icon: BookOpen, keywords: ["books", "catalogue", "borrow", "return"] },

  // ── Finance & Communication ──
  { id: "pg-finance", label: "Finance Analytics", section: S.finance, module: "finance", target: { tab: "income" }, perm: ["finance.view"], icon: PieChart, keywords: ["income", "expenditure", "charts"] },
  { id: "pg-income", label: "Income Statements", section: S.finance, module: "finance", target: { tab: "income" }, perm: ["finance.view"], icon: Bank, keywords: ["revenue", "streams"] },
  { id: "pg-cashier-recon", label: "Cashier Reconciliation", section: S.finance, module: "finance", target: { tab: "cashier" }, perm: ["finance.view"], icon: Scale, keywords: ["variance", "cash vs momo"] },
  { id: "pg-gl", label: "General Ledger", section: S.finance, module: "finance", target: { tab: "ledger" }, perm: ["finance.view"], icon: ListChecks, keywords: ["journal", "entries"] },
  { id: "pg-aging", label: "Receivables Aging", section: S.finance, module: "finance", target: { tab: "aging" }, perm: ["finance.view"], icon: Timer, keywords: ["debt", "buckets", "30 60 90"] },
  { id: "pg-bills", label: "Bills & Utilities", section: S.finance, module: "finance", target: { tab: "bills" }, perm: ["finance.view"], icon: ShoppingBasket, keywords: ["utilities", "invoices to pay"] },
  { id: "pg-expenditure", label: "Expenditure", section: S.finance, module: "finance", target: { tab: "expenditure" }, perm: ["finance.manage"], icon: Banknote, keywords: ["expenses", "spending", "petty"] },
  { id: "pg-payroll", label: "Payroll Runs", section: S.finance, module: "payroll", target: { tab: "runs" }, perm: ["payroll.view"], icon: Calculator, keywords: ["salaries", "wages", "payslip"] },
  { id: "pg-payroll-staff", label: "Staff Payroll", section: S.finance, module: "payroll", target: { tab: "staff" }, perm: ["payroll.view"], icon: Users, keywords: ["per staff", "history"] },
  { id: "pg-ssnit", label: "SSNIT", section: S.finance, module: "payroll", target: { tab: "ssnit" }, perm: ["payroll.view"], icon: ShieldCheck, keywords: ["tier 1", "tier 2", "pension", "social security"] },
  { id: "pg-statutory", label: "Statutory Returns", section: S.finance, module: "payroll", target: { tab: "statutory" }, perm: ["payroll.view"], icon: FileSpreadsheet, keywords: ["gra", "paye", "returns"] },
  { id: "pg-inventory", label: "Store & Inventory", section: S.finance, module: "inventory", target: { tab: "items" }, perm: ["inventory.view"], icon: Package, keywords: ["stock", "items", "tuck"] },
  { id: "pg-store-pos", label: "Store POS", section: S.finance, module: "inventory", target: { tab: "pos" }, perm: ["inventory.pos"], icon: ShoppingBasket, keywords: ["sell", "tuck shop", "sales"] },
  { id: "pg-low-stock", label: "Low Stock", section: S.finance, module: "inventory", target: { tab: "low" }, perm: ["inventory.view"], icon: Wrench, keywords: ["reorder", "minimum"] },
  { id: "pg-movements", label: "Stock Movements", section: S.finance, module: "inventory", target: { tab: "movements" }, perm: ["inventory.view"], icon: Rows3, keywords: ["in", "out", "adjust"] },
  { id: "pg-returns", label: "Store Returns", section: S.finance, module: "inventory", target: { tab: "returns" }, perm: ["inventory.view"], icon: Sparkles, keywords: ["refund", "damage"] },
  { id: "pg-orders", label: "Purchase Orders", section: S.finance, module: "inventory", target: { tab: "orders" }, perm: ["inventory.manage"], icon: FileSpreadsheet, keywords: ["procurement", "po"] },
  { id: "pg-suppliers", label: "Suppliers", section: S.finance, module: "inventory", target: { tab: "suppliers" }, perm: ["inventory.manage"], icon: Trees, keywords: ["vendors", "wholesale"] },
  { id: "pg-sms", label: "SMS Centre", section: S.finance, module: "sms", perm: ["sms.view"], icon: Smartphone, keywords: ["text", "message", "compose", "bulk"] },
  { id: "pg-sms-auto", label: "SMS Automation", section: S.finance, module: "sms", target: { tab: "automations" }, perm: ["sms.send"], icon: MessageSquareText, keywords: ["triggers", "birthday", "arrears reminder"] },
  { id: "pg-ai", label: "AI Assistant", section: S.finance, module: "ai", perm: ["ai.use"], icon: BrainCircuit, keywords: ["chat", "help", "glm"] },

  // ── System & Administration ──
  { id: "pg-set-profile", label: "School Profile", section: S.system, module: "settings", target: { tab: "profile" }, perm: ["settings.manage"], icon: SettingsIcon, keywords: ["name", "logo", "curricula", "ges cambridge ib"] },
  { id: "pg-set-system", label: "System Settings", section: S.system, module: "settings", target: { tab: "system" }, perm: ["settings.manage"], icon: SettingsIcon, keywords: ["currency", "receipts", "defaults"] },
  { id: "pg-set-branding", label: "Branding & Logos", section: S.system, module: "settings", target: { tab: "branding" }, perm: ["settings.manage"], icon: Sparkles, keywords: ["logo", "signature", "ssnit logo", "kente"] },
  { id: "pg-set-theme", label: "Theme & Colours", section: S.system, module: "settings", target: { tab: "theme" }, perm: ["settings.theme"], icon: Palette, keywords: ["theme", "colour scheme", "primary color", "secondary", "accent", "skin", "branding colors", "app_theme", "school colors"] },
  { id: "pg-set-classes", label: "Classrooms", section: S.system, module: "settings", target: { tab: "classes" }, perm: ["settings.manage"], icon: BookOpen, keywords: ["streams", "sections", "levels"] },
  { id: "pg-set-subjects", label: "Subjects", section: S.system, module: "settings", target: { tab: "subjects" }, perm: ["settings.manage"], icon: BookMarked, keywords: ["courses", "maths", "english"] },
  { id: "pg-set-calendar", label: "Calendar & Terms", section: S.system, module: "settings", target: { tab: "calendar" }, perm: ["settings.manage"], icon: CalendarDays, keywords: ["terms", "sessions", "vacation"] },
  { id: "pg-set-grading", label: "Grading Scale", section: S.system, module: "settings", target: { tab: "grading" }, perm: ["settings.manage"], icon: Scale, keywords: ["grades", "1-9", "wassce"] },
  { id: "pg-set-statutory", label: "Statutory Rates", section: S.system, module: "settings", target: { tab: "statutory" }, perm: ["settings.manage"], icon: ShieldCheck, keywords: ["ssnit", "paye", "tax"] },
  { id: "pg-set-staff", label: "Staff & Users Setup", section: S.system, module: "settings", target: { tab: "staff" }, perm: ["settings.manage"], icon: Users, keywords: ["teachers", "employees"] },
  { id: "pg-set-earlyyears", label: "Early Years Setup", section: S.system, module: "settings", target: { tab: "earlyyears" }, perm: ["settings.manage"], icon: HeartPulse, keywords: ["creche", "montessori", "baseline"] },
  { id: "pg-set-sba", label: "SBA Weights", section: S.system, module: "settings", target: { tab: "sba" }, perm: ["settings.manage"], icon: Layers, keywords: ["assessment weights", "breakdown"] },
  { id: "pg-set-waec", label: "WAEC & BECE Setup", section: S.system, module: "settings", target: { tab: "waec" }, perm: ["settings.manage"], icon: Flag, keywords: ["bece", "external exam"] },

  // ── Self-service portals ──
  { id: "pg-portal", label: "Parent Portal", section: "Self-Service", module: "portal", roles: ["PARENT"], icon: Users, keywords: ["my children", "fees", "guardian"] },
  { id: "pg-campus", label: "Learner Portal", section: "Self-Service", module: "campus", roles: ["STUDENT"], icon: Armchair, keywords: ["my results", "homework"] },
];

/** Section order for the A–Z browse + search-result grouping. */
export const PAGE_SECTIONS = [S.quick, S.people, S.billing, S.cashier, S.daily, S.academic, S.finance, S.system, "Self-Service"];

/** Human label for a module id (used in page rows: "Fees · Bill Items"). */
export const MODULE_LABELS: Record<string, string> = {
  dashboard: "Dashboard", students: "Students", admissions: "Admissions", fees: "Fees", momo: "MoMo",
  daily: "Daily Services", cashier: "Cashier", conductor: "Conductor", transport: "Transport",
  attendance: "Attendance", classes: "Classes", gradebook: "Gradebook", curriculum: "Curriculum",
  reports: "Reports", exams: "Exams", library: "Library", boarding: "Boarding", payroll: "Payroll",
  inventory: "Store", finance: "Finance", sms: "SMS", ai: "AI", settings: "Settings", rbac: "Access",
  staffhub: "Staff Hub", portal: "Parent Portal", campus: "Learner Portal", branches: "Branches",
  association: "Association",
};

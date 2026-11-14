// ─────────────────────────────────────────────────────────────
// RBAC — role levels & permission-based access (iEduPro parity)
//
// Admin ladder (iEduPro CI3 "admin levels"):
//   Level 1  SUPER_ADMIN     — system owner, manages users & permissions
//   Level 2  SCHOOL_ADMIN    — school administrator (head of school)
//   Level 3  ACCOUNTANT      — bursar / finance office
//   Level 4  CASHIER         — front-desk collections
//   Level 5  BUS_CONDUCTOR   — transport operations
// Staff & community:
//   TEACHER · NON_TEACHING · LIBRARIAN · PARENT · STUDENT
// Network: GROUP_OWNER · ASSOCIATION_* (portal, unchanged)
// ─────────────────────────────────────────────────────────────

export type PermDef = { id: string; label: string; group: string };

export const PERMISSIONS: PermDef[] = [
  // People
  { id: "students.view", label: "View students & profiles", group: "People" },
  { id: "students.manage", label: "Add / edit students", group: "People" },
  { id: "admissions.manage", label: "Admissions, transfers & billing wizard", group: "People" },
  { id: "staff.view", label: "View staff directory", group: "People" },
  { id: "staff.manage", label: "Manage staff records", group: "People" },
  // Fees & money
  { id: "fees.view", label: "View invoices & ledgers", group: "Fees & Finance" },
  { id: "fees.manage", label: "Create / modify invoices & write-offs", group: "Fees & Finance" },
  { id: "fees.collect", label: "Take payments & issue receipts", group: "Fees & Finance" },
  { id: "payments.momo", label: "MoMo requests & payment links (Hubtel)", group: "Fees & Finance" },
  { id: "discounts.manage", label: "Discount profiles, applications & approvals", group: "Fees & Finance" },
  { id: "cashier.reports", label: "Cashier console & collection reports", group: "Fees & Finance" },
  // Daily services
  { id: "daily.view", label: "View daily services & tuck", group: "Daily Services" },
  { id: "daily.collect", label: "Collect daily fees (classroom / cashier)", group: "Daily Services" },
  { id: "daily.manage", label: "Configure services, rates, waivers & wallets", group: "Daily Services" },
  // Transport
  { id: "transport.view", label: "View routes & assignments", group: "Transport" },
  { id: "transport.conduct", label: "Record boarding, trips & fare collection", group: "Transport" },
  { id: "transport.manage", label: "Manage routes, vehicles & assignments", group: "Transport" },
  // Payroll & HR
  { id: "payroll.view", label: "View payroll & payslips", group: "Payroll & HR" },
  { id: "payroll.process", label: "Run payroll & pay salaries", group: "Payroll & HR" },
  // Inventory
  { id: "inventory.view", label: "View inventory & stores", group: "Inventory" },
  { id: "inventory.manage", label: "Manage products, suppliers & stock", group: "Inventory" },
  { id: "inventory.pos", label: "Point of sale (POS)", group: "Inventory" },
  // Finance analytics
  { id: "finance.view", label: "Finance dashboards & analytics", group: "Fees & Finance" },
  { id: "finance.manage", label: "Expenses & purchase orders", group: "Fees & Finance" },
  // Double-entry accounts (CI3 Accounts.php parity)
  { id: "accounts.view", label: "View chart of accounts & ledgers", group: "Fees & Finance" },
  { id: "accounts.manage", label: "Journal entries, bank accounts & reconciliation", group: "Fees & Finance" },
  // Budget management & fiscal years (CI3 Budget_management.php parity, wave 19)
  { id: "budget.view", label: "View budgets & fiscal years", group: "Fees & Finance" },
  { id: "budget.manage", label: "Budgets, budget lines & fiscal years", group: "Fees & Finance" },
  // Academic
  { id: "attendance.view", label: "View attendance", group: "Academic" },
  { id: "attendance.take", label: "Take attendance", group: "Academic" },
  { id: "classes.view", label: "View classes & timetable", group: "Academic" },
  { id: "classes.manage", label: "Manage classes & timetable", group: "Academic" },
  { id: "gradebook.view", label: "View gradebook", group: "Academic" },
  { id: "gradebook.enter", label: "Enter marks & assessments", group: "Academic" },
  { id: "curriculum.view", label: "View curriculum, lesson notes & materials", group: "Academic" },
  { id: "curriculum.manage", label: "Create lesson notes, templates, syllabus & materials", group: "Academic" },
  { id: "curriculum.review", label: "Endorse or return lesson notes (HOD / head)", group: "Academic" },
  { id: "reports.view", label: "View terminal reports", group: "Academic" },
  { id: "reports.publish", label: "Generate & publish reports", group: "Academic" },
  { id: "exams.online", label: "Online exams — create, publish & mark", group: "Academic" },
  // Discipline & conduct (wave 25 — iEduPro discipline parity)
  { id: "discipline.view", label: "View conduct register & commendations", group: "Academic" },
  { id: "discipline.manage", label: "Record incidents, commendations & resolutions", group: "Academic" },
  // Health & wellness clinic (wave 26 — sick bay & SHEP screenings)
  { id: "health.view", label: "View sick-bay register & screenings", group: "Health & Welfare" },
  { id: "health.manage", label: "Record clinic visits, screenings & guardian notices", group: "Health & Welfare" },
  // Clubs & societies (wave 27 — the co-curricular register)
  { id: "clubs.view", label: "View club register, rosters & events", group: "Academic" },
  { id: "clubs.manage", label: "Manage clubs, memberships, meetings & events", group: "Academic" },
  // Staff attendance & duty (wave 28 — the staff movement book)
  { id: "staffduty.view", label: "View staff attendance, duty roster & movement book", group: "Staff Operations" },
  { id: "staffduty.manage", label: "Mark staff register, assign duty, approve leave & record cover", group: "Staff Operations" },
  // Front office (wave 29 — the front desk books)
  { id: "frontoffice.view", label: "View visitors book, appointments, call log, postal register & events", group: "Front Office" },
  { id: "frontoffice.manage", label: "Book visitors & appointments, log calls, register mail & publish events", group: "Front Office" },
  // Communication
  { id: "sms.view", label: "View SMS centre", group: "Communication" },
  { id: "sms.send", label: "Send SMS & manage automations", group: "Communication" },
  { id: "email.send", label: "Send email & manage the email channel", group: "Communication" },
  { id: "messages.use", label: "Internal messaging (staff inbox)", group: "Communication" },
  // Library
  { id: "library.view", label: "View library catalogue", group: "Library" },
  { id: "library.issue", label: "Issue & return books", group: "Library" },
  { id: "library.manage", label: "Manage catalogue & members", group: "Library" },
  // Boarding
  { id: "boarding.view", label: "View boarding houses & occupancy", group: "Boarding" },
  { id: "boarding.manage", label: "Manage houses, dormitories, beds & assignment", group: "Boarding" },
  // Approvals (receipt/invoice modification queue)
  { id: "approvals.request", label: "Request receipt/invoice modifications", group: "Fees & Finance" },
  { id: "approvals.manage", label: "Approve or reject modification requests", group: "System" },
  // System
  { id: "settings.manage", label: "School & statutory settings", group: "System" },
  { id: "settings.theme", label: "Set the school colour scheme (Theme tab)", group: "System" },
  { id: "backup.manage", label: "Database backup & restore", group: "System" },
  { id: "rbac.manage", label: "Manage users, roles & permissions", group: "System" },
  { id: "ai.use", label: "AI assistant", group: "System" },
  // Self-service portals
  { id: "staffhub.self", label: "Staff self-service portal", group: "Self-service" },
  { id: "portal.self", label: "Parent portal (own children)", group: "Self-service" },
  { id: "campus.self", label: "Student portal (own record)", group: "Self-service" },
];

export const ALL_PERM_IDS = PERMISSIONS.map((p) => p.id);

// iEduPro-style admin ladder. Level 0 = proprietor (above the ladder),
// null = non-admin roles (staff / community / association).
export const ROLE_LEVELS: Record<string, number> = {
  GROUP_OWNER: 0,
  SUPER_ADMIN: 1,
  SCHOOL_ADMIN: 2,
  ACCOUNTANT: 3,
  CASHIER: 4,
  BUS_CONDUCTOR: 5,
};

export const ROLE_LEVEL_LABELS: Record<number, string> = {
  0: "Proprietor",
  1: "Level 1 · Super Admin",
  2: "Level 2 · Admin",
  3: "Level 3 · Accountant",
  4: "Level 4 · Cashier",
  5: "Level 5 · Bus Conductor",
};

const minus = (ids: string[], drop: string[]) => ids.filter((x) => !drop.includes(x));

// Role → default permission set. "*" = everything.
export const ROLE_DEFAULTS: Record<string, string[] | "*"> = {
  SUPER_ADMIN: minus(ALL_PERM_IDS, ["portal.self", "campus.self"]),
  GROUP_OWNER: minus(ALL_PERM_IDS, ["portal.self", "campus.self"]),
  // iEduPro: only level-1 admins adjudicate receipt/invoice modifications;
  // the school colour scheme is the proprietor/super-admin's call, not every
  // head of school (per the user: "customizable by the school's super admin").
  // Backup/restore can wipe the whole school database — same restriction.
  SCHOOL_ADMIN: minus(ALL_PERM_IDS, ["rbac.manage", "approvals.manage", "settings.theme", "backup.manage", "portal.self", "campus.self"]),
  ACCOUNTANT: [
    "students.view", "admissions.manage",
    "fees.view", "fees.manage", "fees.collect", "payments.momo", "discounts.manage", "cashier.reports",
    "daily.view", "daily.collect", "daily.manage",
    "transport.view", "transport.manage",
    "payroll.view", "payroll.process",
    "inventory.view", "inventory.manage", "inventory.pos",
    "finance.view", "finance.manage",
    "accounts.view", "accounts.manage",
    "budget.view", "budget.manage",
    "boarding.view",
    "sms.view", "sms.send", "email.send", "messages.use",
    "settings.manage", "ai.use", "staffhub.self",
    "staffduty.view",
    "frontoffice.view", "frontoffice.manage",
  ],
  CASHIER: [
    "students.view", "fees.view", "fees.collect", "payments.momo", "cashier.reports", "approvals.request",
    "daily.view", "daily.collect",
    "transport.view", "transport.conduct", "messages.use",
  ],
  BUS_CONDUCTOR: [
    "students.view", "transport.view", "transport.conduct", "daily.view", "staffhub.self", "messages.use",
  ],
  TEACHER: [
    "students.view", "students.manage",
    "attendance.view", "attendance.take",
    "discipline.view", "discipline.manage",
    "health.view", "health.manage",
    "clubs.view", "clubs.manage",
    "classes.view", "classes.manage",
    "gradebook.view", "gradebook.enter",
    "curriculum.view", "curriculum.manage",
    "reports.view", "reports.publish", "exams.online",
    "sms.view", "sms.send", "messages.use",
    "daily.view", "daily.collect",
    "staffduty.view",
    "ai.use", "staffhub.self",
  ],
  // non-teaching staff often man the front desk (secretary / receptionist)
  NON_TEACHING: ["staffhub.self", "messages.use", "frontoffice.view", "frontoffice.manage"],
  LIBRARIAN: ["students.view", "library.view", "library.issue", "library.manage", "messages.use"],
  PARENT: ["portal.self"],
  STUDENT: ["campus.self"],
  // Association network roles use their own portal — no school permissions.
  ASSOCIATION_NATIONAL: [],
  ASSOCIATION_REGIONAL: [],
  ASSOCIATION_DISTRICT: [],
  ASSOCIATION_ZONAL: [],
};

export type PermOverrides = { extra?: string[]; denied?: string[] };

/** Parse the overrides JSON stored on the User row. */
export function parseOverrides(json?: string | null): PermOverrides {
  if (!json) return {};
  try {
    const v = JSON.parse(json);
    return { extra: Array.isArray(v.extra) ? v.extra : [], denied: Array.isArray(v.denied) ? v.denied : [] };
  } catch {
    return {};
  }
}

/** Parse the permissions JSON stored on a RoleConfig row. */
export function parseRolePerms(json?: string | null): string[] | null {
  if (!json) return null;
  try {
    const v = JSON.parse(json);
    return Array.isArray(v) ? v.filter((x) => typeof x === "string") : null;
  } catch {
    return null;
  }
}

/** Action permissions imply their view permission (e.g. granting "enter marks"
 *  also shows the Gradebook module). Explicit denials still win over implications. */
export const PERM_IMPLIES: Record<string, string[]> = {
  "students.manage": ["students.view"],
  "admissions.manage": ["students.view"],
  "staff.manage": ["staff.view"],
  "fees.manage": ["fees.view"],
  "fees.collect": ["fees.view"],
  "discounts.manage": ["fees.view"],
  "daily.collect": ["daily.view"],
  "daily.manage": ["daily.view"],
  "transport.conduct": ["transport.view"],
  "transport.manage": ["transport.view"],
  "payroll.process": ["payroll.view"],
  "inventory.manage": ["inventory.view"],
  "inventory.pos": ["inventory.view"],
  "finance.manage": ["finance.view"],
  "accounts.manage": ["accounts.view"],
  "budget.manage": ["budget.view"],
  "attendance.take": ["attendance.view"],
  "classes.manage": ["classes.view"],
  "gradebook.enter": ["gradebook.view"],
  "curriculum.manage": ["curriculum.view"],
  "curriculum.review": ["curriculum.view"],
  "reports.publish": ["reports.view"],
  "discipline.manage": ["discipline.view"],
  "health.manage": ["health.view"],
  "clubs.manage": ["clubs.view"],
  "staffduty.manage": ["staffduty.view"],
  "frontoffice.manage": ["frontoffice.view"],
  "sms.send": ["sms.view"],
  "payments.momo": ["fees.view"],
  "library.issue": ["library.view"],
  "library.manage": ["library.view"],
};

/** Effective permission list for a user. Resolution order (wave 10):
 *  1. BASE — the school's RoleConfig for this role when one is active
 *     (`configPerms`, which may be an explicit empty list = this role may do
 *     nothing at this school), else the code ROLE_DEFAULTS.
 *  2. EXPAND — action ⇒ view implications, unless the view is denied.
 *  3. OVERRIDES — per-user extra grants and denials always win last. */
export function effectivePermissions(role: string, overridesJson?: string | null, configPerms?: string[] | null): string[] {
  const configBase = Array.isArray(configPerms) ? configPerms : null;
  const base = configBase ?? (ROLE_DEFAULTS[role] ?? []);
  const { extra = [], denied = [] } = parseOverrides(overridesJson);
  const baseList = base === "*" ? ALL_PERM_IDS : base;
  const deniedSet = new Set(denied);
  const set = new Set([...baseList, ...extra]);
  // expand implications (action ⇒ view) — unless the view is explicitly denied
  for (const p of Array.from(set)) {
    for (const impl of PERM_IMPLIES[p] ?? []) {
      if (!deniedSet.has(impl)) set.add(impl);
    }
  }
  deniedSet.forEach((d) => set.delete(d));
  return ALL_PERM_IDS.filter((p) => set.has(p)); // stable catalogue order
}

/** The base permission list a role starts from at this school — the RoleConfig
 *  set when active, otherwise the code defaults. Used by the per-user matrix
 *  editor so it shows the school's actual role base, not the hardcoded one. */
export function roleBasePerms(role: string, configPerms?: string[] | null): string[] {
  const configBase = Array.isArray(configPerms) ? configPerms : null;
  const base = configBase ?? (ROLE_DEFAULTS[role] ?? []);
  return base === "*" ? ALL_PERM_IDS : [...base];
}

/** Landing module for a role — config override, then the static map. */
export function landingFor(role: string, configLanding?: string | null): string {
  return configLanding ?? LANDING_MODULE[role] ?? "dashboard";
}

export const can = (perms: string[] | undefined | null, perm: string) => !!perms && perms.includes(perm);
export const canAny = (perms: string[] | undefined | null, need: string[]) => !!perms && need.some((p) => perms.includes(p));

// ── Portal routing ───────────────────────────────────────────
export const LANDING_MODULE: Record<string, string> = {
  SUPER_ADMIN: "dashboard",
  GROUP_OWNER: "dashboard",
  SCHOOL_ADMIN: "dashboard",
  ACCOUNTANT: "dashboard",
  TEACHER: "dashboard",
  CASHIER: "cashier",
  BUS_CONDUCTOR: "conductor",
  LIBRARIAN: "library",
  NON_TEACHING: "staffhub",
  PARENT: "portal",
  STUDENT: "campus",
};

/** Back-office roles that see the school dashboard (not portal-only roles). */
export const DASHBOARD_ROLES = ["SUPER_ADMIN", "GROUP_OWNER", "SCHOOL_ADMIN", "ACCOUNTANT", "TEACHER"];

/** Module → any-of permission list required to see it in the sidebar. */
export const MODULE_PERMS: Record<string, string[]> = {
  students: ["students.view"],
  admissions: ["admissions.manage"],
  fees: ["fees.view"],
  momo: ["payments.momo"],
  daily: ["daily.view"],
  transport: ["transport.view"],
  payroll: ["payroll.view"],
  inventory: ["inventory.view"],
  finance: ["finance.view"],
  attendance: ["attendance.view"],
  classes: ["classes.view"],
  gradebook: ["gradebook.view"],
  results: ["reports.view"],
  discipline: ["discipline.view"],
  health: ["health.view"],
  clubs: ["clubs.view"],
  staffduty: ["staffduty.view"],
  frontoffice: ["frontoffice.view"],
  curriculum: ["curriculum.view"],
  reports: ["reports.view"],
  exams: ["exams.online"],
  sms: ["sms.view"],
  accounts: ["accounts.view"],
  budget: ["budget.view"],
  messages: ["messages.use"],
  ai: ["ai.use"],
  settings: ["settings.manage"],
  library: ["library.view"],
  boarding: ["boarding.view"],
  rbac: ["rbac.manage"],
  cashier: ["cashier.reports", "fees.collect", "daily.collect"],
  conductor: ["transport.conduct"],
  staffhub: ["staffhub.self"],
  portal: ["portal.self"],
  campus: ["campus.self"],
};

/** Can this session open this module? (association & branches are special-cased in the shell) */
export function canModule(role: string | undefined | null, perms: string[] | undefined | null, m: string): boolean {
  if (!role) return false;
  if (role.startsWith("ASSOCIATION_")) return m === "association";
  if (m === "association") return false;
  if (m === "dashboard") return DASHBOARD_ROLES.includes(role);
  if (m === "branches") return role === "GROUP_OWNER";
  if (m === "portal") return role === "PARENT"; // self-service portals are per-role
  if (m === "campus") return role === "STUDENT";
  const need = MODULE_PERMS[m];
  if (!need) return true;
  return canAny(perms, need);
}

/** Role used for module gating: custom backoffice roles (wave 10) behave like
 *  SCHOOL_ADMIN for the hard role gates (dashboard yes; portal/campus no) —
 *  every other module is still gated by the permission list. */
export function gateRole(session: { role: string; roleBackoffice?: boolean } | null | undefined): string | null {
  if (!session) return null;
  if (session.roleBackoffice && !DASHBOARD_ROLES.includes(session.role) && !session.role.startsWith("ASSOCIATION_")) {
    return "SCHOOL_ADMIN";
  }
  return session.role;
}

/** Back-office roles may call school operations APIs (parents/students use the portal API only). */
export const BACKOFFICE_ROLES = [
  "SUPER_ADMIN", "GROUP_OWNER", "SCHOOL_ADMIN", "ACCOUNTANT", "CASHIER",
  "BUS_CONDUCTOR", "TEACHER", "LIBRARIAN", "NON_TEACHING",
];
export const isBackofficeRole = (role?: string | null) => !!role && (BACKOFFICE_ROLES.includes(role) || role.startsWith("ASSOCIATION_") || role === "SYSTEM_ADMIN");

/** School-side roles that see the school app shell (incl. self-service portals). */
export const SCHOOL_SIDED_ROLES = [...BACKOFFICE_ROLES, "PARENT", "STUDENT"];
export const isSchoolSided = (role?: string | null) => !!role && SCHOOL_SIDED_ROLES.includes(role);

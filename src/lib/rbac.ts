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
  // Academic
  { id: "attendance.view", label: "View attendance", group: "Academic" },
  { id: "attendance.take", label: "Take attendance", group: "Academic" },
  { id: "classes.view", label: "View classes & timetable", group: "Academic" },
  { id: "classes.manage", label: "Manage classes & timetable", group: "Academic" },
  { id: "gradebook.view", label: "View gradebook", group: "Academic" },
  { id: "gradebook.enter", label: "Enter marks & assessments", group: "Academic" },
  { id: "reports.view", label: "View terminal reports", group: "Academic" },
  { id: "reports.publish", label: "Generate & publish reports", group: "Academic" },
  // Communication
  { id: "sms.view", label: "View SMS centre", group: "Communication" },
  { id: "sms.send", label: "Send SMS & manage automations", group: "Communication" },
  // Library
  { id: "library.view", label: "View library catalogue", group: "Library" },
  { id: "library.issue", label: "Issue & return books", group: "Library" },
  { id: "library.manage", label: "Manage catalogue & members", group: "Library" },
  // System
  { id: "settings.manage", label: "School & statutory settings", group: "System" },
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
  SCHOOL_ADMIN: minus(ALL_PERM_IDS, ["rbac.manage", "portal.self", "campus.self"]),
  ACCOUNTANT: [
    "students.view", "admissions.manage",
    "fees.view", "fees.manage", "fees.collect", "discounts.manage", "cashier.reports",
    "daily.view", "daily.collect", "daily.manage",
    "transport.view", "transport.manage",
    "payroll.view", "payroll.process",
    "inventory.view", "inventory.manage", "inventory.pos",
    "finance.view", "finance.manage",
    "sms.view", "sms.send",
    "settings.manage", "ai.use", "staffhub.self",
  ],
  CASHIER: [
    "students.view", "fees.view", "fees.collect", "cashier.reports",
    "daily.view", "daily.collect",
    "transport.view", "transport.conduct",
  ],
  BUS_CONDUCTOR: [
    "students.view", "transport.view", "transport.conduct", "daily.view", "staffhub.self",
  ],
  TEACHER: [
    "students.view", "students.manage",
    "attendance.view", "attendance.take",
    "classes.view", "classes.manage",
    "gradebook.view", "gradebook.enter",
    "reports.view", "reports.publish",
    "sms.view", "sms.send",
    "daily.view", "daily.collect",
    "ai.use", "staffhub.self",
  ],
  NON_TEACHING: ["staffhub.self"],
  LIBRARIAN: ["students.view", "library.view", "library.issue", "library.manage"],
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
  "attendance.take": ["attendance.view"],
  "classes.manage": ["classes.view"],
  "gradebook.enter": ["gradebook.view"],
  "reports.publish": ["reports.view"],
  "sms.send": ["sms.view"],
  "library.issue": ["library.view"],
  "library.manage": ["library.view"],
};

/** Effective permission list for a user (role defaults + extra grants − denials,
 *  with action→view implications expanded so granted permissions are usable). */
export function effectivePermissions(role: string, overridesJson?: string | null): string[] {
  const base = ROLE_DEFAULTS[role] ?? [];
  const { extra = [], denied = [] } = parseOverrides(overridesJson);
  if (base === "*") {
    return minus([...ALL_PERM_IDS, ...extra], denied);
  }
  const deniedSet = new Set(denied);
  const set = new Set([...base, ...extra]);
  // expand implications (action ⇒ view) — unless the view is explicitly denied
  for (const p of Array.from(set)) {
    for (const impl of PERM_IMPLIES[p] ?? []) {
      if (!deniedSet.has(impl)) set.add(impl);
    }
  }
  deniedSet.forEach((d) => set.delete(d));
  return ALL_PERM_IDS.filter((p) => set.has(p)); // stable catalogue order
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
  daily: ["daily.view"],
  transport: ["transport.view"],
  payroll: ["payroll.view"],
  inventory: ["inventory.view"],
  finance: ["finance.view"],
  attendance: ["attendance.view"],
  classes: ["classes.view"],
  gradebook: ["gradebook.view"],
  reports: ["reports.view"],
  sms: ["sms.view"],
  ai: ["ai.use"],
  settings: ["settings.manage"],
  library: ["library.view"],
  rbac: ["rbac.manage"],
  cashier: ["cashier.reports"],
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

/** Back-office roles may call school operations APIs (parents/students use the portal API only). */
export const BACKOFFICE_ROLES = [
  "SUPER_ADMIN", "GROUP_OWNER", "SCHOOL_ADMIN", "ACCOUNTANT", "CASHIER",
  "BUS_CONDUCTOR", "TEACHER", "LIBRARIAN", "NON_TEACHING",
];
export const isBackofficeRole = (role?: string | null) => !!role && (BACKOFFICE_ROLES.includes(role) || role.startsWith("ASSOCIATION_") || role === "SYSTEM_ADMIN");

/** School-side roles that see the school app shell (incl. self-service portals). */
export const SCHOOL_SIDED_ROLES = [...BACKOFFICE_ROLES, "PARENT", "STUDENT"];
export const isSchoolSided = (role?: string | null) => !!role && SCHOOL_SIDED_ROLES.includes(role);

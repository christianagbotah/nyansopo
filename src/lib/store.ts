"use client";
// ── Nyansapo global client store (Zustand + localStorage persistence) ──
import { create } from "zustand";
import { persist } from "zustand/middleware";

export type SessionUser = {
  id: string; email: string; fullName: string; phone: string | null;
  role: string; title: string | null;
  associationNodeId: string | null; schoolGroupId: string | null; schoolId: string | null;
  permissions: string[]; // effective RBAC permissions (role config + overrides)
  landingModule?: string;
  roleLabel?: string | null; // custom-role display label (wave 10)
  roleLevel?: number | null; // admin-ladder level (config or static)
  roleBackoffice?: boolean; // custom backoffice roles see the school shell
};

export type SchoolLite = {
  id: string; name: string; code: string; curriculum: string; category: string;
  region: string; town: string; groupId: string | null; isBranchMain: boolean;
  nasiaStatus: string; gnacopsStatus: string; studentsOnRoll: number; logoColor: string;
  phone: string | null; email: string | null;
};

export type QueuedOp = { clientId: string; kind: "payment" | "attendance" | "marks" | "student"; payload: any; label: string; queuedAt: number };

export type ModuleId =
  | "dashboard" | "students" | "admissions" | "fees" | "attendance" | "gradebook"
  | "reports" | "exams" | "branches" | "ai" | "association" | "settings"
  | "daily" | "transport" | "sms" | "payroll" | "inventory" | "finance" | "classes"
  | "library" | "rbac" | "cashier" | "conductor" | "staffhub" | "portal" | "campus"
  | "boarding" | "curriculum" | "momo";

/** Deep-link target within a module (iedupro nested navigation):
 *  tab — a named tab inside the module (e.g. daily → rates / recon)
 *  classroomId — a preselected class (e.g. Students → Basic 4 Gold) */
export type NavTarget = { tab?: string; classroomId?: string } | null;

type Store = {
  session: SessionUser | null;
  schools: SchoolLite[];
  currentSchoolId: string | null;
  screen: "landing" | "login" | "app";
  module: ModuleId;
  navTarget: NavTarget;
  queue: QueuedOp[];
  online: boolean;
  syncing: boolean;
  lastSyncAt: number | null;
  aiOpen: boolean;
  // actions
  setSession: (u: SessionUser | null) => void;
  setSchools: (s: SchoolLite[]) => void;
  setCurrentSchool: (id: string | null) => void;
  setScreen: (s: "landing" | "login" | "app") => void;
  setModule: (m: ModuleId, target?: NavTarget) => void;
  setOnline: (v: boolean) => void;
  setSyncing: (v: boolean) => void;
  setLastSync: (t: number | null) => void;
  enqueue: (op: QueuedOp) => void;
  clearQueue: () => void;
  removeFromQueue: (clientId: string) => void;
  setAiOpen: (v: boolean) => void;
  logout: () => void;
};

export const useStore = create<Store>()(
  persist(
    (set) => ({
      session: null,
      schools: [],
      currentSchoolId: null,
      screen: "landing",
      module: "dashboard",
      navTarget: null,
      queue: [],
      online: true,
      syncing: false,
      lastSyncAt: null,
      aiOpen: false,
      setSession: (u) => set({ session: u, screen: u ? "app" : "landing" }),
      setSchools: (s) => {
        set({ schools: s });
        set((st) => {
          if (s.length && (!st.currentSchoolId || !s.some((x) => x.id === st.currentSchoolId))) {
            return { currentSchoolId: s[0].id };
          }
          return {};
        });
      },
      setCurrentSchool: (id) => set({ currentSchoolId: id }),
      setScreen: (screen) => set({ screen }),
      setModule: (module, target) => set({ module, navTarget: target ?? null }),
      setOnline: (online) => set({ online }),
      setSyncing: (syncing) => set({ syncing }),
      setLastSync: (lastSyncAt) => set({ lastSyncAt }),
      enqueue: (op) => set((st) => ({ queue: [...st.queue, op] })),
      clearQueue: () => set({ queue: [] }),
      removeFromQueue: (clientId) => set((st) => ({ queue: st.queue.filter((q) => q.clientId !== clientId) })),
      setAiOpen: (aiOpen) => set({ aiOpen }),
      logout: () => set({ session: null, schools: [], currentSchoolId: null, screen: "landing", module: "dashboard", navTarget: null, queue: [] }),
    }),
    {
      name: "nyansapo-store",
      partialize: (st) => ({ session: st.session, currentSchoolId: st.currentSchoolId, module: st.module, queue: st.queue, lastSyncAt: st.lastSyncAt }),
    }
  )
);

export const ROLE_LABELS: Record<string, string> = {
  ASSOCIATION_NATIONAL: "National Executive",
  ASSOCIATION_REGIONAL: "Regional Council",
  ASSOCIATION_DISTRICT: "District Coordinator",
  ASSOCIATION_ZONAL: "Zonal Representative",
  GROUP_OWNER: "Proprietor",
  SUPER_ADMIN: "Super Admin · Level 1",
  SCHOOL_ADMIN: "Administrator · Level 2",
  ACCOUNTANT: "Accountant · Level 3",
  CASHIER: "Cashier · Level 4",
  BUS_CONDUCTOR: "Bus Conductor · Level 5",
  TEACHER: "Teacher",
  NON_TEACHING: "Non-Teaching Staff",
  LIBRARIAN: "Librarian",
  PARENT: "Parent / Guardian",
  STUDENT: "Learner",
};

export const isAssociationRole = (r?: string) => !!r && r.startsWith("ASSOCIATION_");
export const hasSchoolScope = (r?: string) => !!r && !r.startsWith("ASSOCIATION_");

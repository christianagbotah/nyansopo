"use client";
import { useEffect } from "react";
import { useStore } from "@/lib/store";
import { LANDING_MODULE } from "@/lib/rbac";
import { Landing } from "@/components/landing";
import { Login } from "@/components/login";
import { AppShell } from "@/components/app-shell";
import { apiGet } from "@/lib/api";

// every module a signed-in session can deep-link to (?module=…) — the app-shell
// guard still resets out-of-scope modules for the role, so this is safe
const DEEPLINK_MODULES = [
  "dashboard", "students", "admissions", "fees", "attendance", "gradebook",
  "reports", "exams", "branches", "ai", "association", "settings", "daily", "transport",
  "payroll", "inventory", "finance", "classes", "library", "rbac", "cashier",
  "conductor", "staffhub", "portal", "campus", "sms",
];

export default function Home() {
  const { session, screen, setSchools, setScreen, setModule } = useStore();

  // If a persisted session exists, re-hydrate school scope + deep link (?module=…&tab=…&class=…)
  useEffect(() => {
    if (!session) return;
    const params = new URLSearchParams(window.location.search);
    const deep = params.get("module");
    const tab = params.get("tab");
    const cls = params.get("class");
    apiGet<any>("/api/context")
      .then((ctx) => {
        setSchools(ctx.schools);
        const role = ctx.user.role;
        if (role.startsWith("ASSOCIATION_")) setModule("association");
        // land on this role's portal (RBAC landing map) unless a valid deep link
        // was requested — the app-shell guard still resets out-of-scope modules
        else if (!(deep && DEEPLINK_MODULES.includes(deep)))
          setModule(((useStore.getState().session?.landingModule as string) ?? LANDING_MODULE[role] ?? "dashboard") as any);
      })
      .catch(() => {
        // session stale — sign out
        useStore.getState().logout();
        setScreen("landing");
      });
    if (deep && DEEPLINK_MODULES.includes(deep)) {
      setModule(deep as any, (tab || cls) ? { tab: tab ?? undefined, classroomId: cls ?? undefined } : undefined);
    }
  }, [session?.id]);

  if (session) return <AppShell />;
  if (screen === "login") return <Login />;
  return <Landing />;
}

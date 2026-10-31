"use client";
import { useEffect } from "react";
import { useStore } from "@/lib/store";
import { Landing } from "@/components/landing";
import { Login } from "@/components/login";
import { AppShell } from "@/components/app-shell";
import { apiGet } from "@/lib/api";

export default function Home() {
  const { session, screen, setSchools, setScreen, setModule } = useStore();

  // If a persisted session exists, re-hydrate school scope + deep link (?module=)
  useEffect(() => {
    if (!session) return;
    apiGet<any>("/api/context")
      .then((ctx) => {
        setSchools(ctx.schools);
        const role = ctx.user.role;
        if (role.startsWith("ASSOCIATION_")) setModule("association");
        else setModule("dashboard");
      })
      .catch(() => {
        // session stale — sign out
        useStore.getState().logout();
        setScreen("landing");
      });
    const params = new URLSearchParams(window.location.search);
    const m = params.get("module");
    if (m && ["dashboard", "students", "admissions", "fees", "attendance", "gradebook", "reports", "branches", "ai", "association", "settings"].includes(m)) {
      setModule(m as any);
    }
  }, [session?.id]);

  if (session) return <AppShell />;
  if (screen === "login") return <Login />;
  return <Landing />;
}

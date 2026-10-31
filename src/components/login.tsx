"use client";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { BrandWord, KenteStrip } from "@/components/brand";
import { useStore, ROLE_LABELS, ModuleId } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { toast } from "@/hooks/use-toast";
import { ArrowLeft, KeyRound, Loader2, LogIn, ShieldCheck, Users } from "lucide-react";

// grouped one-click demo accounts — every portal in the ecosystem
const QUICK_GROUPS: { group: string; accounts: { email: string; who: string }[] }[] = [
  {
    group: "Administration (Levels 1–2)",
    accounts: [
      { email: "super@nyansapo.gh", who: "Super Admin · Level 1 — users, roles & permissions" },
      { email: "head@nyansapo.gh", who: "Administrator · Level 2 — Head of School" },
      { email: "proprietor@nyansapo.gh", who: "Proprietor — 3-branch group" },
    ],
  },
  {
    group: "Finance (Levels 3–4)",
    accounts: [
      { email: "bursar@nyansapo.gh", who: "Accountant · Level 3 — Bursar" },
      { email: "cashier@nyansapo.gh", who: "Cashier · Level 4 — collections" },
    ],
  },
  {
    group: "Operations & Staff",
    accounts: [
      { email: "conductor@nyansapo.gh", who: "Bus Conductor · Level 5 — transport" },
      { email: "librarian@nyansapo.gh", who: "Librarian — library portal" },
      { email: "teacher@nyansapo.gh", who: "Teacher — classroom toolchain" },
      { email: "staff@nyansapo.gh", who: "Non-Teaching Staff — self-service" },
    ],
  },
  {
    group: "Community",
    accounts: [
      { email: "parent@nyansapo.gh", who: "Parent — children, fees & reports" },
      { email: "student@nyansapo.gh", who: "Learner — my classes & results" },
    ],
  },
  {
    group: "Association Network",
    accounts: [
      { email: "zone.adenta@nyansapo.gh", who: "Zonal Rep · Adenta" },
      { email: "national@nyansapo.gh", who: "National · GNACOPS" },
    ],
  },
];

export function Login() {
  const { setSession, setSchools, setScreen } = useStore();
  const [email, setEmail] = useState("super@nyansapo.gh");
  const [password, setPassword] = useState("nyansapo");
  const [busy, setBusy] = useState(false);

  async function signIn(em: string, pw: string) {
    setBusy(true);
    try {
      const res = await apiPost<{ user: any }>(`/api/auth`, { email: em, password: pw });
      setSession(res.user);
      const ctx = await apiGet<any>(`/api/context`);
      setSchools(ctx.schools);
      // land each role on its own portal (RBAC landing map from the server)
      const st = useStore.getState();
      const landing = (res.user.landingModule ?? "dashboard") as ModuleId;
      st.setModule(landing);
      toast({ title: `Akwaaba, ${res.user.fullName.split(" ").slice(-1)[0]}!`, description: `${ROLE_LABELS[res.user.role] ?? res.user.role} — you are signed in.` });
    } catch (e: any) {
      toast({ title: "Sign-in failed", description: e.message, variant: "destructive" });
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="min-h-screen grid lg:grid-cols-2">
      {/* left panel */}
      <div className="hidden lg:flex flex-col adinkra-bg text-white p-10 relative overflow-hidden">
        <BrandWord className="[&_span]:text-white" />
        <div className="my-auto max-w-md">
          <h1 className="text-3xl font-bold leading-snug">One login. <span className="text-[#e5b83b]">The whole ecosystem.</span></h1>
          <p className="mt-4 text-white/80 leading-relaxed">
            Five admin levels — Super Admin, Administrator, Accountant, Cashier, Bus Conductor — plus teachers,
            non-teaching staff, librarians, parents and learners. Every portal is permission-based: you see exactly
            what your role allows, nothing more.
          </p>
          <div className="mt-8 space-y-3">
            {[
              { icon: ShieldCheck, t: "Level-based RBAC", d: "iEduPro-style admin ladder with per-user permission overrides." },
              { icon: Users, t: "A portal for everyone", d: "Library circulation, bus manifests, payslips, fee statements — scoped to each account." },
              { icon: KeyRound, t: "Nyansapo Index", d: "Every learner gets a national index that follows them school to school." },
            ].map((x) => (
              <div key={x.t} className="flex gap-3 items-start rounded-xl bg-white/10 border border-white/15 p-3">
                <x.icon className="size-5 text-[#e5b83b] mt-0.5" />
                <div>
                  <div className="text-sm font-medium">{x.t}</div>
                  <div className="text-xs text-white/70">{x.d}</div>
                </div>
              </div>
            ))}
          </div>
        </div>
        <div className="text-xs text-white/50">Demo environment · seeded with realistic Ghanaian data</div>
      </div>

      {/* form */}
      <div className="flex flex-col">
        <div className="p-6 flex items-center justify-between">
          <Button variant="ghost" size="sm" className="gap-1.5" onClick={() => setScreen("landing")}><ArrowLeft className="size-4" /> Back</Button>
          <span className="text-xs text-muted-foreground">All demo passwords: <code className="bg-muted px-1.5 py-0.5 rounded">nyansapo</code></span>
        </div>
        <div className="flex-1 flex items-center justify-center p-6 pt-0">
          <Card className="w-full max-w-md">
            <KenteStrip className="rounded-t-lg" />
            <CardHeader>
              <CardTitle>Sign in to Nyansapo</CardTitle>
              <CardDescription>Choose a demo account or enter credentials.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input id="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="you@school.edu.gh" />
              </div>
              <div className="space-y-2">
                <Label htmlFor="password">Password</Label>
                <Input id="password" type="password" value={password} onChange={(e) => setPassword(e.target.value)} />
              </div>
              <Button className="w-full gap-2" disabled={busy} onClick={() => signIn(email, password)}>
                {busy ? <Loader2 className="size-4 animate-spin" /> : <LogIn className="size-4" />} Sign in
              </Button>
              <div className="relative">
                <div className="absolute inset-0 flex items-center"><span className="w-full border-t" /></div>
                <div className="relative flex justify-center text-xs uppercase"><span className="bg-card px-2 text-muted-foreground">One-click demo portals</span></div>
              </div>
              <div className="max-h-80 overflow-y-auto nice-scroll pr-1 space-y-3">
                {QUICK_GROUPS.map((g) => (
                  <div key={g.group}>
                    <div className="px-3 pb-1 text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">{g.group}</div>
                    <div className="space-y-1.5">
                      {g.accounts.map((q) => (
                        <button
                          key={q.email}
                          onClick={() => { setEmail(q.email); setPassword("nyansapo"); signIn(q.email, "nyansapo"); }}
                          className="w-full text-left rounded-lg border px-3 py-2 hover:bg-secondary/60 transition-colors"
                          disabled={busy}
                        >
                          <div className="text-sm font-medium">{q.who}</div>
                          <div className="text-xs text-muted-foreground font-mono">{q.email}</div>
                        </button>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}

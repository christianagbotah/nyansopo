"use client";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { BrandWord, KenteStrip } from "@/components/brand";
import { useStore, ROLE_LABELS } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { toast } from "@/hooks/use-toast";
import { ArrowLeft, KeyRound, Loader2, LogIn, ShieldCheck } from "lucide-react";

const QUICK = [
  { email: "head@nyansapo.gh", who: "Headteacher · Golden Excellence (GES)" },
  { email: "proprietor@nyansapo.gh", who: "Proprietor · 3-branch group" },
  { email: "teacher@nyansapo.gh", who: "Class Teacher · Basic 4 Gold" },
  { email: "bursar@nyansapo.gh", who: "Bursar · fee collection" },
  { email: "head.gka@nyansapo.gh", who: "Head · Great Kings Adenta" },
  { email: "zone.adenta@nyansapo.gh", who: "Zonal Rep · Adenta" },
  { email: "district.gaeast@nyansapo.gh", who: "District · Ga East" },
  { email: "regional.gar@nyansapo.gh", who: "Regional · Greater Accra" },
  { email: "national@nyansapo.gh", who: "National · GNACOPS" },
];

export function Login() {
  const { setSession, setSchools, setScreen } = useStore();
  const [email, setEmail] = useState("head@nyansapo.gh");
  const [password, setPassword] = useState("nyansapo");
  const [busy, setBusy] = useState(false);

  async function signIn(em: string, pw: string) {
    setBusy(true);
    try {
      const res = await apiPost<{ user: any }>(`/api/auth`, { email: em, password: pw });
      setSession(res.user);
      const ctx = await apiGet<any>(`/api/context`);
      setSchools(ctx.schools);
      // association users land in the portal; school users in the school app
      const role = res.user.role;
      setModuleFor(role);
      toast({ title: `Akwaaba, ${res.user.fullName.split(" ").slice(-1)[0]}!`, description: `${ROLE_LABELS[role] ?? role} — you are signed in.` });
    } catch (e: any) {
      toast({ title: "Sign-in failed", description: e.message, variant: "destructive" });
    } finally {
      setBusy(false);
    }
  }

  function setModuleFor(role: string) {
    const st = useStore.getState();
    if (role.startsWith("ASSOCIATION_")) st.setModule("association");
    else if (role === "GROUP_OWNER") st.setModule("dashboard");
    else st.setModule("dashboard");
  }

  return (
    <div className="min-h-screen grid lg:grid-cols-2">
      {/* left panel */}
      <div className="hidden lg:flex flex-col adinkra-bg text-white p-10 relative overflow-hidden">
        <BrandWord className="[&_span]:text-white" />
        <div className="my-auto max-w-md">
          <h1 className="text-3xl font-bold leading-snug">One login. <span className="text-[#e5b83b]">The whole ecosystem.</span></h1>
          <p className="mt-4 text-white/80 leading-relaxed">
            School leaders run their branches. Proprietors compare every campus. Association officers — national, regional, district and
            zonal — see the living picture of private education in Ghana.
          </p>
          <div className="mt-8 space-y-3">
            {[
              { icon: ShieldCheck, t: "Verified flags", d: "Defaulter flags confirmed by association officers carry weight across the network." },
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
                <div className="relative flex justify-center text-xs uppercase"><span className="bg-card px-2 text-muted-foreground">One-click demo roles</span></div>
              </div>
              <div className="grid grid-cols-1 gap-1.5 max-h-64 overflow-y-auto nice-scroll pr-1">
                {QUICK.map((q) => (
                  <button
                    key={q.email}
                    onClick={() => { setEmail(q.email); setPassword("nyansapo"); signIn(q.email, "nyansapo"); }}
                    className="text-left rounded-lg border px-3 py-2 hover:bg-secondary/60 transition-colors"
                    disabled={busy}
                  >
                    <div className="text-sm font-medium">{q.who}</div>
                    <div className="text-xs text-muted-foreground font-mono">{q.email}</div>
                  </button>
                ))}
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}

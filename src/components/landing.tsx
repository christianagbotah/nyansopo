"use client";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import { BrandWord, KenteStrip, LogoMark } from "@/components/brand";
import { useStore } from "@/lib/store";
import {
  ArrowRight, Banknote, BookOpenCheck, BrainCircuit, CloudOff, GraduationCap, Handshake,
  MonitorSmartphone, Monitor, Smartphone, Network, RefreshCcw, ShieldCheck, Sparkles, Users, WifiOff, CheckCircle2,
  UtensilsCrossed, Bus, Calculator, Package, Ticket,
} from "lucide-react";

const DEMO = [
  { email: "head@nyansapo.gh", label: "Headteacher — Golden Excellence Academy", desc: "Full operations: fees, tuck, transport, payroll, attendance, gradebook" },
  { email: "proprietor@nyansapo.gh", label: "Proprietor — Great Kings & Queens (3 branches)", desc: "Centralised multi-branch control & comparison" },
  { email: "zone.adenta@nyansapo.gh", label: "Zonal Rep — Adenta Zone", desc: "Association oversight, flagged students, verification" },
  { email: "district.gaeast@nyansapo.gh", label: "District Coordinator — Ga East", desc: "District-wide dashboards & compliance" },
  { email: "regional.gar@nyansapo.gh", label: "Regional Chair — Greater Accra", desc: "Regional roll-up across districts and zones" },
  { email: "national@nyansapo.gh", label: "National Executive — GNACOPS", desc: "Everything, everywhere: the national picture" },
];

const FEATURES = [
  { icon: Banknote, title: "Fees, Invoicing & Discounts", text: "Term billing in ₵ — tuition, exam, books, ICT, meals & transport items. MoMo receipts, payment plans, sibling & scholarship discounts, arrears chase-lists and defaulter flagging." },
  { icon: UtensilsCrossed, title: "Daily Services & Tuck", text: "Lunch, breakfast, sachet water, remedial classes — billed by the school day with a fast POS counter, weekly water days, subsidised rates and automatic SMS receipts." },
  { icon: Bus, title: "Transportation", text: "Bus routes, vehicles and drivers. Assign learners to pickup stops, log daily trips and bill the termly fare straight onto invoices." },
  { icon: Calculator, title: "Payroll & HR", text: "Ghana-compliant payroll: SSNIT Tier 1 (5%/13%) and Tier 2 (5%), progressive PAYE, allowances and deductions — with payslips and MoMo payout lists." },
  { icon: Package, title: "Inventory & Stores", text: "Uniforms, exercise books, textbooks and kitchen supplies — stock levels, reorder alerts, suppliers, and issues charged straight to learners' invoices." },
  { icon: Smartphone, title: "SMS Alerts", text: "Fee reminders, absence alerts, payment receipts and announcements to guardians — broadcast by class or arrears list, with templates and auto-alerts." },
  { icon: Network, title: "Cross-School Transfer Tracker", text: "The Nyansapo Index follows every learner. A school admits → the registry instantly pulls records, contacts and flags from every prior school." },
  { icon: BookOpenCheck, title: "Attendance & Gradebook", text: "Daily class registers, continuous assessment, exercises, quizzes, projects and mid-term marks — all offline-capable." },
  { icon: GraduationCap, title: "Exams & Terminal Reports", text: "GES SBC/CCP proficiency grades, BECE & WASSCE scales, Cambridge and IB pathways. Positioned report cards with AI-drafted remarks." },
  { icon: ShieldCheck, title: "Association Oversight", text: "National → Regional → District → Zone. Live school directories, NaSIA licensing status, GNACOPS verification and a national flagged-students registry." },
  { icon: BrainCircuit, title: "AI Suite", text: "Ask the assistant anything about your school, predict fee-default risk per learner, auto-draft report remarks and catch anomalies early." },
  { icon: CloudOff, title: "Offline-First", text: "Dumsa or no data? Mark attendance, take payments, sell tuck and enrol learners offline — everything syncs the moment network returns." },
];

export function Landing() {
  const setScreen = useStore((s) => s.setScreen);
  return (
    <div className="min-h-screen flex flex-col bg-background">
      {/* nav */}
      <header className="sticky top-0 z-40 bg-background/85 backdrop-blur border-b border-border">
        <div className="mx-auto max-w-6xl px-4 sm:px-6 h-16 flex items-center justify-between">
          <BrandWord />
          <nav className="hidden md:flex items-center gap-6 text-sm text-muted-foreground">
            <a href="#features" className="hover:text-foreground">Features</a>
            <a href="#how" className="hover:text-foreground">How the knot works</a>
            <a href="#curricula" className="hover:text-foreground">Curricula</a>
            <a href="#demo" className="hover:text-foreground">Demo accounts</a>
          </nav>
          <div className="flex items-center gap-2">
            <Button variant="outline" onClick={() => setScreen("login")}>Sign in</Button>
            <Button onClick={() => setScreen("login")} className="gap-1.5">Launch demo <ArrowRight className="size-4" /></Button>
          </div>
        </div>
        <KenteStrip />
      </header>

      <main className="flex-1">
        {/* hero */}
        <section className="relative overflow-hidden">
          <div className="adinkra-bg text-white">
            <div className="mx-auto max-w-6xl px-4 sm:px-6 py-16 md:py-24 grid md:grid-cols-2 gap-10 items-center">
              <div>
                <div className="inline-flex items-center gap-2 rounded-full bg-white/10 border border-white/20 px-3 py-1 text-xs font-medium mb-5">
                  <Sparkles className="size-3.5 text-[#e5b83b]" /> Built for Ghana&apos;s 22,000+ private schools · GNACOPS ecosystem
                </div>
                <h1 className="text-4xl md:text-5xl font-bold leading-tight tracking-tight">
                  The Wisdom Knot that binds <span className="text-[#e5b83b]">Ghana&apos;s schools.</span>
                </h1>
                <p className="mt-5 text-white/85 text-lg leading-relaxed max-w-xl">
                  Nyansapo is the complete school management cloud for Ghanaian private schools — from daily operations in a single
                  branch to association-wide oversight across every region, district and zone. And when a parent moves a ward owing fees,
                  the whole network knows.
                </p>
                <div className="mt-8 flex flex-wrap gap-3">
                  <Button size="lg" onClick={() => setScreen("login")} className="bg-[#d9a521] hover:bg-[#c4941c] text-[#20180a] gap-2">
                    Explore the live demo <ArrowRight className="size-4" />
                  </Button>
                  <Button size="lg" variant="outline" className="bg-white/10 border-white/30 text-white hover:bg-white/20 hover:text-white" onClick={() => document.getElementById("how")?.scrollIntoView({ behavior: "smooth" })}>
                    See how the knot works
                  </Button>
                </div>
                <div className="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-white/75">
                  {["Windows", "macOS", "iOS", "Android", "Web portal", "Offline-first"].map((p) => (
                    <span key={p} className="inline-flex items-center gap-1.5"><CheckCircle2 className="size-4 text-[#e5b83b]" /> {p}</span>
                  ))}
                </div>
              </div>
              <div className="hidden md:block">
                {/* association hierarchy visual */}
                <div className="rounded-2xl border border-white/15 bg-[#0a3f2a]/70 p-6 backdrop-blur">
                  <div className="text-sm font-semibold text-white/90 mb-4 flex items-center gap-2"><Network className="size-4 text-[#e5b83b]" /> The knot — one network, every school</div>
                  {[
                    { l: "GNACOPS National Secretariat", d: "National picture · compliance · registry" },
                    { l: "Regional Councils", d: "Greater Accra · Ashanti · Eastern …" },
                    { l: "District Coordinators", d: "Ga East · Kumasi Metro · New Juaben …" },
                    { l: "Zones", d: "Adenta · Madina · Asokwa · Koforidua …" },
                    { l: "Schools & Branches", d: "10 demo schools · 430+ learners · 3 curricula" },
                  ].map((row, i) => (
                    <div key={row.l} className="relative pl-5 pb-4 last:pb-0">
                      {i < 4 && <div className="absolute left-[5px] top-4 bottom-0 w-px bg-white/25" />}
                      <div className="absolute left-0 top-1.5 size-[11px] rounded-full border-2 border-[#e5b83b] bg-[#0e5f3f]" />
                      <div className="text-sm font-medium">{row.l}</div>
                      <div className="text-xs text-white/60">{row.d}</div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
          <KenteStrip />
        </section>

        {/* the problem story */}
        <section id="how" className="mx-auto max-w-6xl px-4 sm:px-6 py-16">
          <div className="text-center max-w-2xl mx-auto">
            <Badge variant="secondary" className="mb-3">The problem Nyansapo solves</Badge>
            <h2 className="text-3xl font-bold tracking-tight">A parent owes fees at School A…</h2>
            <p className="mt-3 text-muted-foreground">…then quietly moves the child to School B down the road. School A is left with bad debt; School B has no idea. In a knot of 22,000 schools, this ends today.</p>
          </div>
          <div className="mt-10 grid md:grid-cols-4 gap-4">
            {[
              { n: "1", t: "Admission inquiry", d: "A guardian applies at a new school. Nyansapo checks the national registry by name, date of birth or MoMo number." },
              { n: "2", t: "Records pulled", d: "The learner's full history appears — every school attended, with contact details for record requests." },
              { n: "3", t: "Flags surface", d: "Outstanding fees, withdrawals owing, discipline and exam-malpractice flags from prior schools — verified by association officers." },
              { n: "4", t: "Clean resolution", d: "The new school requests records, the old school responds, arrears are settled and the flag resolves — everyone protected." },
            ].map((s) => (
              <Card key={s.n} className="relative overflow-hidden">
                <KenteStrip className="absolute top-0" />
                <CardContent className="pt-6">
                  <div className="w-9 h-9 rounded-full bg-primary text-primary-foreground font-bold grid place-items-center mb-3">{s.n}</div>
                  <div className="font-semibold">{s.t}</div>
                  <p className="text-sm text-muted-foreground mt-1.5 leading-relaxed">{s.d}</p>
                </CardContent>
              </Card>
            ))}
          </div>
        </section>

        {/* features */}
        <section id="features" className="bg-secondary/40 py-16">
          <div className="mx-auto max-w-6xl px-4 sm:px-6">
            <div className="text-center max-w-2xl mx-auto mb-10">
              <Badge variant="secondary" className="mb-3">One rich platform</Badge>
              <h2 className="text-3xl font-bold tracking-tight">Everything a modern Ghanaian school runs on</h2>
              <p className="mt-3 text-muted-foreground">Day-to-day school operations, multi-branch proprietorship and association leadership — in one offline-first, AI-powered cloud.</p>
            </div>
            <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
              {FEATURES.map((f) => (
                <Card key={f.title} className="hover:shadow-md transition-shadow">
                  <CardContent className="pt-6">
                    <f.icon className="size-7 text-primary mb-3" />
                    <div className="font-semibold">{f.title}</div>
                    <p className="text-sm text-muted-foreground mt-1.5 leading-relaxed">{f.text}</p>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        </section>

        {/* curricula */}
        <section id="curricula" className="mx-auto max-w-6xl px-4 sm:px-6 py-16">
          <div className="text-center max-w-2xl mx-auto mb-10">
            <Badge variant="secondary" className="mb-3">Configure to your pathway</Badge>
            <h2 className="text-3xl font-bold tracking-tight">GES by default. Cambridge & IB ready.</h2>
            <p className="mt-3 text-muted-foreground">Each school configures its curriculum; classes, subjects, grading scales and report cards adapt instantly.</p>
          </div>
          <div className="grid md:grid-cols-3 gap-4">
            {[
              { t: "GES (NaCCA)", d: "Standards-Based Curriculum KG–B6, Common Core Programme B7–B9, BECE stanine & WASSCE aggregate grading for JHS/SHS.", tag: "Default" },
              { t: "Cambridge (CAIE)", d: "Cambridge Primary, Lower Secondary, IGCSE and A-Level stages with A*–U grading for international schools.", tag: "Premium" },
              { t: "IB World School", d: "PYP, MYP and Diploma Programme with the 1–7 scale and inquiry-based assessment.", tag: "Premium" },
            ].map((c) => (
              <Card key={c.t}>
                <CardContent className="pt-6">
                  <div className="flex items-center justify-between">
                    <div className="font-semibold">{c.t}</div>
                    <Badge variant="outline">{c.tag}</Badge>
                  </div>
                  <p className="text-sm text-muted-foreground mt-2 leading-relaxed">{c.d}</p>
                </CardContent>
              </Card>
            ))}
          </div>
        </section>

        {/* demo accounts */}
        <section id="demo" className="bg-secondary/40 py-16">
          <div className="mx-auto max-w-6xl px-4 sm:px-6">
            <div className="text-center max-w-2xl mx-auto mb-10">
              <Badge variant="secondary" className="mb-3">Try it now</Badge>
              <h2 className="text-3xl font-bold tracking-tight">Walk the whole ecosystem in one demo</h2>
              <p className="mt-3 text-muted-foreground">One-click sign-in with any role — password <code className="bg-card px-1.5 py-0.5 rounded border">nyansapo</code>.</p>
            </div>
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
              {DEMO.map((d) => (
                <Card key={d.email} className="hover:shadow-md transition-shadow cursor-pointer" onClick={() => useStore.getState().setScreen("login")}>
                  <CardContent className="pt-6">
                    <div className="font-medium text-sm">{d.label}</div>
                    <div className="text-xs text-muted-foreground mt-1">{d.desc}</div>
                    <div className="mt-3 font-mono text-xs bg-muted rounded px-2 py-1 inline-block">{d.email}</div>
                  </CardContent>
                </Card>
              ))}
            </div>
            <div className="text-center mt-8">
              <Button size="lg" onClick={() => setScreen("login")} className="gap-2">Open the sign-in screen <ArrowRight className="size-4" /></Button>
            </div>
          </div>
        </section>

        {/* PWA note */}
        <section className="mx-auto max-w-6xl px-4 sm:px-6 py-16">
          <Card className="overflow-hidden">
            <KenteStrip className="no-print" />
            <CardContent className="pt-8 pb-8 grid md:grid-cols-3 gap-8 items-center">
              <div className="md:col-span-2">
                <div className="flex items-center gap-2 font-semibold"><WifiOff className="size-5 text-primary" /> Install Nyansapo on any device</div>
                <p className="text-sm text-muted-foreground mt-2 leading-relaxed">
                  Nyansapo is a Progressive Web App: on Windows and macOS use your browser&apos;s <em>Install</em> option; on iOS tap <em>Share → Add to Home Screen</em>; on Android tap <em>Install app</em>. Once installed it works offline — attendance, payments, marks and enrolments queue locally and sync when your data returns. The demo preview here runs the exact same experience.
                </p>
                <div className="mt-4 flex flex-wrap gap-2">
                  {["Monitor", "Laptop", "Smartphone", "Tablet", "Globe"].map((t) => {
                    const Icon = { Monitor, Laptop: MonitorSmartphone, Smartphone, Tablet: MonitorSmartphone, Globe: Network }[t]!;
                    return <Badge key={t} variant="secondary" className="gap-1.5"><Icon className="size-3.5" />{t}</Badge>;
                  })}
                </div>
              </div>
              <div className="flex justify-center">
                <div className="rounded-2xl border-2 border-dashed border-border p-8 flex flex-col items-center gap-3 text-center">
                  <LogoMark size={64} />
                  <div className="text-sm font-medium">Nyansapo</div>
                  <div className="text-xs text-muted-foreground">Install from your browser menu</div>
                  <div className="flex items-center gap-1 text-xs text-primary font-medium"><RefreshCcw className="size-3.5" /> Syncs automatically</div>
                </div>
              </div>
            </CardContent>
          </Card>
        </section>
      </main>

      <footer className="mt-auto bg-foreground text-background">
        <KenteStrip />
        <div className="mx-auto max-w-6xl px-4 sm:px-6 py-10 grid md:grid-cols-3 gap-8">
          <div>
            <div className="flex items-center gap-2 font-bold text-lg"><Handshake className="size-5" /> Nyansapo</div>
            <p className="text-sm text-background/70 mt-2">The Wisdom Knot — an Adinkra symbol of wisdom, ingenuity and knowledge. Built for Ghana&apos;s private education community.</p>
          </div>
          <div className="text-sm text-background/70">
            <div className="font-semibold text-background mb-2">For schools</div>
            <ul className="space-y-1">
              <li>Golden Excellence Academy · GES</li>
              <li>Sika Gold International · Cambridge</li>
              <li>Star of the East School · IB</li>
              <li>Great Kings & Queens · 3 branches</li>
            </ul>
          </div>
          <div className="text-sm text-background/70">
            <div className="font-semibold text-background mb-2">The knot</div>
            <ul className="space-y-1">
              <li className="flex items-center gap-1.5"><Users className="size-3.5" /> 10 schools · 430+ learners seeded</li>
              <li className="flex items-center gap-1.5"><ShieldCheck className="size-3.5" /> National → Zonal oversight</li>
              <li className="flex items-center gap-1.5"><BrainCircuit className="size-3.5" /> AI advisory & risk engine</li>
            </ul>
          </div>
        </div>
      </footer>
    </div>
  );
}

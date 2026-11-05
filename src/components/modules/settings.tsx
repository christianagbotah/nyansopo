"use client";
import { useEffect, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { ModuleHeader, LoadingBlock, StatusBadge, StatCard, EmptyState } from "@/components/ui-helpers";
import { SCALES_TABLE } from "@/lib/grading-scale";
import { CURRICULA } from "@/lib/curricula";
import { THEME_PRESETS, DEFAULT_THEME, applyThemeToDOM, type SchoolTheme } from "@/lib/theme";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import {Settings as SettingsIcon, GraduationCap, BookOpen, Users, CalendarRange, Plus, Save, MessageSquareQuote, Sparkles, Trash2, Award, Pencil, Baby, Sigma, PencilLine, ChevronDown, ChevronRight, ImageUp, ReceiptText, ShieldCheck, Banknote, Palette, CheckCircle2} from "lucide-react";
import { Textarea } from "@/components/ui/textarea";
import { Checkbox } from "@/components/ui/checkbox";

/** Image file → resized JPEG data-URL (logos ≤256px, signatures ≤320px wide). */
function imageToDataUrl(file: File, maxW: number, maxH: number): Promise<string> {
  return new Promise((resolve, reject) => {
    const img = new Image();
    const url = URL.createObjectURL(file);
    img.onload = () => {
      const scale = Math.min(1, maxW / Math.max(1, img.width), maxH / Math.max(1, img.height));
      const canvas = document.createElement("canvas");
      canvas.width = Math.max(1, Math.round(img.width * scale));
      canvas.height = Math.max(1, Math.round(img.height * scale));
      canvas.getContext("2d")!.drawImage(img, 0, 0, canvas.width, canvas.height);
      URL.revokeObjectURL(url);
      resolve(canvas.toDataURL("image/png"));
    };
    img.onerror = () => { URL.revokeObjectURL(url); reject(new Error("Not a readable image")); };
    img.src = url;
  });
}

export function Settings() {
  const { currentSchoolId, navTarget, session } = useStore();
  // the colour scheme is the school's super admin / proprietor's call —
  // other admins still see every other tab, just not Theme (settings.theme)
  const canTheme = !!session?.permissions?.includes("settings.theme");
  const [data, setData] = useState<any | null>(null);
  const [busy, setBusy] = useState(false);
  const [clsOpen, setClsOpen] = useState(false);
  const [tab, setTab] = useState("profile");
  const [form, setForm] = useState<any>({ motto: "", phone: "", email: "", curriculum: "GES", curricula: ["GES"], nasiaStatus: "", gnacopsStatus: "" });
  // branding & receipts preferences (SchoolSetting-backed)
  const [branding, setBranding] = useState<{ logoUrl: string | null; signatureUrl: string | null; ssnitLogoUrl: string | null; receiptStyle: string }>({ logoUrl: null, signatureUrl: null, ssnitLogoUrl: null, receiptStyle: "A5" });
  // CI3 system_settings_modern General/Payment/Security fields (system.* KV)
  const [sys, setSys] = useState<any>({ slogan: "", address: "", boxNumber: "", digitalAddress: "", website: "", ssnitNumber: "", currency: "GHS", halfPaymentBy: "First Week", fullPaymentBy: "", momoAccountName: "", momoAccountNumber: "", autoLock: false });
  // school theme (CI3 app_theme / theme_settings parity)
  const [themeState, setThemeState] = useState<SchoolTheme>(DEFAULT_THEME);
  const [customTheme, setCustomTheme] = useState<{ primary: string; secondary: string; accent: string }>({ primary: DEFAULT_THEME.primary, secondary: DEFAULT_THEME.secondary, accent: DEFAULT_THEME.accent });
  // statutory rates (OpsSetting — shared with payroll runs)
  const [rates, setRates] = useState<any>({ ssnitTier1Employee: 5, ssnitTier1Employer: 13, ssnitTier2: 5, getfund: 2.5, nhil: 2.5 });
  // per-term "days opened" edits (termId → value as typed)
  const [daysEdits, setDaysEdits] = useState<Record<string, string>>({});

  /* iEduPro nested nav: deep-link to any settings tab */
  const [appliedTarget, setAppliedTarget] = useState<any>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    if (navTarget?.tab && ["profile", "system", "branding", ...(canTheme ? ["theme"] : []), "classes", "subjects", "calendar", "staff", "grading", "earlyyears", "sba", "waec", "reportlib", "statutory"].includes(navTarget.tab)) setTab(navTarget.tab);
  }

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<any>(`/api/school/${currentSchoolId}/settings`).then((d) => {
      setData(d);
      setForm({ motto: d.school.motto ?? "", phone: d.school.phone ?? "", email: d.school.email ?? "", curriculum: d.school.curriculum, curricula: (() => { try { const arr = d.school.curricula ? JSON.parse(d.school.curricula) : [d.school.curriculum]; return Array.isArray(arr) && arr.length ? arr : [d.school.curriculum]; } catch { return [d.school.curriculum]; } })(), nasiaStatus: d.school.nasiaStatus, gnacopsStatus: d.school.gnacopsStatus });
      setBranding({ logoUrl: d.branding?.logoUrl ?? null, signatureUrl: d.branding?.signatureUrl ?? null, ssnitLogoUrl: d.branding?.ssnitLogoUrl ?? null, receiptStyle: d.branding?.receiptStyle ?? "A5" });
      setSys((prev: any) => ({ ...prev, ...(d.system ?? {}) }));
      const t: SchoolTheme | null = d.theme ?? null;
      const resolved = t && t.primary && t.secondary && t.accent ? t : DEFAULT_THEME;
      setThemeState(resolved);
      setCustomTheme({ primary: resolved.primary, secondary: resolved.secondary, accent: resolved.accent });
      applyThemeToDOM(resolved);
      setRates(d.statutory ?? rates);
      setDaysEdits({});
    }).catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(load, [currentSchoolId]);

  if (!data) return <LoadingBlock label="Loading settings…" />;
  const s = data.school;

  async function saveSchool() {
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/settings`, { action: "update-school", ...form, curriculum: form.curricula?.[0] ?? form.curriculum });
      const changed = JSON.stringify([...(form.curricula ?? [])].sort()) !== JSON.stringify([...(s.curricula ? JSON.parse(s.curricula) : [s.curriculum])].sort());
      toast({ title: "School profile updated", description: changed ? `Curricula updated to ${(form.curricula ?? []).join(" + ")}. Grade bands, frameworks and grading now follow the new pathway.` : undefined });
      load();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  }

  async function savePreferences(prefs: { logoUrl?: string | null; signatureUrl?: string | null; ssnitLogoUrl?: string | null; receiptStyle?: string; daysOpened?: Record<string, number> }) {
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/settings`, { action: "update-preferences", ...prefs });
      toast({ title: "Preferences saved" });
      load();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  }

  /* CI3 theme_settings apply_theme / save_custom — live-paints the shell (no reload) */
  async function applyTheme(payload: { action: "preset" | "custom"; theme?: string; primary?: string; secondary?: string; accent?: string; name?: string }) {
    setBusy(true);
    try {
      const d = await apiPost<{ theme: SchoolTheme; message: string }>(`/api/school/${currentSchoolId}/ops/theme`, payload);
      setThemeState(d.theme);
      setCustomTheme({ primary: d.theme.primary, secondary: d.theme.secondary, accent: d.theme.accent });
      applyThemeToDOM(d.theme);
      toast({ title: d.message, description: "Every page, button, modal and chart now wears the new colours." });
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  }

  function previewCustom() {
    applyThemeToDOM({ name: "Custom preview", ...customTheme });
  }

  /* CI3 system_settings do_update — General + Payment + Security in one save */
  async function saveSystem() {
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/settings`, {
        action: "update-system",
        slogan: sys.slogan, address: sys.address, boxNumber: sys.boxNumber, digitalAddress: sys.digitalAddress,
        website: sys.website, ssnitNumber: sys.ssnitNumber, currency: sys.currency,
        halfPaymentBy: sys.halfPaymentBy, fullPaymentBy: sys.fullPaymentBy || null,
        momoAccountName: sys.momoAccountName, momoAccountNumber: sys.momoAccountNumber,
        autoLock: !!sys.autoLock,
      });
      toast({ title: "System settings saved" });
      load();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  }

  /* statutory rates — same store the payroll runs read (OpsSetting) */
  async function saveRates() {
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/payroll`, { action: "save-rates", ...rates });
      toast({ title: "Statutory rates updated", description: "New payroll runs will use these rates — existing payslips keep theirs." });
      load();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  }

  return (
    <div>
      <ModuleHeader title="School Settings" description="Profile, curriculum pathway, classes, subjects and the academic calendar." />
      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="flex-wrap">
          <TabsTrigger value="profile">Profile & curriculum</TabsTrigger>
          <TabsTrigger value="system" className="gap-1.5"><SettingsIcon className="size-4" /> System</TabsTrigger>
          <TabsTrigger value="branding" className="gap-1.5"><ReceiptText className="size-4" /> Branding &amp; logos</TabsTrigger>
          {canTheme && <TabsTrigger value="theme" className="gap-1.5"><Palette className="size-4" /> Theme</TabsTrigger>}
          <TabsTrigger value="statutory" className="gap-1.5"><ShieldCheck className="size-4" /> Statutory (SSNIT)</TabsTrigger>
          <TabsTrigger value="classes">Classes ({data.classrooms.length})</TabsTrigger>
          <TabsTrigger value="subjects">Subjects ({data.subjects.length})</TabsTrigger>
          <TabsTrigger value="calendar">Calendar</TabsTrigger>
          <TabsTrigger value="staff">Staff ({data.staff.length})</TabsTrigger>
          <TabsTrigger value="grading">Grading scale</TabsTrigger>
          <TabsTrigger value="earlyyears" className="gap-1.5"><Baby className="size-4" /> Early years</TabsTrigger>
          <TabsTrigger value="sba" className="gap-1.5"><Sigma className="size-4" /> SBA marks config</TabsTrigger>
          <TabsTrigger value="waec" className="gap-1.5"><Award className="size-4" /> WAEC grading</TabsTrigger>
          <TabsTrigger value="reportlib">Report library ({(data.remarkTemplates?.length ?? 0) + (data.conductItems?.length ?? 0)})</TabsTrigger>
        </TabsList>

        <TabsContent value="profile">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><SettingsIcon className="size-4" /> School profile</CardTitle></CardHeader>
              <CardContent className="space-y-3">
                <div className="grid grid-cols-2 gap-3 text-sm">
                  <div className="rounded-lg border p-3"><div className="text-xs text-muted-foreground">School code</div><div className="font-mono font-semibold">{s.code}</div></div>
                  <div className="rounded-lg border p-3"><div className="text-xs text-muted-foreground">NaSIA registration</div><div className="font-semibold">{s.nasiaRegNo ?? "—"}</div></div>
                  <div className="rounded-lg border p-3"><div className="text-xs text-muted-foreground">Location</div><div className="font-semibold">{s.town}, {s.district}, {s.region}</div></div>
                  <div className="rounded-lg border p-3"><div className="text-xs text-muted-foreground">Zone</div><div className="font-semibold">{s.associationNode?.name}</div></div>
                </div>
                <div className="space-y-1.5"><Label>Motto</Label><Input value={form.motto} onChange={(e) => setForm({ ...form, motto: e.target.value })} /></div>
                <div className="grid gap-3 sm:grid-cols-2">
                  <div className="space-y-1.5"><Label>Phone</Label><Input value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} /></div>
                  <div className="space-y-1.5"><Label>Email</Label><Input value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} /></div>
                </div>
                <div className="grid gap-3 sm:grid-cols-2">
                  <div className="space-y-1.5">
                    <Label>NaSIA status</Label>
                    <Select value={form.nasiaStatus} onValueChange={(v) => setForm({ ...form, nasiaStatus: v })}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent><SelectItem value="LICENSED">Licensed</SelectItem><SelectItem value="PROVISIONAL">Provisional</SelectItem><SelectItem value="PENDING">Pending</SelectItem><SelectItem value="EXPIRED">Expired</SelectItem></SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-1.5">
                    <Label>GNACOPS membership</Label>
                    <Select value={form.gnacopsStatus} onValueChange={(v) => setForm({ ...form, gnacopsStatus: v })}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent><SelectItem value="VERIFIED">Verified</SelectItem><SelectItem value="PENDING">Pending</SelectItem><SelectItem value="LAPSED">Lapsed</SelectItem></SelectContent>
                    </Select>
                  </div>
                </div>
                <Button className="gap-1.5" onClick={saveSchool} disabled={busy}><Save className="size-4" /> {busy ? "Saving…" : "Save profile"}</Button>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><GraduationCap className="size-4" /> Curriculum pathway</CardTitle></CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <Label>Curricula offered <span className="text-muted-foreground font-normal">— tap all that apply; the first is the primary track</span></Label>
                  <div className="grid gap-2 sm:grid-cols-3">
                    {(["GES", "CAIE", "IB"] as const).map((key, idx) => {
                      const on = (form.curricula ?? []).includes(key);
                      const primary = (form.curricula ?? [])[0] === key;
                      return (
                        <button
                          key={key} type="button"
                          onClick={() => {
                            const cur = form.curricula ?? [];
                            let next = on ? cur.filter((c: string) => c !== key) : [...cur, key];
                            if (!next.length) next = [key]; // always keep one
                            setForm({ ...form, curricula: next, curriculum: next[0] });
                          }}
                          className={cn("rounded-xl border p-3 text-left transition-colors cursor-pointer", on ? "border-primary bg-primary/5" : "hover:border-primary/40")}
                        >
                          <div className="flex items-center justify-between">
                            <span className="text-sm font-semibold">{CURRICULA[key].label}</span>
                            {primary && <Badge className="text-[10px]">Primary</Badge>}
                          </div>
                          <p className="text-[11px] text-muted-foreground mt-1 leading-snug">{CURRICULA[key].full.split(" — ")[0]}</p>
                        </button>
                      );
                    })}
                  </div>
                  <p className="text-[11px] text-muted-foreground">International schools (Datus, Tema International…) run the national GES track alongside Cambridge or IB — lesson notes and schemes then pick the framework per subject.</p>
                </div>
                <div className={`rounded-xl border p-4 ${JSON.stringify(form.curricula ?? []) === JSON.stringify(s.curricula ? JSON.parse(s.curricula) : [s.curriculum]) ? "" : "border-amber-400 bg-amber-50/50 dark:bg-amber-950/20"}`}>
                  <div className="font-medium text-sm">{SCALES_TABLE[(form.curricula ?? [])[0]]?.name}</div>
                  <p className="text-xs text-muted-foreground mt-1">{SCALES_TABLE[(form.curricula ?? [])[0]]?.blurb}</p>
                  <div className="mt-3 space-y-1">
                    {SCALES_TABLE[(form.curricula ?? [])[0]]?.bands.map((b: any) => (
                      <div key={b.grade} className="flex justify-between text-xs">
                        <span><b>{b.grade}</b> · {b.label}</span>
                        <span className="text-muted-foreground">≥ {b.min}%</span>
                      </div>
                    ))}
                  </div>
                  {JSON.stringify(form.curricula ?? []) !== JSON.stringify(s.curricula ? JSON.parse(s.curricula) : [s.curriculum]) && <div className="text-xs text-amber-700 dark:text-amber-400 mt-3 font-medium">Saving re-labels class stages and applies the primary curriculum's grading scale to generated report cards.</div>}
                </div>
              </CardContent>
            </Card>

            <Card className="lg:row-span-2">
              <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><ReceiptText className="size-4" /> Branding &amp; receipts</CardTitle></CardHeader>
              <CardContent className="space-y-4">
                {/* logo */}
                <div className="flex items-center gap-3">
                  <div className="size-16 rounded-xl border bg-secondary/50 flex items-center justify-center overflow-hidden shrink-0">
                    {branding.logoUrl ? <img src={branding.logoUrl} alt="School logo" className="size-full object-contain" /> : <ImageUp className="size-6 text-muted-foreground/50" />}
                  </div>
                  <div className="min-w-0 flex-1">
                    <Label className="text-xs">School logo (receipt letterheads)</Label>
                    <div className="flex items-center gap-2 mt-1.5">
                      <Input type="file" accept="image/*" className="h-8 w-44 text-xs file:mr-1.5 file:h-6 file:px-2 file:text-xs"
                        onChange={async (e) => { const f = e.target.files?.[0]; if (!f) return; try { setBranding({ ...branding, logoUrl: await imageToDataUrl(f, 256, 256) }); } catch (err: any) { toast({ title: "Skipped", description: err.message, variant: "destructive" }); } }} />
                      {branding.logoUrl && <Button size="sm" variant="ghost" className="h-8 text-xs text-muted-foreground" onClick={() => setBranding({ ...branding, logoUrl: null })}>Remove</Button>}
                    </div>
                  </div>
                </div>
                {/* head-teacher signature */}
                <div className="flex items-center gap-3">
                  <div className="h-14 w-40 rounded-xl border bg-secondary/50 flex items-center justify-center overflow-hidden shrink-0">
                    {branding.signatureUrl ? <img src={branding.signatureUrl} alt="Head-teacher signature" className="h-full object-contain" /> : <ImageUp className="size-6 text-muted-foreground/50" />}
                  </div>
                  <div className="min-w-0 flex-1">
                    <Label className="text-xs">Head-teacher / bursar signature (receipts)</Label>
                    <div className="flex items-center gap-2 mt-1.5">
                      <Input type="file" accept="image/*" className="h-8 w-44 text-xs file:mr-1.5 file:h-6 file:px-2 file:text-xs"
                        onChange={async (e) => { const f = e.target.files?.[0]; if (!f) return; try { setBranding({ ...branding, signatureUrl: await imageToDataUrl(f, 320, 120) }); } catch (err: any) { toast({ title: "Skipped", description: err.message, variant: "destructive" }); } }} />
                      {branding.signatureUrl && <Button size="sm" variant="ghost" className="h-8 text-xs text-muted-foreground" onClick={() => setBranding({ ...branding, signatureUrl: null })}>Remove</Button>}
                    </div>
                  </div>
                </div>
                {/* SSNIT logo (statutory payroll reports) */}
                <div className="flex items-center gap-3">
                  <div className="size-16 rounded-xl border bg-secondary/50 flex items-center justify-center overflow-hidden shrink-0">
                    {branding.ssnitLogoUrl ? <img src={branding.ssnitLogoUrl} alt="SSNIT logo" className="size-full object-contain" /> : <ImageUp className="size-6 text-muted-foreground/50" />}
                  </div>
                  <div className="min-w-0 flex-1">
                    <Label className="text-xs">SSNIT logo (Tier 1 / Tier 2 statutory reports)</Label>
                    <div className="flex items-center gap-2 mt-1.5">
                      <Input type="file" accept="image/*" className="h-8 w-44 text-xs file:mr-1.5 file:h-6 file:px-2 file:text-xs"
                        onChange={async (e) => { const f = e.target.files?.[0]; if (!f) return; try { setBranding({ ...branding, ssnitLogoUrl: await imageToDataUrl(f, 256, 256) }); } catch (err: any) { toast({ title: "Skipped", description: err.message, variant: "destructive" }); } }} />
                      {branding.ssnitLogoUrl && <Button size="sm" variant="ghost" className="h-8 text-xs text-muted-foreground" onClick={() => setBranding({ ...branding, ssnitLogoUrl: null })}>Remove</Button>}
                    </div>
                  </div>
                </div>
                {/* default receipt style */}
                <div className="space-y-1.5">
                  <Label>Default receipt style</Label>
                  <Select value={branding.receiptStyle} onValueChange={(v) => setBranding({ ...branding, receiptStyle: v })}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="A5">Standard A5 — kente-accented, itemised</SelectItem>
                      <SelectItem value="FORMAL">Formal Bursar A5 — serif, dotted fields, amount in words</SelectItem>
                      <SelectItem value="MODERN">Modern Gradient — bold paid banner</SelectItem>
                      <SelectItem value="THERMAL">Thermal 80mm — counter roll printer</SelectItem>
                    </SelectContent>
                  </Select>
                  <p className="text-[11px] text-muted-foreground">Every newly issued receipt (fees, counter, store, transport) opens in this style. Cashiers can still reprint in another style.</p>
                </div>
                <Button className="gap-1.5" disabled={busy} onClick={() => savePreferences({ logoUrl: branding.logoUrl, signatureUrl: branding.signatureUrl, ssnitLogoUrl: branding.ssnitLogoUrl, receiptStyle: branding.receiptStyle })}>
                  <Save className="size-4" /> {busy ? "Saving…" : "Save branding & receipts"}
                </Button>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* ── CI3 system_settings_modern — General + Payment + Security tabs ── */}
        <TabsContent value="system">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><SettingsIcon className="size-4" /> School information</CardTitle></CardHeader>
              <CardContent className="space-y-3">
                <div className="grid grid-cols-2 gap-3 text-sm">
                  <div className="rounded-lg border p-3"><div className="text-xs text-muted-foreground">System name</div><div className="font-semibold">{s.name}</div></div>
                  <div className="rounded-lg border p-3"><div className="text-xs text-muted-foreground">Location</div><div className="font-semibold">{s.town}, {s.district}, {s.region}</div></div>
                </div>
                <div className="space-y-1.5"><Label>School&rsquo;s slogan</Label><Input value={sys.slogan ?? ""} onChange={(e) => setSys({ ...sys, slogan: e.target.value })} placeholder="Wisdom knots, bright futures" /></div>
                <div className="space-y-1.5"><Label>Address</Label><Textarea rows={2} value={sys.address ?? ""} onChange={(e) => setSys({ ...sys, address: e.target.value })} placeholder="Street / locality" /></div>
                <div className="grid gap-3 sm:grid-cols-2">
                  <div className="space-y-1.5"><Label>Box number</Label><Input value={sys.boxNumber ?? ""} onChange={(e) => setSys({ ...sys, boxNumber: e.target.value })} placeholder="P.O. Box &hellip;" /></div>
                  <div className="space-y-1.5"><Label>Digital address (GhanaPost GPS)</Label><Input value={sys.digitalAddress ?? ""} onChange={(e) => setSys({ ...sys, digitalAddress: e.target.value })} placeholder="GA-123-4567" /></div>
                </div>
                <div className="grid gap-3 sm:grid-cols-2">
                  <div className="space-y-1.5"><Label>Website</Label><Input value={sys.website ?? ""} onChange={(e) => setSys({ ...sys, website: e.target.value })} placeholder="https://&hellip;" /></div>
                  <div className="space-y-1.5"><Label>SSNIT Establishment Number</Label><Input value={sys.ssnitNumber ?? ""} onChange={(e) => setSys({ ...sys, ssnitNumber: e.target.value })} placeholder="Employer registration with SSNIT" /></div>
                </div>
                <div className="grid gap-3 sm:grid-cols-2">
                  <div className="space-y-1.5"><Label>Currency</Label><Input value={sys.currency ?? "GHS"} disabled /></div>
                  <div className="space-y-1.5"><Label>System email</Label><Input value={s.email ?? ""} disabled /></div>
                </div>
              </CardContent>
            </Card>
            <div className="space-y-4">
              <Card>
                <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Banknote className="size-4" /> Payment settings</CardTitle></CardHeader>
                <CardContent className="space-y-3">
                  <div className="grid gap-3 sm:grid-cols-2">
                    <div className="space-y-1.5">
                      <Label>Half payment expected by</Label>
                      <Select value={sys.halfPaymentBy ?? "First Week"} onValueChange={(v) => setSys({ ...sys, halfPaymentBy: v })}>
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                          <SelectItem value="First Week">First week of term</SelectItem>
                          <SelectItem value="Second Week">Second week of term</SelectItem>
                          <SelectItem value="Third Week">Third week of term</SelectItem>
                          <SelectItem value="Fourth Week">Fourth week of term</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <div className="space-y-1.5"><Label>Full payment expected by</Label><Input type="date" value={sys.fullPaymentBy ? String(sys.fullPaymentBy).slice(0, 10) : ""} onChange={(e) => setSys({ ...sys, fullPaymentBy: e.target.value })} /></div>
                  </div>
                  <div className="grid gap-3 sm:grid-cols-2">
                    <div className="space-y-1.5"><Label>Mobile money account name</Label><Input value={sys.momoAccountName ?? ""} onChange={(e) => setSys({ ...sys, momoAccountName: e.target.value })} placeholder="Golden Excellence Academy" /></div>
                    <div className="space-y-1.5"><Label>Mobile money number</Label><Input value={sys.momoAccountNumber ?? ""} onChange={(e) => setSys({ ...sys, momoAccountNumber: e.target.value })} placeholder="024 000 0000 (MTN)" /></div>
                  </div>
                </CardContent>
              </Card>
              <Card>
                <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><ShieldCheck className="size-4" /> Financial security</CardTitle></CardHeader>
                <CardContent className="space-y-3">
                  <label className="flex items-start gap-3 rounded-lg border p-3 cursor-pointer">
                    <Checkbox checked={!!sys.autoLock} onCheckedChange={(v) => setSys({ ...sys, autoLock: !!v })} />
                    <span>
                      <span className="text-sm font-medium block">Auto-lock invoices &amp; payments</span>
                      <span className="text-xs text-muted-foreground">Payments lock on creation and invoices lock once fully paid &mdash; edits then require an approved modification request.</span>
                    </span>
                  </label>
                  <div className="rounded-lg border-l-4 border-blue-400 bg-blue-50 dark:bg-blue-950/40 p-3 text-xs text-muted-foreground">
                    Locked records change only through the modification-request queue (Fees &rarr; Approvals), keeping an audit trail of who changed what.
                  </div>
                </CardContent>
              </Card>
              <Button className="gap-1.5" onClick={saveSystem} disabled={busy}><Save className="size-4" /> {busy ? "Saving&hellip;" : "Save all settings"}</Button>
            </div>
          </div>
        </TabsContent>

        {/* ── Branding — the dedicated tab reuses the profile card&rsquo;s branding block
             (the uploads live in one place; the tab deep-links straight to them) ── */}
        <TabsContent value="branding">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><ReceiptText className="size-4" /> Branding, signatures &amp; receipts</CardTitle></CardHeader>
            <CardContent className="space-y-4">
              <p className="text-xs text-muted-foreground">Upload once &mdash; the school logo prints on receipts and report cards, the head-teacher signature signs them, and the SSNIT logo heads the Tier&nbsp;1 / Tier&nbsp;2 statutory schedules. Manage the uploads from the <button className="text-primary underline" onClick={() => setTab("profile")}>Profile tab</button> (Branding &amp; receipts card).</p>
              <div className="grid gap-3 sm:grid-cols-3">
                <div className="rounded-xl border p-3 text-center">
                  <div className="mx-auto size-20 rounded-xl border bg-secondary/50 flex items-center justify-center overflow-hidden">
                    {branding.logoUrl ? <img src={branding.logoUrl} alt="School logo" className="size-full object-contain" /> : <ImageUp className="size-7 text-muted-foreground/50" />}
                  </div>
                  <div className="mt-2 text-xs font-semibold">School logo</div>
                  <div className="text-[11px] text-muted-foreground">receipt letterheads</div>
                </div>
                <div className="rounded-xl border p-3 text-center">
                  <div className="mx-auto h-20 w-full rounded-xl border bg-secondary/50 flex items-center justify-center overflow-hidden">
                    {branding.signatureUrl ? <img src={branding.signatureUrl} alt="Head-teacher signature" className="h-full object-contain" /> : <ImageUp className="size-7 text-muted-foreground/50" />}
                  </div>
                  <div className="mt-2 text-xs font-semibold">Head-teacher signature</div>
                  <div className="text-[11px] text-muted-foreground">signs receipts &amp; reports</div>
                </div>
                <div className="rounded-xl border p-3 text-center">
                  <div className="mx-auto size-20 rounded-xl border bg-secondary/50 flex items-center justify-center overflow-hidden">
                    {branding.ssnitLogoUrl ? <img src={branding.ssnitLogoUrl} alt="SSNIT logo" className="size-full object-contain" /> : <ImageUp className="size-7 text-muted-foreground/50" />}
                  </div>
                  <div className="mt-2 text-xs font-semibold">SSNIT logo</div>
                  <div className="text-[11px] text-muted-foreground">Tier 1 / Tier 2 reports</div>
                </div>
              </div>
              <div className="space-y-1.5 max-w-sm">
                <Label>Default receipt style</Label>
                <Select value={branding.receiptStyle} onValueChange={(v) => setBranding({ ...branding, receiptStyle: v })}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="A5">Standard A5 &mdash; kente-accented, itemised</SelectItem>
                    <SelectItem value="FORMAL">Formal Bursar A5 &mdash; serif, dotted fields, amount in words</SelectItem>
                    <SelectItem value="MODERN">Modern Gradient &mdash; bold paid banner</SelectItem>
                    <SelectItem value="THERMAL">Thermal 80mm &mdash; counter roll printer</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <Button className="gap-1.5" disabled={busy} onClick={() => savePreferences({ logoUrl: branding.logoUrl, signatureUrl: branding.signatureUrl, ssnitLogoUrl: branding.ssnitLogoUrl, receiptStyle: branding.receiptStyle })}>
                <Save className="size-4" /> {busy ? "Saving&hellip;" : "Save branding &amp; receipts"}
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── Theme (CI3 theme_settings.php parity): preset swatch cards +
             custom pickers — applies instantly, every member sees it next load.
             Super-admin-only: the school's colour scheme is their call ── */}
        {canTheme && (
        <TabsContent value="theme">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Palette className="size-4" /> Theme customization</CardTitle></CardHeader>
            <CardContent className="space-y-6">
              <p className="text-xs text-muted-foreground">Pick the colour scheme for your system — the top bar, sidebar, buttons, modal headers, cards and charts all follow it. Choose a predefined theme below or mix your own with the custom pickers (CI3 app_theme parity). Applies instantly for everyone at next sign-in.</p>

              <div>
                <h3 className="mb-3 text-sm font-semibold flex items-center gap-1.5"><Sparkles className="size-4 text-primary" /> Predefined themes</h3>
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                  {THEME_PRESETS.map((p) => {
                    const active = themeState.primary.toLowerCase() === p.primary.toLowerCase() && themeState.secondary.toLowerCase() === p.secondary.toLowerCase();
                    return (
                      <button
                        key={p.key}
                        onClick={() => applyTheme({ action: "preset", theme: p.key })}
                        disabled={busy}
                        className={cn(
                          "rounded-xl border-2 overflow-hidden text-left transition-all hover:-translate-y-0.5 hover:shadow-md",
                          active ? "border-emerald-500 shadow-[0_0_0_3px_rgba(16,185,129,0.15)]" : "border-border",
                        )}
                        title={`Apply ${p.name}`}
                      >
                        <div className="relative h-20" style={{ background: `linear-gradient(135deg, ${p.primary} 0%, ${p.secondary} 100%)` }}>
                          <div className="absolute bottom-2 left-2 right-2 h-4 rounded" style={{ background: p.accent }} />
                        </div>
                        <div className="flex items-center justify-between px-2.5 py-2 bg-secondary/40">
                          <span className="text-xs font-semibold">{p.name}</span>
                          {active && <CheckCircle2 className="size-4 text-emerald-500" />}
                        </div>
                      </button>
                    );
                  })}
                </div>
              </div>

              <div className="border-t pt-5">
                <h3 className="mb-3 text-sm font-semibold flex items-center gap-1.5"><Pencil className="size-4 text-primary" /> Custom theme</h3>
                <div className="grid gap-3 sm:grid-cols-3">
                  <div className="space-y-1.5">
                    <Label>Primary colour</Label>
                    <input type="color" value={customTheme.primary} onChange={(e) => { setCustomTheme({ ...customTheme, primary: e.target.value }); previewCustom(); }} className="h-11 w-full cursor-pointer rounded-lg border bg-card p-1" />
                  </div>
                  <div className="space-y-1.5">
                    <Label>Secondary colour</Label>
                    <input type="color" value={customTheme.secondary} onChange={(e) => { setCustomTheme({ ...customTheme, secondary: e.target.value }); previewCustom(); }} className="h-11 w-full cursor-pointer rounded-lg border bg-card p-1" />
                  </div>
                  <div className="space-y-1.5">
                    <Label>Accent colour</Label>
                    <input type="color" value={customTheme.accent} onChange={(e) => { setCustomTheme({ ...customTheme, accent: e.target.value }); previewCustom(); }} className="h-11 w-full cursor-pointer rounded-lg border bg-card p-1" />
                  </div>
                </div>
                <div className="mt-4 flex items-center gap-2 flex-wrap">
                  <Button className="gap-1.5" disabled={busy} onClick={() => applyTheme({ action: "custom", ...customTheme, name: "Custom" })}>
                    <Save className="size-4" /> {busy ? "Applying&hellip;" : "Apply custom theme"}
                  </Button>
                  <Button variant="outline" className="gap-1.5" onClick={previewCustom} disabled={busy}>
                    <Sparkles className="size-4" /> Preview (reloads on sign-in if not saved)
                  </Button>
                  <span className="text-xs text-muted-foreground">Current: <b>{themeState.name}</b> — {themeState.primary} / {themeState.secondary} / {themeState.accent}</span>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
        )}

        {/* ── Statutory rates (CI3 payroll_statutory_settings parity) ── */}
        <TabsContent value="statutory">
          <div className="space-y-4">
            <div className="rounded-lg border-l-4 border-amber-400 bg-amber-50 dark:bg-amber-950/40 p-3 text-xs text-muted-foreground">
              <b className="text-amber-800 dark:text-amber-300">Important:</b> changes affect all <i>new</i> payroll calculations &mdash; existing payslips keep the rates they were computed with. Verify rates with the Ghana Revenue Authority before making changes.
            </div>
            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><ShieldCheck className="size-4" /> Statutory deduction rates</CardTitle></CardHeader>
              <CardContent className="p-0">
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead className="bg-secondary/70">
                      <tr className="text-left border-b">
                        <th className="px-4 py-2.5 font-medium">Deduction type</th>
                        <th className="px-4 py-2.5 font-medium">Description</th>
                        <th className="px-4 py-2.5 font-medium text-center w-32">Rate (%)</th>
                      </tr>
                    </thead>
                    <tbody>
                      {([
                        ["ssnitTier1Employer", "SSNIT Tier 1 \u2014 Employer", "Employer contribution to SSNIT Tier 1 (applied to basic salary)"],
                        ["ssnitTier1Employee", "SSNIT Tier 1 \u2014 Employee", "Employee share deducted for PAYE computation (5.5% statutory, 5% typical net scheme)"],
                        ["ssnitTier2", "SSNIT Tier 2", "Occupational pension contribution ( Tier 2 fund)"],
                        ["getfund", "GETFund", "Ghana Education Trust Fund levy on basic salary"],
                        ["nhil", "NHIL", "National Health Insurance Levy on basic salary"],
                      ] as const).map(([key, label, desc]) => (
                        <tr key={key} className="border-b last:border-0">
                          <td className="px-4 py-2.5 font-medium">{label}</td>
                          <td className="px-4 py-2.5 text-xs text-muted-foreground">{desc}</td>
                          <td className="px-4 py-2.5">
                            <Input type="number" min="0" max="100" step="0.01" className="h-9 text-center" value={rates[key] ?? 0}
                              onChange={(e) => setRates({ ...rates, [key]: parseFloat(e.target.value) || 0 })} />
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
                <div className="flex flex-wrap items-center gap-2 border-t px-4 py-3">
                  <Button className="gap-1.5" onClick={saveRates} disabled={busy}><Save className="size-4" /> {busy ? "Saving&hellip;" : "Save all changes"}</Button>
                  <Button variant="outline" onClick={load} disabled={busy}>Reset to saved values</Button>
                  <span className="text-xs text-muted-foreground ml-auto">Tier-1 contributions are capped at GH₵35,000/month pensionable earnings. Full schedules print from Payroll &rarr; SSNIT reports.</span>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="classes">
          <Card>
            <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
              <CardTitle className="text-base">Classes & streams</CardTitle>
              <Button size="sm" className="gap-1.5" onClick={() => setClsOpen(true)}><Plus className="size-4" /> Add class</Button>
            </CardHeader>
            <CardContent className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
              {data.classrooms.map((c: any) => (
                <div key={c.id} className="rounded-lg border p-3">
                  <div className="font-medium text-sm">{c.name}</div>
                  <div className="text-xs text-muted-foreground">{c.stage} · capacity {c.capacity}</div>
                  <Badge variant="secondary" className="mt-2">{c.grade}</Badge>
                </div>
              ))}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="subjects">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><BookOpen className="size-4" /> Subjects offered ({s.curriculum} pathway)</CardTitle></CardHeader>
            <CardContent className="flex flex-wrap gap-2">
              {data.subjects.map((sub: any) => (
                <Badge key={sub.id} variant="secondary" className="py-1.5 text-xs">{sub.name}</Badge>
              ))}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="calendar">
          <div className="space-y-4">
            {data.years.map((y: any) => (
              <Card key={y.id}>
                <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
                  <CardTitle className="text-base flex items-center gap-2"><CalendarRange className="size-4" /> {y.name}</CardTitle>
                  {y.isCurrent && <Badge>Current year</Badge>}
                </CardHeader>
                <CardContent className="grid sm:grid-cols-3 gap-3">
                  {y.terms.map((t: any) => (
                    <div key={t.id} className={`rounded-lg border p-3 ${t.isCurrent ? "border-primary" : ""}`}>
                      <div className="font-medium text-sm flex items-center justify-between">{t.name} {t.isCurrent && <Badge variant="secondary">current</Badge>}</div>
                      <div className="text-xs text-muted-foreground mt-1">{new Date(t.startsAt).toLocaleDateString("en-GB")} → {new Date(t.endsAt).toLocaleDateString("en-GB")}</div>
                      <div className="mt-2">
                        <Label className="text-[11px] text-muted-foreground">Days opened (term runtime)</Label>
                        <Input
                          type="number" min="0" max="400" className="h-8 mt-1" placeholder="e.g. 60"
                          value={daysEdits[t.id] ?? (data.daysOpened?.[t.id] != null ? String(data.daysOpened[t.id]) : "")}
                          onChange={(e) => setDaysEdits({ ...daysEdits, [t.id]: e.target.value })}
                        />
                      </div>
                    </div>
                  ))}
                </CardContent>
                {(() => {
                  const changed: Record<string, number> = {};
                  for (const [termId, v] of Object.entries(daysEdits)) {
                    const n = Number(v);
                    if (v !== "" && Number.isFinite(n) && n !== (data.daysOpened?.[termId] ?? null)) changed[termId] = n;
                  }
                  const hasChanges = Object.keys(changed).length > 0;
                  return (
                    <div className="px-6 pb-5 -mt-1">
                      <Button size="sm" variant="outline" className="gap-1.5" disabled={!hasChanges || busy}
                        onClick={() => savePreferences({ daysOpened: changed })}>
                        <Save className="size-3.5" /> Save days opened{hasChanges ? ` (${Object.keys(changed).length})` : ""}
                      </Button>
                      <p className="text-[11px] text-muted-foreground mt-1.5">Term runtime used for attendance-rate maths and lesson-note compliance (weeks elapsed ÷ days opened).</p>
                    </div>
                  );
                })()}
              </Card>
            ))}
          </div>
        </TabsContent>

        <TabsContent value="staff">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Users className="size-4" /> Staff</CardTitle></CardHeader>
            <CardContent className="space-y-2">
              {data.staff.map((st: any) => (
                <div key={st.id} className="rounded-lg border p-3 flex items-center justify-between">
                  <div>
                    <div className="font-medium text-sm">{st.fullName}</div>
                    <div className="text-xs text-muted-foreground">{st.title ?? st.role}</div>
                  </div>
                  <Badge variant="outline">{st.role}</Badge>
                </div>
              ))}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="grading">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base">Active grading scale — {s.curriculum}</CardTitle></CardHeader>
            <CardContent>
              <div className="rounded-xl border overflow-hidden">
                <table className="w-full text-sm">
                  <thead><tr className="bg-secondary text-left"><th className="px-3 py-2 font-medium">Grade</th><th className="px-3 py-2 font-medium">Descriptor</th><th className="px-3 py-2 font-medium text-right">Minimum score</th></tr></thead>
                  <tbody>
                    {SCALES_TABLE[s.curriculum]?.bands.map((b: any) => (
                      <tr key={b.grade} className="border-t"><td className="px-3 py-2 font-semibold">{b.grade}</td><td className="px-3 py-2">{b.label}</td><td className="px-3 py-2 text-right tabular-nums">{b.min}%</td></tr>
                    ))}
                  </tbody>
                </table>
              </div>
              <p className="text-xs text-muted-foreground mt-3">{SCALES_TABLE[s.curriculum]?.examLabel} — class score 30% + exam 70% (GES basic convention).</p>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="waec">
          <WaecGradingTab />
        </TabsContent>

        <TabsContent value="earlyyears">
          <EarlyYearsTab />
        </TabsContent>

        <TabsContent value="sba">
          <SbaConfigTab />
        </TabsContent>

        <TabsContent value="reportlib">
          <ReportLibraryTab data={data} onChanged={load} />
        </TabsContent>
      </Tabs>

      <AddClassDialog open={clsOpen} onOpenChange={setClsOpen} onDone={load} />
    </div>
  );
}

function AddClassDialog({ open, onOpenChange, onDone }: { open: boolean; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [form, setForm] = useState({ name: "", grade: "B1", stage: "Primary (SBC)", capacity: "30" });
  const [busy, setBusy] = useState(false);

  async function submit() {
    if (!form.name) { toast({ title: "Class name required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/settings`, { action: "create-classroom", ...form, capacity: Number(form.capacity) });
      toast({ title: "Class created" });
      onOpenChange(false); onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader><DialogTitle>Add a class</DialogTitle></DialogHeader>
        <div className="grid gap-3 sm:grid-cols-2">
          <div className="space-y-1.5 sm:col-span-2"><Label>Class name</Label><Input placeholder="e.g. Basic 6 — Emerald" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} /></div>
          <div className="space-y-1.5">
            <Label>Grade level</Label>
            <Input value={form.grade} onChange={(e) => setForm({ ...form, grade: e.target.value })} placeholder="B6 / Year 6 / MYP 2" />
          </div>
          <div className="space-y-1.5"><Label>Capacity</Label><Input type="number" value={form.capacity} onChange={(e) => setForm({ ...form, capacity: e.target.value })} /></div>
          <div className="space-y-1.5 sm:col-span-2">
            <Label>Stage</Label>
            <Select value={form.stage} onValueChange={(v) => setForm({ ...form, stage: v })}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="Kindergarten (SBC)">Kindergarten (SBC)</SelectItem>
                <SelectItem value="Primary (SBC)">Primary (SBC)</SelectItem>
                <SelectItem value="Common Core (CCP)">Common Core (CCP)</SelectItem>
                <SelectItem value="Cambridge Primary">Cambridge Primary</SelectItem>
                <SelectItem value="Cambridge Lower Secondary">Cambridge Lower Secondary</SelectItem>
                <SelectItem value="Cambridge Upper Secondary / IGCSE">Cambridge Upper Secondary / IGCSE</SelectItem>
                <SelectItem value="IB Primary Years Programme">IB Primary Years Programme</SelectItem>
                <SelectItem value="IB Middle Years Programme">IB Middle Years Programme</SelectItem>
                <SelectItem value="IB Diploma Programme">IB Diploma Programme</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Creating…" : "Create class"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ── REPORT SHEET LIBRARY — remark templates & conduct traits (iedupro parity) ── */
const TEMPLATE_CATEGORIES: { value: string; label: string; hint: string }[] = [
  { value: "TEACHER_POSITIVE", label: "Teacher — positive", hint: "used for scores ≥ 68%" },
  { value: "TEACHER_NEUTRAL", label: "Teacher — neutral", hint: "used for 40–67%" },
  { value: "TEACHER_NEGATIVE", label: "Teacher — concern", hint: "used below 40%" },
  { value: "HEAD_RANGE", label: "Head — by average band", hint: "picked by the learner's overall average" },
];

function ReportLibraryTab({ data, onChanged }: { data: any; onChanged: () => void }) {
  const { currentSchoolId } = useStore();
  const templates: { id: string; category: string; minPercent: number | null; maxPercent: number | null; text: string; active: boolean }[] = data.remarkTemplates ?? [];
  const conductItems: { id: string; name: string; kind: string; active: boolean }[] = data.conductItems ?? [];
  const [busy, setBusy] = useState<string | null>(null);
  const [draft, setDraft] = useState<{ category: string; minPercent: string; maxPercent: string; text: string }>({ category: "TEACHER_POSITIVE", minPercent: "", maxPercent: "", text: "" });
  const [conductDraft, setConductDraft] = useState<{ name: string; kind: string }>({ name: "", kind: "CONDUCT" });

  async function act(payload: Record<string, unknown>, key: string, okMsg: string) {
    if (!currentSchoolId) return;
    setBusy(key);
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/settings`, payload);
      toast({ title: okMsg, description: res.created ? `${res.created} starter entries added.` : undefined });
      onChanged();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(null); }
  }

  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
      {/* remark templates */}
      <Card>
        <CardHeader className="pb-3 flex-row items-center justify-between space-y-0 flex-wrap gap-2">
          <CardTitle className="text-base flex items-center gap-2"><MessageSquareQuote className="size-4" /> Remarks library ({templates.length})</CardTitle>
          <Button size="sm" variant="outline" className="h-8 gap-1.5" disabled={busy === "seed" || templates.length > 0} onClick={() => act({ action: "seed-remark-templates" }, "seed", "Starter library loaded")}>
            <Sparkles className="size-3.5" /> Load starters
          </Button>
        </CardHeader>
        <CardContent className="space-y-3">
          <p className="text-xs text-muted-foreground">
            When report cards are generated, a remark is drawn at random from the matching band — subject remarks by score, head remarks by the learner&apos;s average. Keep several per band for natural variety.
          </p>
          {templates.length === 0 ? (
            <div className="rounded-lg border border-dashed p-4 text-center text-sm text-muted-foreground">
              No custom remarks yet — the generator falls back to built-in phrases. Load the starter library or add your own below.
            </div>
          ) : (
            <div className="rounded-xl border max-h-[320px] overflow-y-auto nice-scroll divide-y">
              {templates.map((t) => {
                const cat = TEMPLATE_CATEGORIES.find((c) => c.value === t.category);
                return (
                  <div key={t.id} className="p-2.5 flex items-start gap-2.5">
                    <div className="flex-1 min-w-0">
                      <div className="flex flex-wrap items-center gap-1.5">
                        <Badge variant="secondary" className="text-[10px]">{cat?.label ?? t.category}</Badge>
                        {t.category === "HEAD_RANGE" && <Badge variant="outline" className="text-[10px]">{t.minPercent}–{t.maxPercent}%</Badge>}
                        {!t.active && <Badge variant="outline" className="text-[10px] text-muted-foreground">off</Badge>}
                      </div>
                      <p className="text-[13px] mt-1 leading-snug">{t.text}</p>
                    </div>
                    <div className="flex flex-col gap-1">
                      <Button size="sm" variant="ghost" className="h-6 px-2 text-[11px]" disabled={busy === t.id} onClick={() => act({ action: "toggle-remark-template", id: t.id, active: !t.active }, t.id, t.active ? "Template disabled" : "Template enabled")}>{t.active ? "Disable" : "Enable"}</Button>
                      <Button size="sm" variant="ghost" className="h-6 px-2 text-[11px] text-destructive" disabled={busy === t.id} onClick={() => act({ action: "delete-remark-template", id: t.id }, t.id, "Template deleted")}><Trash2 className="size-3" /></Button>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
          {/* add form */}
          <div className="rounded-xl border bg-secondary/30 p-3 space-y-2.5">
            <div className="grid grid-cols-2 gap-2">
              <Select value={draft.category} onValueChange={(v) => setDraft({ ...draft, category: v })}>
                <SelectTrigger className="h-8 text-xs"><SelectValue /></SelectTrigger>
                <SelectContent>
                  {TEMPLATE_CATEGORIES.map((c) => (
                    <SelectItem key={c.value} value={c.value} className="text-xs">
                      <span className="flex flex-col"><span>{c.label}</span><span className="text-[10px] text-muted-foreground">{c.hint}</span></span>
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {draft.category === "HEAD_RANGE" ? (
                <div className="flex gap-1.5">
                  <Input className="h-8 text-xs" placeholder="from %" value={draft.minPercent} onChange={(e) => setDraft({ ...draft, minPercent: e.target.value })} />
                  <Input className="h-8 text-xs" placeholder="to %" value={draft.maxPercent} onChange={(e) => setDraft({ ...draft, maxPercent: e.target.value })} />
                </div>
              ) : <div className="text-[11px] text-muted-foreground self-center">Applies by subject score automatically.</div>}
            </div>
            <Textarea rows={2} className="text-[13px]" placeholder="e.g. A promising term — neater work and steadier revision will lift the scores further." value={draft.text} onChange={(e) => setDraft({ ...draft, text: e.target.value })} />
            <div className="flex justify-end">
              <Button size="sm" className="h-8 gap-1.5" disabled={!draft.text.trim() || busy === "add"} onClick={() => act({ action: "save-remark-template", category: draft.category, minPercent: draft.minPercent, maxPercent: draft.maxPercent, text: draft.text }, "add", "Remark template added").then(() => setDraft({ ...draft, text: "" }))}>
                <Plus className="size-3.5" /> Add remark
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* conduct & interests */}
      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><GraduationCap className="size-4" /> Conduct & interests ({conductItems.length})</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          <p className="text-xs text-muted-foreground">
            Traits the class teacher can pick for the &quot;Conduct&quot; and &quot;Interests&quot; boxes on terminal report sheets.
          </p>
          <div className="flex flex-wrap gap-1.5">
            {conductItems.length === 0 && <span className="text-sm text-muted-foreground">None yet — add the ones your school reports on.</span>}
            {conductItems.map((c) => (
              <Badge key={c.id} variant={c.kind === "INTEREST" ? "outline" : "secondary"} className="gap-1 py-1 pl-2.5 pr-1">
                {c.name}
                <button className="rounded-full hover:bg-destructive/15 p-0.5" disabled={busy === c.id} onClick={() => act({ action: "delete-conduct-item", id: c.id }, c.id, "Removed")} aria-label={`Remove ${c.name}`}>
                  <Trash2 className="size-3 text-destructive" />
                </button>
              </Badge>
            ))}
          </div>
          <div className="rounded-xl border bg-secondary/30 p-3 flex gap-2">
            <Input className="h-8 text-xs flex-1" placeholder="e.g. Polite & respectful" value={conductDraft.name} onChange={(e) => setConductDraft({ ...conductDraft, name: e.target.value })} />
            <Select value={conductDraft.kind} onValueChange={(v) => setConductDraft({ ...conductDraft, kind: v })}>
              <SelectTrigger className="h-8 w-28 text-xs"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="CONDUCT" className="text-xs">Conduct</SelectItem>
                <SelectItem value="INTEREST" className="text-xs">Interest</SelectItem>
              </SelectContent>
            </Select>
            <Button size="sm" className="h-8 gap-1.5" disabled={!conductDraft.name.trim() || busy === "addc"} onClick={() => act({ action: "save-conduct-item", name: conductDraft.name, kind: conductDraft.kind }, "addc", "Added").then(() => setConductDraft({ name: "", kind: conductDraft.kind }))}>
              <Plus className="size-3.5" /> Add
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

/* ═══════════════════════════════════════════════════════════════
   WAEC RAW-SCORE GRADING (CI3 raw_score_grade parity) — the A1–F9
   scale used by exam-marks SMS, cumulative sheets and BECE
   aggregates. CRUD + CI3 stats (pass mark = lowest markFrom ≥ 50). */
type RawGradeRow = { id: string; name: string; gradePoint: string; gradePointNumeric: number; markFrom: number; markUpto: number; comment: string | null };

function WaecGradingTab() {
  const { currentSchoolId } = useStore();
  const [data, setData] = useState<{ grades: RawGradeRow[]; stats: { total: number; highest: number; lowest: number; passMark: number } } | null>(null);
  const [editing, setEditing] = useState<RawGradeRow | "new" | null>(null);
  const [deleting, setDeleting] = useState<RawGradeRow | null>(null);
  const [busy, setBusy] = useState(false);

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<{ grades: RawGradeRow[]; stats: { total: number; highest: number; lowest: number; passMark: number } }>(`/api/school/${currentSchoolId}/ops/academics?tab=rawgrades`)
      .then(setData)
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(load, [currentSchoolId]);

  async function remove(g: RawGradeRow) {
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/academics`, { action: "rawgrade.delete", id: g.id });
      toast({ title: "Grade deleted", description: `${g.gradePoint} (${g.name}) removed from the scale.` });
      setDeleting(null);
      load();
    } catch (e: any) {
      toast({ title: "Delete failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  if (!data) return <LoadingBlock label="Loading WAEC grading scale…" />;

  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <StatCard icon={<Award className="size-4" />} label="Total grades" value={data.stats.total} tone="primary" sub="A1 … F9 bands" />
        <StatCard label="Highest mark" value={`${data.stats.highest}%`} sub="top of the scale" />
        <StatCard label="Lowest mark" value={`${data.stats.lowest}%`} sub="bottom of the scale" />
        <StatCard label="Pass mark" value={`${data.stats.passMark}%`} tone="gold" sub="lowest credit band (≥ 50)" />
      </div>

      <Card>
        <CardHeader className="pb-3 flex-row flex-wrap items-center justify-between gap-2 space-y-0">
          <div>
            <CardTitle className="text-base">WAEC raw-score grades</CardTitle>
            <p className="text-xs text-muted-foreground mt-1">Raw exam percentages map onto these bands for exam-marks SMS, cumulative sheets and the BECE aggregate (grade points summed).</p>
          </div>
          <Button size="sm" className="gap-1.5" onClick={() => setEditing("new")}><Plus className="size-4" /> Add grade</Button>
        </CardHeader>
        <CardContent className="p-0">
          {data.grades.length === 0 ? (
            <EmptyState icon={<Award className="size-5" />} title="No grades configured" hint="Add the A1–F9 WAEC bands so raw scores can be graded." />
          ) : (
            <div className="overflow-x-auto nice-scroll">
              <table className="w-full text-sm">
                <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                  <tr className="text-left border-b">
                    <th className="px-3 py-2.5 font-medium">Grade</th>
                    <th className="px-3 py-2.5 font-medium">Remark</th>
                    <th className="px-3 py-2.5 font-medium text-right">Numeric</th>
                    <th className="px-3 py-2.5 font-medium text-right">Marks</th>
                    <th className="px-3 py-2.5 font-medium hidden md:table-cell">Comment</th>
                    <th className="px-3 py-2.5"></th>
                  </tr>
                </thead>
                <tbody>
                  {data.grades.map((g) => (
                    <tr key={g.id} className="border-b last:border-0 hover:bg-secondary/30">
                      <td className="px-3 py-2.5">
                        <Badge className={cn("font-semibold", g.markFrom >= 50 ? "bg-emerald-600" : g.markFrom >= 40 ? "bg-amber-600" : "bg-red-600")}>{g.gradePoint}</Badge>
                      </td>
                      <td className="px-3 py-2.5 font-medium">{g.name}</td>
                      <td className="px-3 py-2.5 text-right tabular-nums">{g.gradePointNumeric}</td>
                      <td className="px-3 py-2.5 text-right tabular-nums">{g.markFrom}–{g.markUpto}%</td>
                      <td className="px-3 py-2.5 text-muted-foreground hidden md:table-cell">{g.comment ?? "—"}</td>
                      <td className="px-3 py-2.5 text-right whitespace-nowrap">
                        <Button size="sm" variant="outline" className="h-8 gap-1" onClick={() => setEditing(g)}><Pencil className="size-3.5" /> Edit</Button>
                        <Button size="sm" variant="ghost" className="h-8 size-8 p-0 text-destructive ml-1" onClick={() => setDeleting(g)} aria-label={`Delete ${g.gradePoint}`}><Trash2 className="size-4" /></Button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>

      <RawGradeDialog grade={editing} onOpenChange={(o) => !o && setEditing(null)} onDone={() => { setEditing(null); load(); }} />

      <AlertDialog open={!!deleting} onOpenChange={(o) => !o && setDeleting(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete grade {deleting?.gradePoint}?</AlertDialogTitle>
            <AlertDialogDescription>
              {deleting?.name} ({deleting?.markFrom}–{deleting?.markUpto}%) will be removed from the WAEC scale. Raw scores in that band will fall through to the next matching grade.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction className="bg-destructive text-destructive-foreground hover:bg-destructive/90" disabled={busy} onClick={() => deleting && remove(deleting)}>Delete</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}

function RawGradeDialog({ grade, onOpenChange, onDone }: { grade: RawGradeRow | "new" | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const isNew = grade === "new";
  const [form, setForm] = useState({ name: "", gradePoint: "", gradePointNumeric: "1", markFrom: "0", markUpto: "100", comment: "" });
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (grade && grade !== "new") {
      setForm({ name: grade.name, gradePoint: grade.gradePoint, gradePointNumeric: String(grade.gradePointNumeric), markFrom: String(grade.markFrom), markUpto: String(grade.markUpto), comment: grade.comment ?? "" });
    } else if (grade === "new") {
      setForm({ name: "CREDIT", gradePoint: "", gradePointNumeric: "1", markFrom: "50", markUpto: "54", comment: "" });
    }
  }, [grade]);

  async function submit() {
    if (!form.name.trim() || !form.gradePoint.trim()) { toast({ title: "Remark name and grade point are required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/academics`, {
        action: isNew ? "rawgrade.create" : "rawgrade.update",
        id: isNew ? undefined : (grade as RawGradeRow).id,
        name: form.name, gradePoint: form.gradePoint, gradePointNumeric: Number(form.gradePointNumeric) || 0,
        markFrom: Number(form.markFrom), markUpto: Number(form.markUpto), comment: form.comment || null,
      });
      toast({ title: isNew ? "Grade added" : "Grade updated", description: `${form.gradePoint} · ${form.name} (${form.markFrom}–${form.markUpto}%).` });
      onDone();
    } catch (e: any) {
      toast({ title: "Failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!grade} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader><DialogTitle>{isNew ? "Add WAEC grade" : `Edit grade ${grade ? grade.gradePoint : ""}`}</DialogTitle></DialogHeader>
        <div className="grid gap-3 sm:grid-cols-2">
          <div className="space-y-1.5"><Label>Grade point *</Label><Input placeholder="e.g. C6" value={form.gradePoint} onChange={(e) => setForm({ ...form, gradePoint: e.target.value.toUpperCase().slice(0, 8) })} /></div>
          <div className="space-y-1.5">
            <Label>Remark name *</Label>
            <Select value={form.name} onValueChange={(v) => setForm({ ...form, name: v })}>
              <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
              <SelectContent>
                {["EXCELLENT", "VERY GOOD", "GOOD", "CREDIT", "PASS", "FAIL"].map((n) => <SelectItem key={n} value={n}>{n.charAt(0) + n.slice(1).toLowerCase()}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5"><Label>Mark from (%)</Label><Input type="number" value={form.markFrom} onChange={(e) => setForm({ ...form, markFrom: e.target.value })} /></div>
          <div className="space-y-1.5"><Label>Mark up to (%)</Label><Input type="number" value={form.markUpto} onChange={(e) => setForm({ ...form, markUpto: e.target.value })} /></div>
          <div className="space-y-1.5"><Label>Grade numeric (BECE weight)</Label><Input type="number" value={form.gradePointNumeric} onChange={(e) => setForm({ ...form, gradePointNumeric: e.target.value })} /></div>
          <div className="space-y-1.5 sm:col-span-2"><Label>Comment</Label><Input placeholder="Shown with the grade" value={form.comment} onChange={(e) => setForm({ ...form, comment: e.target.value })} /></div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Saving…" : isNew ? "Add grade" : "Save grade"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ═══════════════════════════════════════════════════════════════
   EARLY YEARS (CRÈCHE / KG) — NaCCA developmental-domain setup:
   the proficiency rating scale (code → label) and the domains with
   their observable skills, rated per learner in the Gradebook.      */
type CrecheScaleRow = { id: string; code: string; label: string; sortOrder: number };
type CrecheSkillRow = { id: string; domainId: string; name: string; sortOrder: number };
type CrecheDomainRow = { id: string; name: string; sortOrder: number; active: boolean; skills: CrecheSkillRow[] };

function EarlyYearsTab() {
  const { currentSchoolId } = useStore();
  const [data, setData] = useState<{ scale: CrecheScaleRow[]; domains: CrecheDomainRow[] } | null>(null);
  const [busy, setBusy] = useState<string | null>(null);
  const [openDomain, setOpenDomain] = useState<string | null>(null);
  // scale draft row (new / editing)
  const [scaleDraft, setScaleDraft] = useState<{ id?: string; code: string; label: string; sortOrder: string }>({ code: "", label: "", sortOrder: "" });
  // domain draft
  const [domainDraft, setDomainDraft] = useState<{ id?: string; name: string; sortOrder: string; active: boolean } | null>(null);
  // skill drafts per domain
  const [skillDrafts, setSkillDrafts] = useState<Record<string, { id?: string; name: string; sortOrder: string }>>({});
  const [editSkill, setEditSkill] = useState<{ domainId: string; skill: CrecheSkillRow } | null>(null);

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<{ scale: CrecheScaleRow[]; domains: CrecheDomainRow[] }>(`/api/school/${currentSchoolId}/ops/academics?action=creche-setup`)
      .then(setData)
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(load, [currentSchoolId]);

  async function act(payload: Record<string, unknown>, key: string, okMsg: string) {
    if (!currentSchoolId) return false;
    setBusy(key);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/academics`, payload);
      toast({ title: okMsg });
      load();
      return true;
    } catch (e: any) {
      toast({ title: "Failed", description: e.message, variant: "destructive" });
      return false;
    } finally { setBusy(null); }
  }

  if (!data) return <LoadingBlock label="Loading early-years setup…" />;

  const activeDomains = data.domains.filter((d) => d.active).length;

  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
      {/* ── rating scale ── */}
      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-base flex items-center gap-2"><Baby className="size-4" /> Proficiency rating scale ({data.scale.length})</CardTitle>
          <p className="text-xs text-muted-foreground mt-1">
            Codes the KG / crèche teachers pick for every skill — e.g. <b>EX</b> Excellent … <b>NA</b> Needs Attention (NaCCA early-years convention). Seeded on first load.
          </p>
        </CardHeader>
        <CardContent className="space-y-3">
          <div className="rounded-xl border max-h-72 overflow-y-auto nice-scroll divide-y">
            {data.scale.length === 0 && <div className="p-4 text-center text-sm text-muted-foreground">No codes yet — add the scale below.</div>}
            {data.scale.map((s) => (
              <div key={s.id} className="p-2.5 flex items-center gap-2.5">
                <Badge className="min-w-10 justify-center font-semibold">{s.code}</Badge>
                <Input
                  className="h-8 flex-1 text-sm"
                  defaultValue={s.label}
                  onBlur={(e) => e.target.value.trim() !== s.label && act({ action: "creche-scale-save", id: s.id, code: s.code, label: e.target.value, sortOrder: s.sortOrder }, s.id, `${s.code} label updated`)}
                  aria-label={`Label for ${s.code}`}
                />
                <Input
                  type="number" className="h-8 w-16 text-sm tabular-nums" defaultValue={s.sortOrder}
                  onBlur={(e) => Number(e.target.value) !== s.sortOrder && act({ action: "creche-scale-save", id: s.id, code: s.code, label: s.label, sortOrder: Number(e.target.value) || 0 }, s.id, `${s.code} order updated`)}
                  aria-label={`Sort order for ${s.code}`}
                />
                <Button size="sm" variant="ghost" className="size-8 p-0 text-destructive" disabled={busy === s.id} onClick={() => act({ action: "creche-scale-delete", id: s.id }, s.id, `${s.code} removed`)} aria-label={`Delete ${s.code}`}><Trash2 className="size-4" /></Button>
              </div>
            ))}
          </div>
          {/* add form */}
          <div className="rounded-xl border bg-secondary/30 p-3 flex flex-wrap gap-2">
            <Input className="h-8 w-20 font-semibold uppercase" placeholder="Code" value={scaleDraft.code} onChange={(e) => setScaleDraft({ ...scaleDraft, code: e.target.value.toUpperCase().slice(0, 8) })} aria-label="New rating code" />
            <Input className="h-8 flex-1 min-w-36" placeholder="Label (e.g. Emerging)" value={scaleDraft.label} onChange={(e) => setScaleDraft({ ...scaleDraft, label: e.target.value })} aria-label="New rating label" />
            <Input className="h-8 w-16 tabular-nums" type="number" placeholder="Order" value={scaleDraft.sortOrder} onChange={(e) => setScaleDraft({ ...scaleDraft, sortOrder: e.target.value })} aria-label="Sort order" />
            <Button size="sm" className="h-8 gap-1.5" disabled={!scaleDraft.code.trim() || !scaleDraft.label.trim() || busy === "scale-add"}
              onClick={async () => { const ok = await act({ action: "creche-scale-save", ...scaleDraft, sortOrder: Number(scaleDraft.sortOrder) || 0 }, "scale-add", `${scaleDraft.code} added to the scale`); if (ok) setScaleDraft({ code: "", label: "", sortOrder: "" }); }}>
              <Plus className="size-3.5" /> Add
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* ── domains & skills ── */}
      <Card>
        <CardHeader className="pb-3 flex-row flex-wrap items-center justify-between gap-2 space-y-0">
          <div>
            <CardTitle className="text-base">Developmental domains ({activeDomains} active)</CardTitle>
            <p className="text-xs text-muted-foreground mt-1">NaCCA early-years domains, each with its observable skills / indicators. Expand a domain to manage its skills.</p>
          </div>
          <Button size="sm" className="gap-1.5" onClick={() => setDomainDraft({ name: "", sortOrder: String(data.domains.length + 1), active: true })}><Plus className="size-4" /> Add domain</Button>
        </CardHeader>
        <CardContent className="space-y-2.5">
          {data.domains.length === 0 && <div className="rounded-lg border border-dashed p-4 text-center text-sm text-muted-foreground">No domains yet — add the five NaCCA domains or reload to auto-seed defaults.</div>}
          {data.domains.map((d) => {
            const open = openDomain === d.id;
            const draft = skillDrafts[d.id] ?? { name: "", sortOrder: "" };
            return (
              <div key={d.id} className="rounded-xl border">
                <div className="flex flex-wrap items-center gap-2 p-2.5">
                  <button className="flex items-center gap-1.5 flex-1 min-w-40 text-left" onClick={() => setOpenDomain(open ? null : d.id)} aria-expanded={open}>
                    {open ? <ChevronDown className="size-4 text-muted-foreground" /> : <ChevronRight className="size-4 text-muted-foreground" />}
                    <span className="font-medium text-sm">{d.name}</span>
                    <Badge variant="secondary" className="text-[10px]">{d.skills.length} skills</Badge>
                    {!d.active && <Badge variant="outline" className="text-[10px] text-muted-foreground">inactive</Badge>}
                  </button>
                  <Checkbox
                    id={`dom-active-${d.id}`} checked={d.active}
                    onCheckedChange={(v) => act({ action: "creche-domain-save", id: d.id, name: d.name, sortOrder: d.sortOrder, active: v === true }, d.id, v ? `${d.name} activated` : `${d.name} deactivated (hidden from rating & reports)`)}
                  />
                  <Label htmlFor={`dom-active-${d.id}`} className="text-xs text-muted-foreground font-normal cursor-pointer">active</Label>
                  <Button size="sm" variant="ghost" className="h-7 px-2 text-[11px] gap-1" onClick={() => setDomainDraft({ id: d.id, name: d.name, sortOrder: String(d.sortOrder), active: d.active })}><Pencil className="size-3" /> Rename</Button>
                  <Button size="sm" variant="ghost" className="size-7 p-0 text-destructive" disabled={busy === d.id} onClick={() => act({ action: "creche-domain-delete", id: d.id }, d.id, `${d.name} deleted with its ${d.skills.length} skills`)} aria-label={`Delete ${d.name}`}><Trash2 className="size-3.5" /></Button>
                </div>
                {open && (
                  <div className="border-t px-2.5 py-2 space-y-1.5">
                    {d.skills.map((sk) => (
                      <div key={sk.id} className="flex items-center gap-2 group">
                        <span className="text-[11px] text-muted-foreground tabular-nums w-6 text-right">{sk.sortOrder}</span>
                        {editSkill?.skill.id === sk.id ? (
                          <>
                            <Input className="h-8 flex-1 text-sm" value={editSkill.skill.name} onChange={(e) => setEditSkill({ domainId: d.id, skill: { ...sk, name: e.target.value } })} aria-label="Skill name" autoFocus />
                            <Input className="h-8 w-14 text-sm tabular-nums" type="number" value={editSkill.skill.sortOrder} onChange={(e) => setEditSkill({ domainId: d.id, skill: { ...sk, sortOrder: Number(e.target.value) || 0 } })} aria-label="Skill order" />
                            <Button size="sm" className="h-8 gap-1" disabled={busy === sk.id} onClick={async () => { const ok = await act({ action: "creche-skill-save", id: sk.id, domainId: d.id, name: editSkill.skill.name, sortOrder: editSkill.skill.sortOrder }, sk.id, "Skill updated"); if (ok) setEditSkill(null); }}><Save className="size-3.5" /> Save</Button>
                            <Button size="sm" variant="ghost" className="h-8" onClick={() => setEditSkill(null)}>Cancel</Button>
                          </>
                        ) : (
                          <>
                            <span className="flex-1 text-sm">{sk.name}</span>
                            <Button size="sm" variant="ghost" className="h-7 size-7 p-0 opacity-0 group-hover:opacity-100" onClick={() => setEditSkill({ domainId: d.id, skill: sk })} aria-label={`Edit ${sk.name}`}><PencilLine className="size-3.5" /></Button>
                            <Button size="sm" variant="ghost" className="h-7 size-7 p-0 text-destructive opacity-0 group-hover:opacity-100" disabled={busy === sk.id} onClick={() => act({ action: "creche-skill-delete", id: sk.id }, sk.id, "Skill removed")} aria-label={`Delete ${sk.name}`}><Trash2 className="size-3.5" /></Button>
                          </>
                        )}
                      </div>
                    ))}
                    {d.skills.length === 0 && <p className="text-xs text-muted-foreground px-1">No skills yet for this domain.</p>}
                    <div className="flex gap-2 pt-1">
                      <Input className="h-8 flex-1 text-sm" placeholder="New skill / indicator (e.g. Shares toys with peers)" value={draft.name} onChange={(e) => setSkillDrafts({ ...skillDrafts, [d.id]: { ...draft, name: e.target.value } })} aria-label={`New skill for ${d.name}`} />
                      <Input className="h-8 w-16 text-sm tabular-nums" type="number" placeholder="Order" value={draft.sortOrder} onChange={(e) => setSkillDrafts({ ...skillDrafts, [d.id]: { ...draft, sortOrder: e.target.value } })} aria-label="New skill order" />
                      <Button size="sm" className="h-8 gap-1.5" disabled={!draft.name.trim() || busy === `skill-add-${d.id}`}
                        onClick={async () => { const ok = await act({ action: "creche-skill-save", domainId: d.id, name: draft.name, sortOrder: Number(draft.sortOrder) || d.skills.length + 1 }, `skill-add-${d.id}`, "Skill added"); if (ok) setSkillDrafts({ ...skillDrafts, [d.id]: { name: "", sortOrder: "" } }); }}>
                        <Plus className="size-3.5" /> Add skill
                      </Button>
                    </div>
                  </div>
                )}
              </div>
            );
          })}
        </CardContent>
      </Card>

      {/* domain rename / add dialog */}
      <Dialog open={!!domainDraft} onOpenChange={(o) => !o && setDomainDraft(null)}>
        <DialogContent>
          <DialogHeader><DialogTitle>{domainDraft?.id ? "Rename domain" : "Add developmental domain"}</DialogTitle></DialogHeader>
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5 sm:col-span-2"><Label>Domain name *</Label><Input placeholder="e.g. Communication & Language" value={domainDraft?.name ?? ""} onChange={(e) => setDomainDraft(domainDraft ? { ...domainDraft, name: e.target.value } : null)} /></div>
            <div className="space-y-1.5"><Label>Sort order</Label><Input type="number" value={domainDraft?.sortOrder ?? ""} onChange={(e) => setDomainDraft(domainDraft ? { ...domainDraft, sortOrder: e.target.value } : null)} /></div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDomainDraft(null)}>Cancel</Button>
            <Button disabled={!domainDraft?.name.trim() || busy === "domain-save"} onClick={async () => {
              if (!domainDraft) return;
              const ok = await act({ action: "creche-domain-save", id: domainDraft.id, name: domainDraft.name, sortOrder: Number(domainDraft.sortOrder) || 0, active: domainDraft.active }, "domain-save", domainDraft.id ? "Domain updated" : "Domain added");
              if (ok) setDomainDraft(null);
            }}>{busy === "domain-save" ? "Saving…" : domainDraft?.id ? "Save domain" : "Add domain"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

/* ═══════════════════════════════════════════════════════════════
   ASSESSMENT & SBA CONFIG — the four SBA column maxes (sub-total
   warns when ≠ 60), class-score weight % and the portfolio
   auto-fill weight % (system_settings parity, SchoolSetting KV).  */
function SbaConfigTab() {
  const { currentSchoolId } = useStore();
  const [cfg, setCfg] = useState<Record<string, number> | null>(null);
  const [form, setForm] = useState({ classTestMax: "", groupWorkMax: "", test2Max: "", projectMax: "", classScoreWeightPct: "", portfolioAutoFillPct: "" });
  const [busy, setBusy] = useState(false);
  const [acknowledged, setAcknowledged] = useState(false);

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<{ config: Record<string, number> }>(`/api/school/${currentSchoolId}/ops/academics?action=sba-config`)
      .then((d) => {
        const c = d.config;
        setCfg(c);
        setForm({
          classTestMax: String(c["sba.classTestMax"] ?? ""), groupWorkMax: String(c["sba.groupWorkMax"] ?? ""),
          test2Max: String(c["sba.test2Max"] ?? ""), projectMax: String(c["sba.projectMax"] ?? ""),
          classScoreWeightPct: String(c["sba.classScoreWeightPct"] ?? ""), portfolioAutoFillPct: String(c["sba.portfolioAutoFillPct"] ?? ""),
        });
      })
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(load, [currentSchoolId]);

  const subtotal = Number(form.classTestMax || 0) + Number(form.groupWorkMax || 0) + Number(form.test2Max || 0) + Number(form.projectMax || 0);
  const misaligned = subtotal !== 60;
  const warn = misaligned && !acknowledged;

  async function save() {
    if (!currentSchoolId) return;
    setBusy(true);
    try {
      const res = await apiPost<{ config: Record<string, number> }>(`/api/school/${currentSchoolId}/ops/academics`, {
        action: "sba-config",
        classTestMax: Number(form.classTestMax), groupWorkMax: Number(form.groupWorkMax),
        test2Max: Number(form.test2Max), projectMax: Number(form.projectMax),
        classScoreWeightPct: Number(form.classScoreWeightPct), portfolioAutoFillPct: Number(form.portfolioAutoFillPct),
      });
      setCfg(res.config);
      setAcknowledged(false);
      toast({ title: "SBA configuration saved", description: `Sub-total ${subtotal} · class score ${form.classScoreWeightPct}% · exam ${100 - Number(form.classScoreWeightPct || 0)}%.` });
    } catch (e: any) {
      toast({ title: "Save failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  if (!cfg) return <LoadingBlock label="Loading SBA configuration…" />;

  const maxFields: { key: keyof typeof form; label: string; hint: string }[] = [
    { key: "classTestMax", label: "Class Test", hint: "usually 20" },
    { key: "groupWorkMax", label: "Group Work", hint: "usually 10" },
    { key: "test2Max", label: "Test 2", hint: "usually 20" },
    { key: "projectMax", label: "Project", hint: "usually 10" },
  ];

  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-base flex items-center gap-2"><Sigma className="size-4" /> SBA column maxima</CardTitle>
          <p className="text-xs text-muted-foreground mt-1">
            The four fixed School-Based Assessment columns on the SBA sheet (Gradebook → SBA sheet). GES convention sums to <b>60</b> — the class score carries the rest of the term weight.
          </p>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-2 gap-3">
            {maxFields.map((f) => (
              <div key={f.key} className="space-y-1.5">
                <Label>{f.label} <span className="text-muted-foreground font-normal">(max)</span></Label>
                <Input type="number" min={0} max={100} value={form[f.key]} onChange={(e) => setForm({ ...form, [f.key]: e.target.value })} placeholder={f.hint} />
              </div>
            ))}
          </div>
          <div className={`rounded-xl border p-3 flex flex-wrap items-center justify-between gap-2 ${misaligned ? "border-amber-400 bg-amber-50/60 dark:bg-amber-950/20" : "border-emerald-400/60 bg-emerald-50/50 dark:bg-emerald-950/20"}`}>
            <div className="text-sm">
              <span className="text-muted-foreground">SBA sub-total (out of):</span> <b className="tabular-nums text-base">{subtotal}</b>
              {!misaligned && <span className="text-emerald-700 dark:text-emerald-400 text-xs ml-2">✓ matches the GES 60-mark convention</span>}
            </div>
            {misaligned && (
              <label className="flex items-center gap-2 text-xs text-amber-700 dark:text-amber-400">
                <Checkbox checked={acknowledged} onCheckedChange={(v) => setAcknowledged(v === true)} />
                Proceed with a non-standard sub-total
              </label>
            )}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-base">Term weighting &amp; portfolio auto-fill</CardTitle>
          <p className="text-xs text-muted-foreground mt-1">
            Class score = SBA sub-total × weight ÷ actual max (capped at the weight). Exam score = raw exam × (100 − weight) ÷ 100.
          </p>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label>Class score weight %</Label>
              <Input type="number" min={0} max={99} value={form.classScoreWeightPct} onChange={(e) => setForm({ ...form, classScoreWeightPct: e.target.value })} placeholder="50" />
              <p className="text-[11px] text-muted-foreground">GES legacy is 50 (class 50 / exam 50); enter 30 for the 30/70 convention.</p>
            </div>
            <div className="space-y-1.5">
              <Label>Portfolio auto-fill weight %</Label>
              <Input type="number" min={0} max={100} value={form.portfolioAutoFillPct} onChange={(e) => setForm({ ...form, portfolioAutoFillPct: e.target.value })} placeholder="30" />
              <p className="text-[11px] text-muted-foreground">Auto-filled class test = portfolio term average % × this weight ÷ 100, clamped to the Class Test max.</p>
            </div>
          </div>
          <div className="rounded-xl border bg-secondary/40 p-3 text-sm space-y-1.5">
            <div className="flex justify-between"><span className="text-muted-foreground">Effective split</span><b>Class {form.classScoreWeightPct || 0}% · Exam {100 - Number(form.classScoreWeightPct || 0)}%</b></div>
            <div className="flex justify-between"><span className="text-muted-foreground">Auto-fill ceiling (Class Test)</span><b className="tabular-nums">min({form.classTestMax || 0}, avg% × {form.portfolioAutoFillPct || 0}%)</b></div>
          </div>
          <Button className="gap-1.5" onClick={save} disabled={busy || !!warn}><Save className="size-4" /> {busy ? "Saving…" : "Save configuration"}</Button>
          {warn && <p className="text-xs text-amber-700 dark:text-amber-400">Tick “Proceed with a non-standard sub-total” above to save a sub-total other than 60.</p>}
        </CardContent>
      </Card>
    </div>
  );
}

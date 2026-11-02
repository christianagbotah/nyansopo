"use client";
/* iedupro parity — Discount workspace extras:
   · DiscountProfilesTab — named rule-sets (invoice / daily-fees categories,
     percent or fixed, per-class value overrides) assigned to learners.
   · DiscountApprovalsTab — assignment review queue (pending → approve/reject)
     with a full audit trail. */
import { useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { StatCard, StatusBadge, EmptyState } from "@/components/ui-helpers";
import { fmtCedi } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { Layers, Plus, Pencil, Trash2, CheckCircle2, XCircle, UserPlus, FileSpreadsheet, Ticket, Power, Utensils, Sparkles } from "lucide-react";

/** Human chips for a daily-service profile scope ("All daily fees" / "Lunch + Breakfast"). */
export function profileScopeChips(p: { category: string; serviceIds: string; feeItemName: string }, services: { id: string; name: string }[]): string[] {
  if (p.category !== "DAILY_SERVICE") return p.feeItemName && p.feeItemName !== "*" ? [p.feeItemName] : ["All fee items"];
  if (!p.serviceIds || p.serviceIds === "*") return ["All daily fees"];
  const names = p.serviceIds.split(",").map((id) => services.find((s) => s.id === id)?.name).filter(Boolean) as string[];
  return names.length ? names : ["All daily fees"];
}

// ── shared types (consumed by fees.tsx) ─────────────────────
export type ProfileRule = { id: string; classroomName: string | null; feeItemName: string | null; value: number };
export type ProfileRow = { id: string; name: string; category: string; method: string; value: number; feeItemName: string; serviceIds: string; description: string | null; active: boolean; applied: number; rules: ProfileRule[] };
export type ApplicationRow = {
  id: string; discountId: string | null; profileId: string | null; discountName: string; type: string; value: number; scope: string;
  serviceId: string | null; student: string; studentIndex: string | null; classroom: string | null; studentId: string; guardianPhone: string;
  status: string; reason: string | null; appliedByName: string; approvedByName: string | null; decidedAt: string | null; createdAt: string;
};
export type DiscountData = {
  discounts: { id: string; name: string; type: string; value: number; scope: string; autoApply: boolean; active: boolean; notes: string | null; applied: number }[];
  profiles: ProfileRow[];
  applications: ApplicationRow[];
  counts: { pending: number; approved: number; rejected: number };
  services: { id: string; name: string; price: number }[];
  classrooms: { id: string; name: string }[];
};

// ── Profiles tab ──────────────────────────────────────────────
export function DiscountProfilesTab({ data, students, onRefresh }: { data: DiscountData | null; students: { id: string; name: string; classroom: string | null }[]; onRefresh: () => void }) {
  const { currentSchoolId } = useStore();
  const [edit, setEdit] = useState<ProfileRow | "new" | null>(null);
  const [applyFor, setApplyFor] = useState<ProfileRow | null>(null);
  const [categoryFilter, setCategoryFilter] = useState("ALL");
  const [statusFilter, setStatusFilter] = useState("ALL");

  const profiles = useMemo(() => (data?.profiles ?? []).filter((p) =>
    (categoryFilter === "ALL" || p.category === categoryFilter) &&
    (statusFilter === "ALL" || (statusFilter === "ACTIVE" ? p.active : !p.active))
  ), [data, categoryFilter, statusFilter]);

  const active = (data?.profiles ?? []).filter((p) => p.active).length;
  const invoice = (data?.profiles ?? []).filter((p) => p.category === "INVOICE").length;
  const daily = (data?.profiles ?? []).filter((p) => p.category === "DAILY_SERVICE").length;

  async function toggle(p: ProfileRow) {
    await apiPost(`/api/school/${currentSchoolId}/ops/discounts`, { action: "toggle-profile", id: p.id });
    toast({ title: p.active ? "Profile paused" : "Profile reactivated" });
    onRefresh();
  }

  return (
    <div className="space-y-4">
      <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard icon={<Layers className="size-4" />} label="Total profiles" value={String(data?.profiles.length ?? 0)} />
        <StatCard icon={<CheckCircle2 className="size-4" />} label="Active profiles" value={String(active)} tone="primary" />
        <StatCard icon={<FileSpreadsheet className="size-4" />} label="Invoice discounts" value={String(invoice)} />
        <StatCard icon={<Ticket className="size-4" />} label="Daily fees discounts" value={String(daily)} tone="gold" />
      </div>

      <div className="flex flex-wrap items-center justify-between gap-2">
        <div className="flex flex-wrap gap-2">
          <Select value={categoryFilter} onValueChange={setCategoryFilter}>
            <SelectTrigger className="w-44 h-8 text-xs"><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem value="ALL">All categories</SelectItem>
              <SelectItem value="INVOICE">📄 Invoice discounts</SelectItem>
              <SelectItem value="DAILY_SERVICE">📅 Daily fees discounts</SelectItem>
            </SelectContent>
          </Select>
          <Select value={statusFilter} onValueChange={setStatusFilter}>
            <SelectTrigger className="w-36 h-8 text-xs"><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem value="ALL">All status</SelectItem>
              <SelectItem value="ACTIVE">✅ Active only</SelectItem>
              <SelectItem value="INACTIVE">⛔ Inactive only</SelectItem>
            </SelectContent>
          </Select>
        </div>
        <Button size="sm" className="gap-1.5" onClick={() => setEdit("new")}><Plus className="size-4" /> Create profile</Button>
      </div>

      <Card>
        <CardContent className="p-0">
          <div className="overflow-x-auto nice-scroll">
            <table className="w-full text-sm">
              <thead className="bg-secondary/70">
                <tr className="text-left border-b text-xs uppercase tracking-wide text-muted-foreground">
                  <th className="px-3 py-2.5 font-medium">Profile name</th>
                  <th className="px-3 py-2.5 font-medium hidden md:table-cell">Category</th>
                  <th className="px-3 py-2.5 font-medium">Applies to</th>
                  <th className="px-3 py-2.5 font-medium hidden lg:table-cell">Method</th>
                  <th className="px-3 py-2.5 font-medium">Value</th>
                  <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Class overrides</th>
                  <th className="px-3 py-2.5 font-medium">Status</th>
                  <th className="px-3 py-2.5 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {profiles.length === 0 && <tr><td colSpan={7}><EmptyState icon={<Layers className="size-5" />} title="No discount profiles" hint="Profiles are named rule-sets — e.g. 'Tuition discount for PTA executives' with per-class values." /></td></tr>}
                {profiles.map((p) => (
                  <tr key={p.id} className={`border-b last:border-0 hover:bg-secondary/30 ${p.active ? "" : "opacity-60"}`}>
                    <td className="px-3 py-2.5">
                      <div className="font-medium">{p.name}</div>
                      {p.description && <div className="text-xs text-muted-foreground max-w-72 truncate">{p.description}</div>}
                    </td>
                    <td className="px-3 py-2.5 hidden md:table-cell"><Badge variant="outline" className="text-[10px]">{p.category === "INVOICE" ? "Invoice" : "Daily fees"}</Badge></td>
                    <td className="px-3 py-2.5">
                      <div className="flex flex-wrap gap-1 max-w-52">
                        {profileScopeChips(p, data?.services ?? []).slice(0, 3).map((c) => (
                          <Badge key={c} variant={p.category === "DAILY_SERVICE" ? "secondary" : "outline"} className="text-[10px] font-normal">{c}</Badge>
                        ))}
                        {profileScopeChips(p, data?.services ?? []).length > 3 && <Badge variant="outline" className="text-[10px]">+{profileScopeChips(p, data?.services ?? []).length - 3}</Badge>}
                      </div>
                    </td>
                    <td className="px-3 py-2.5 hidden lg:table-cell text-muted-foreground">{p.method === "PERCENT" ? "📊 Percentage" : "💰 Fixed"}</td>
                    <td className="px-3 py-2.5 tabular-nums font-medium">{p.method === "PERCENT" ? `${p.value}%` : fmtCedi(p.value)}</td>
                    <td className="px-3 py-2.5 hidden sm:table-cell">
                      {p.rules.length === 0 ? <span className="text-muted-foreground text-xs">—</span> : (
                        <div className="flex flex-wrap gap-1">
                          {p.rules.slice(0, 3).map((r) => (
                            <Badge key={r.id} variant="secondary" className="text-[10px]">{r.classroomName ?? "All classes"}: {p.method === "PERCENT" ? `${r.value}%` : fmtCedi(r.value)}</Badge>
                          ))}
                          {p.rules.length > 3 && <Badge variant="outline" className="text-[10px]">+{p.rules.length - 3}</Badge>}
                        </div>
                      )}
                    </td>
                    <td className="px-3 py-2.5"><Badge className={p.active ? "bg-emerald-600" : ""} variant={p.active ? "default" : "secondary"}>{p.active ? "Active" : "Inactive"}</Badge></td>
                    <td className="px-3 py-2.5 text-right whitespace-nowrap">
                      <Button size="sm" variant="ghost" className="h-7 gap-1" title="Assign to learner" onClick={() => setApplyFor(p)}><UserPlus className="size-3.5" /> Assign</Button>
                      <Button size="sm" variant="ghost" className="h-7 gap-1" onClick={() => setEdit(p)}><Pencil className="size-3.5" /> Edit</Button>
                      <Button size="sm" variant="ghost" className="h-7 gap-1" title={p.active ? "Pause" : "Reactivate"} onClick={() => toggle(p)}><Power className="size-3.5" /></Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <ProfileDialog profile={edit} classrooms={data?.classrooms ?? []} services={data?.services ?? []} onOpenChange={(o) => !o && setEdit(null)} onDone={() => { setEdit(null); onRefresh(); }} />
      <ApplyProfileDialog profile={applyFor} data={data} students={students} onOpenChange={(o) => !o && setApplyFor(null)} onDone={() => { setApplyFor(null); onRefresh(); }} />
    </div>
  );
}

// ── Approvals tab ─────────────────────────────────────────────
export function DiscountApprovalsTab({ data, onRefresh, defaultFilter = "ALL" }: { data: DiscountData | null; onRefresh: () => void; defaultFilter?: "ALL" | "PENDING" | "ACTIVE" | "REJECTED" | "REVOKED" }) {
  const { currentSchoolId } = useStore();
  const [filter, setFilter] = useState<"ALL" | "PENDING" | "ACTIVE" | "REJECTED" | "REVOKED">(defaultFilter);
  const [busyId, setBusyId] = useState<string | null>(null);

  const rows = useMemo(() => (data?.applications ?? []).filter((a) => {
    if (filter === "ALL") return true;
    if (filter === "PENDING") return a.status === "PENDING";
    if (filter === "ACTIVE") return a.status === "ACTIVE" || a.status === "APPROVED";
    return a.status === filter;
  }), [data, filter]);

  async function decide(applicationId: string, action: "approve" | "reject") {
    setBusyId(applicationId);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/discounts`, { action, applicationId });
      toast({ title: action === "approve" ? "Assignment approved" : "Assignment rejected" });
      onRefresh();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusyId(null); }
  }

  return (
    <div className="space-y-4">
      <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard icon={<Badge className="bg-amber-500 text-white"><Layers className="size-4" /></Badge>} label="Pending review" value={String(data?.counts.pending ?? 0)} tone={data?.counts.pending ? "gold" : "default"} />
        <StatCard icon={<CheckCircle2 className="size-4" />} label="Approved" value={String(data?.counts.approved ?? 0)} tone="primary" />
        <StatCard icon={<XCircle className="size-4" />} label="Rejected" value={String(data?.counts.rejected ?? 0)} tone="danger" />
        <StatCard icon={<Ticket className="size-4" />} label="Total assignments" value={String(data?.applications.length ?? 0)} />
      </div>

      <div className="flex flex-wrap gap-1.5">
        {(["ALL", "PENDING", "ACTIVE", "REJECTED", "REVOKED"] as const).map((f) => (
          <Button key={f} size="sm" variant={filter === f ? "default" : "outline"} className="h-8 text-xs" onClick={() => setFilter(f)}>
            {f === "ALL" ? "All" : f === "PENDING" ? "Pending" : f === "ACTIVE" ? "Approved" : f === "REJECTED" ? "Rejected" : "Revoked"}
          </Button>
        ))}
      </div>

      <Card>
        <CardContent className="p-0">
          <div className="overflow-x-auto nice-scroll max-h-[520px]">
            <table className="w-full text-sm">
              <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                <tr className="text-left border-b text-xs uppercase tracking-wide text-muted-foreground">
                  <th className="px-3 py-2.5 font-medium">Date</th>
                  <th className="px-3 py-2.5 font-medium">Learner / assignment</th>
                  <th className="px-3 py-2.5 font-medium hidden lg:table-cell">Reason</th>
                  <th className="px-3 py-2.5 font-medium hidden md:table-cell">Applied by</th>
                  <th className="px-3 py-2.5 font-medium">Status</th>
                  <th className="px-3 py-2.5 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {rows.length === 0 && <tr><td colSpan={6}><EmptyState title={filter === "PENDING" ? "Nothing pending — all caught up" : "No assignments in this view"} /></td></tr>}
                {rows.map((a) => (
                  <tr key={a.id} className={`border-b last:border-0 hover:bg-secondary/30 ${a.status === "REJECTED" || a.status === "REVOKED" ? "opacity-60" : ""}`}>
                    <td className="px-3 py-2.5 text-xs text-muted-foreground whitespace-nowrap">{new Date(a.createdAt).toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" })}</td>
                    <td className="px-3 py-2.5">
                      <div className="font-medium">{a.student}{a.classroom && <span className="text-muted-foreground font-normal"> · {a.classroom}</span>}</div>
                      <div className="text-xs text-muted-foreground">
                        {a.scope === "DAILY_SERVICE" ? "Daily fees" : "Invoice"} · {a.discountName} <Badge variant="secondary" className="text-[10px] ml-0.5">{a.type === "PERCENT" ? `${a.value}%` : fmtCedi(a.value)}</Badge>
                      </div>
                    </td>
                    <td className="px-3 py-2.5 hidden lg:table-cell text-muted-foreground text-xs max-w-64 truncate">{a.reason ?? "—"}</td>
                    <td className="px-3 py-2.5 hidden md:table-cell text-xs text-muted-foreground">
                      {a.appliedByName}
                      {a.approvedByName && a.approvedByName !== a.appliedByName && <div className="text-[10px]">approved by {a.approvedByName}</div>}
                    </td>
                    <td className="px-3 py-2.5"><StatusBadge status={a.status === "ACTIVE" ? "APPROVED" : a.status} /></td>
                    <td className="px-3 py-2.5 text-right whitespace-nowrap">
                      {a.status === "PENDING" ? (
                        <>
                          <Button size="sm" className="h-7 gap-1" disabled={busyId === a.id} onClick={() => decide(a.id, "approve")}><CheckCircle2 className="size-3.5" /> Approve</Button>
                          <Button size="sm" variant="ghost" className="h-7 gap-1 text-destructive" disabled={busyId === a.id} onClick={() => decide(a.id, "reject")}><XCircle className="size-3.5" /> Reject</Button>
                        </>
                      ) : (
                        <span className="text-xs text-muted-foreground">
                          {a.decidedAt ? new Date(a.decidedAt).toLocaleDateString("en-GB", { day: "numeric", month: "short" }) : ""}
                        </span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

// ── Profile create/edit dialog with per-class rules editor ────
function ProfileDialog({ profile, classrooms, services, onOpenChange, onDone }: {
  profile: ProfileRow | "new" | null; classrooms: { id: string; name: string }[]; services: { id: string; name: string; price: number }[];
  onOpenChange: (o: boolean) => void; onDone: () => void;
}) {
  const { currentSchoolId } = useStore();
  const isNew = profile === "new";
  const [name, setName] = useState("");
  const [category, setCategory] = useState("INVOICE");
  const [method, setMethod] = useState("PERCENT");
  const [value, setValue] = useState("");
  const [description, setDescription] = useState("");
  const [rules, setRules] = useState<ProfileRule[]>([]);
  // DAILY_SERVICE scope: all services, or an explicit selection (lunch-only, classes-only, …)
  const [allServices, setAllServices] = useState(true);
  const [pickedServices, setPickedServices] = useState<string[]>([]);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (profile && profile !== "new") {
      setName(profile.name); setCategory(profile.category); setMethod(profile.method);
      setValue(String(profile.value)); setDescription(profile.description ?? ""); setRules(profile.rules);
      if (profile.category === "DAILY_SERVICE" && profile.serviceIds && profile.serviceIds !== "*") {
        setAllServices(false);
        setPickedServices(profile.serviceIds.split(",").map((s) => s.trim()).filter(Boolean));
      } else { setAllServices(true); setPickedServices([]); }
    } else { setName(""); setCategory("INVOICE"); setMethod("PERCENT"); setValue(""); setDescription(""); setRules([]); setAllServices(true); setPickedServices([]); }
  }, [profile]);

  const isDaily = category === "DAILY_SERVICE";

  // live preview: each covered service's price after this profile is applied
  const preview = useMemo(() => {
    if (!isDaily) return [];
    const v = Number(value) || 0;
    const covered = allServices ? services : services.filter((s) => pickedServices.includes(s.id));
    return covered.map((s) => ({
      name: s.name,
      standard: s.price,
      after: method === "PERCENT" ? Math.max(0, Math.round(s.price * (1 - Math.min(100, v) / 100))) : Math.max(0, s.price - v),
    }));
  }, [isDaily, allServices, pickedServices, services, method, value]);

  async function submit() {
    if (!name.trim() || !Number(value)) { toast({ title: "Profile name and value are required" }); return; }
    if (isDaily && !allServices && pickedServices.length === 0) { toast({ title: "Pick at least one daily service (or choose all)" }); return; }
    setBusy(true);
    try {
      const res = await apiPost<{ profile: { id: string } }>(`/api/school/${currentSchoolId}/ops/discounts`, {
        action: "save-profile", id: isNew ? undefined : (profile as ProfileRow).id,
        name, category, method, value: Number(value), description, active: isNew ? true : (profile as ProfileRow).active,
        serviceIds: isDaily && !allServices ? pickedServices : undefined,
      });
      // persist rule edits/additions for the saved profile
      const pid = res.profile?.id ?? (profile as ProfileRow)?.id;
      for (const r of rules) await apiPost(`/api/school/${currentSchoolId}/ops/discounts`, {
        action: "save-rule", profileId: pid, id: r.id.startsWith("tmp-") ? undefined : r.id,
        classroomName: r.classroomName ?? undefined, value: r.value,
      });
      toast({ title: isNew ? "Profile created" : "Profile updated" });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!profile} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto nice-scroll">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><Layers className="size-4 text-primary" /> {isNew ? "Create discount profile" : "Edit discount profile"}</DialogTitle>
          <DialogDescription>A named rule-set assigned to learners — e.g. &quot;Full lunch support — needy families&quot; or &quot;Tuition discount — PTA executives&quot;.</DialogDescription>
        </DialogHeader>
        <div className="space-y-4">
          <div className="grid sm:grid-cols-2 gap-3">
            <div className="space-y-1.5 sm:col-span-2">
              <Label>Profile name</Label>
              <Input value={name} onChange={(e) => setName(e.target.value)} placeholder={isDaily ? "e.g. Half-price lunch — staff children" : "e.g. Tuition fee discount — PTA executives"} />
            </div>
            <div className="space-y-1.5">
              <Label>Category</Label>
              <Select value={category} onValueChange={setCategory}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="INVOICE">📄 Invoice (termly fees)</SelectItem>
                  <SelectItem value="DAILY_SERVICE">📅 Daily fees</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Method</Label>
              <Select value={method} onValueChange={setMethod}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="PERCENT">📊 Percentage</SelectItem>
                  <SelectItem value="FIXED">💰 Fixed amount (cedi)</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>{method === "PERCENT" ? "Value (% off)" : "Value (GH₵ off)"}</Label>
              <Input type="number" min="0" value={value} onChange={(e) => setValue(e.target.value)} placeholder={method === "PERCENT" ? "e.g. 15" : "e.g. 6.50"} />
              {method === "PERCENT" && (
                <div className="flex flex-wrap gap-1.5 pt-1">
                  {[
                    { l: "Full (100%)", v: 100 }, { l: "Half (50%)", v: 50 }, { l: "Quarter (25%)", v: 25 }, { l: "10%", v: 10 },
                  ].map((p) => (
                    <button key={p.v} type="button" onClick={() => setValue(String(p.v))}
                      className={`rounded-full border px-2.5 py-0.5 text-[11px] transition-colors ${Number(value) === p.v ? "border-primary bg-primary text-primary-foreground" : "hover:bg-secondary"}`}>
                      {p.l}
                    </button>
                  ))}
                </div>
              )}
            </div>
            <div className="space-y-1.5">
              <Label>Validity note (optional)</Label>
              <Input value={description} onChange={(e) => setDescription(e.target.value)} placeholder="Who qualifies and the evidence required" />
            </div>
          </div>

          {isDaily && (
            <div className="space-y-2 rounded-lg border p-3 bg-secondary/30">
              <div className="flex items-center justify-between">
                <Label className="flex items-center gap-1.5 text-sm"><Utensils className="size-3.5 text-primary" /> Which daily fees does this affect?</Label>
              </div>
              <div className="flex flex-wrap gap-1.5">
                <button type="button" onClick={() => setAllServices(true)}
                  className={`rounded-full border px-3 py-1 text-xs transition-colors ${allServices ? "border-primary bg-primary text-primary-foreground" : "hover:bg-secondary"}`}>
                  All daily fees
                </button>
                <button type="button" onClick={() => setAllServices(false)}
                  className={`rounded-full border px-3 py-1 text-xs transition-colors ${!allServices ? "border-primary bg-primary text-primary-foreground" : "hover:bg-secondary"}`}>
                  Selected services only
                </button>
              </div>
              {!allServices && (
                <div className="grid grid-cols-2 sm:grid-cols-3 gap-1.5">
                  {services.map((s) => {
                    const on = pickedServices.includes(s.id);
                    return (
                      <button key={s.id} type="button" onClick={() => setPickedServices(on ? pickedServices.filter((x) => x !== s.id) : [...pickedServices, s.id])}
                        className={`flex items-center justify-between rounded-md border px-2.5 py-1.5 text-left text-xs transition-colors ${on ? "border-primary bg-primary/10" : "hover:bg-secondary"}`}>
                        <span className="flex items-center gap-1.5 truncate">
                          <span className={`size-2 rounded-full shrink-0 ${on ? "bg-primary" : "bg-muted-foreground/40"}`} />
                          {s.name}
                        </span>
                        <span className="text-[10px] text-muted-foreground shrink-0">{fmtCedi(s.price)}</span>
                      </button>
                    );
                  })}
                  {services.length === 0 && <div className="col-span-full text-xs text-muted-foreground">No daily services configured yet — create them in Daily Services first.</div>}
                </div>
              )}
              {preview.length > 0 && (
                <div className="space-y-1 pt-1">
                  <div className="text-[11px] font-medium text-muted-foreground flex items-center gap-1"><Sparkles className="size-3" /> Learner pays after this profile:</div>
                  <div className="flex flex-wrap gap-1.5">
                    {preview.map((p) => (
                      <span key={p.name} className={`rounded-md border px-2 py-0.5 text-[11px] ${p.after === 0 ? "border-emerald-500/50 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400" : ""}`}>
                        {p.name}: <s className="opacity-50">{fmtCedi(p.standard)}</s> → <b>{fmtCedi(p.after)}</b>
                      </span>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <Label className="text-sm">Per-class overrides</Label>
              <Button size="sm" variant="outline" className="h-7 gap-1 text-xs" onClick={() => setRules([...rules, { id: `tmp-${Date.now()}`, classroomName: classrooms[0]?.name ?? null, feeItemName: null, value: Number(value) || 0 }])}><Plus className="size-3.5" /> Add rule</Button>
            </div>
            <p className="text-xs text-muted-foreground">The most specific rule wins when invoices are generated — a class row overrides the default value.</p>
            {rules.length === 0 && <div className="rounded-lg border border-dashed p-3 text-center text-xs text-muted-foreground">No overrides — every class uses the default value.</div>}
            {rules.map((r, i) => (
              <div key={r.id} className="flex flex-wrap items-center gap-2 rounded-lg border p-2">
                <Select value={r.classroomName ?? ""} onValueChange={(v) => setRules(rules.map((x, j) => j === i ? { ...x, classroomName: v } : x))}>
                  <SelectTrigger className="w-44 h-8 text-xs flex-1"><SelectValue placeholder="All classes" /></SelectTrigger>
                  <SelectContent>{classrooms.map((c) => <SelectItem key={c.id} value={c.name}>{c.name}</SelectItem>)}</SelectContent>
                </Select>
                <Input type="number" className="w-24 h-8 text-xs" value={r.value} onChange={(e) => setRules(rules.map((x, j) => j === i ? { ...x, value: Number(e.target.value) } : x))} />
                <span className="text-xs text-muted-foreground">{method === "PERCENT" ? "%" : "GH₵"}</span>
                <Button size="sm" variant="ghost" className="h-8 text-destructive ml-auto" onClick={() => setRules(rules.filter((_, j) => j !== i))}><Trash2 className="size-3.5" /></Button>
              </div>
            ))}
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Saving…" : isNew ? "Create profile" : "Save changes"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ── Assign a profile to a learner ─────────────────────────────
function ApplyProfileDialog({ profile, data, students, onOpenChange, onDone }: {
  profile: ProfileRow | null; data: DiscountData | null; students: { id: string; name: string; classroom: string | null }[];
  onOpenChange: (o: boolean) => void; onDone: () => void;
}) {
  const { currentSchoolId } = useStore();
  const [studentId, setStudentId] = useState("");
  const [reason, setReason] = useState("");
  const [approveNow, setApproveNow] = useState(true);
  const [search, setSearch] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => { setStudentId(""); setReason(""); setSearch(""); setApproveNow(true); }, [profile]);

  // full learner list threaded in from the fees module (invoices carry every billed learner)
  const list = useMemo(() => students, [students]);

  // daily profiles carry their own scope — show what the learner will actually pay
  const coverage = useMemo(() => {
    if (!profile || profile.category !== "DAILY_SERVICE") return [];
    const services = data?.services ?? [];
    const covered = !profile.serviceIds || profile.serviceIds === "*"
      ? services
      : services.filter((s) => profile.serviceIds.split(",").includes(s.id));
    return covered.map((s) => ({
      name: s.name,
      standard: s.price,
      after: profile.method === "PERCENT"
        ? Math.max(0, Math.round(s.price * (1 - Math.min(100, profile.value) / 100)))
        : Math.max(0, s.price - profile.value),
    }));
  }, [profile, data]);

  async function submit() {
    if (!profile || !studentId) { toast({ title: "Choose a learner" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/discounts`, {
        action: "apply", profileId: profile.id, studentId,
        reason: reason || undefined, approveNow,
      });
      toast({
        title: approveNow ? "Profile assigned & approved" : "Profile assigned — pending approval",
        description: profile.category === "DAILY_SERVICE"
          ? (approveNow ? "The learner's daily rates are subsidised from today — the most generous rate wins where profiles overlap." : "The subsidised rates take effect once approved.")
          : (approveNow ? "Applied automatically the next time invoices are generated." : "Takes effect at billing once approved."),
      });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!profile} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto nice-scroll">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><UserPlus className="size-4 text-primary" /> Assign — {profile?.name}</DialogTitle>
          <DialogDescription>{profile?.method === "PERCENT" ? `${profile.value}% off` : fmtCedi(profile?.value ?? 0)} · {profile?.category === "INVOICE" ? "termly invoices" : "daily fees"}</DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          {coverage.length > 0 && (
            <div className="rounded-lg border bg-secondary/30 p-3 space-y-1.5">
              <div className="text-[11px] font-medium text-muted-foreground flex items-center gap-1"><Utensils className="size-3" /> Covered daily fees — learner pays:</div>
              <div className="flex flex-wrap gap-1.5">
                {coverage.map((c) => (
                  <span key={c.name} className={`rounded-md border px-2 py-0.5 text-[11px] ${c.after === 0 ? "border-emerald-500/50 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400" : ""}`}>
                    {c.name}: <s className="opacity-50">{fmtCedi(c.standard)}</s> → <b>{fmtCedi(c.after)}</b>
                  </span>
                ))}
              </div>
            </div>
          )}
          <Input placeholder="Search learner…" value={search} onChange={(e) => setSearch(e.target.value)} />
          <div className="border rounded-lg max-h-48 overflow-y-auto nice-scroll">
            {list.filter((s) => !search || s.name.toLowerCase().includes(search)).slice(0, 40).map((s) => (
              <button key={s.id} type="button" onClick={() => setStudentId(s.id)}
                className={`w-full flex items-center justify-between px-3 py-2 border-b last:border-0 text-sm text-left ${studentId === s.id ? "bg-primary text-primary-foreground" : "hover:bg-secondary/50"}`}>
                <span>{s.name}</span><span className="text-xs opacity-70">{s.classroom ?? ""}</span>
              </button>
            ))}
            {list.length === 0 && <div className="p-3 text-xs text-muted-foreground">Learners appear here once invoices exist for them.</div>}
          </div>
          <div className="space-y-1.5">
            <Label>Reason / evidence</Label>
            <Input value={reason} onChange={(e) => setReason(e.target.value)} placeholder="e.g. PTA executive — verified by chairman" />
          </div>
          <div className="flex items-center justify-between rounded-lg border p-3">
            <div>
              <div className="text-sm font-medium">Approve immediately</div>
              <div className="text-xs text-muted-foreground">Off → the assignment waits in the approvals queue.</div>
            </div>
            <Switch checked={approveNow} onCheckedChange={setApproveNow} />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy || !studentId}>{busy ? "Assigning…" : approveNow ? "Assign & approve" : "Assign for review"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

"use client";
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { PERMISSIONS, PERM_IMPLIES, ROLE_LEVELS, ROLE_LEVEL_LABELS, effectivePermissions, roleBasePerms } from "@/lib/rbac";
import { ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { fmtDateTime } from "@/lib/format";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { Checkbox } from "@/components/ui/checkbox";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { toast } from "@/hooks/use-toast";
import { initials } from "@/lib/format";
import { ShieldCheck, UserPlus, MoreHorizontal, KeyRound, ShieldX, BadgeCheck, Pencil, Search, Link2, RotateCcw, Users, CheckCircle2, PauseCircle, Layers, Trash2, Sparkles } from "lucide-react";

type RbacUser = {
  id: string; email: string; fullName: string; phone: string | null; role: string; title: string | null;
  status: string; lastLoginAt: string | null; permissions: string[];
  roleBase?: string[]; roleLabel?: string | null;
  overrides: { extra?: string[]; denied?: string[] };
  staffId: string | null; studentId: string | null; linkedStudentIds: string[];
  staffName: string | null; createdAt: string;
};
type RoleCard = {
  role: string; label: string; level: number | null; isCustom: boolean; system: boolean;
  configured: boolean; active: boolean; userCount: number; permCount: number;
  basePerms: string[]; landingModule: string; note: string | null; updatedByName: string | null;
};
type RbacData = { users: RbacUser[]; staff: StaffLite[]; roles: RoleCard[]; assignableRoles: string[]; protectedRoles: string[] };
type StaffLite = { id: string; fullName: string; position: string | null; category: string; role: string };
type StudentLite = { id: string; name: string; nyansapoIndex: string; classroom: string };

const ASSIGNABLE = ["SUPER_ADMIN", "SCHOOL_ADMIN", "ACCOUNTANT", "CASHIER", "BUS_CONDUCTOR", "TEACHER", "NON_TEACHING", "LIBRARIAN", "PARENT", "STUDENT"];
const ROLE_SHORT: Record<string, string> = {
  SUPER_ADMIN: "Super Admin", SCHOOL_ADMIN: "Administrator", ACCOUNTANT: "Accountant", CASHIER: "Cashier",
  BUS_CONDUCTOR: "Bus Conductor", TEACHER: "Teacher", NON_TEACHING: "Non-Teaching", LIBRARIAN: "Librarian",
  PARENT: "Parent", STUDENT: "Learner",
};
const STAFF_ROLES = ["SUPER_ADMIN", "SCHOOL_ADMIN", "ACCOUNTANT", "CASHIER", "BUS_CONDUCTOR", "TEACHER", "NON_TEACHING", "LIBRARIAN"];

const roleShort = (u: RbacUser, all: RoleCard[]) =>
  u.roleLabel ?? ROLE_SHORT[u.role] ?? all.find((r) => r.role === u.role)?.label ?? u.role.replace(/_/g, " ").toLowerCase().replace(/\b\w/g, (c) => c.toUpperCase());

export function RbacConsole() {
  const { currentSchoolId, session, navTarget } = useStore();
  const [tab, setTab] = useState("users");
  const [users, setUsers] = useState<RbacUser[]>([]);
  const [staff, setStaff] = useState<StaffLite[]>([]);
  const [roles, setRoles] = useState<RoleCard[]>([]);
  const [loading, setLoading] = useState(true);
  const [createOpen, setCreateOpen] = useState(false);
  const [editUser, setEditUser] = useState<RbacUser | null>(null);
  const [permUser, setPermUser] = useState<RbacUser | null>(null);
  const [roleEdit, setRoleEdit] = useState<{ mode: "new" | "edit"; card?: RoleCard } | null>(null);

  /* iEduPro nested nav: deep-link to the roles tab */
  const [appliedTarget, setAppliedTarget] = useState<any>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    if (navTarget?.tab && ["users", "roles"].includes(navTarget.tab)) setTab(navTarget.tab);
  }

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<RbacData>(`/api/school/${currentSchoolId}/ops/rbac`)
      .then((d) => { setUsers(d.users); setStaff(d.staff); setRoles(d.roles ?? []); })
      .catch((e) => toast({ title: "Could not load users", description: e.message, variant: "destructive" }))
      .finally(() => setLoading(false));
  }, [currentSchoolId]);
  useEffect(() => { load(); }, [load]);

  async function act(body: Record<string, unknown>, okMsg: string) {
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/rbac`, body);
      toast({ title: okMsg });
      load();
      return true;
    } catch (e: any) {
      toast({ title: "Action failed", description: e.message, variant: "destructive" });
      return false;
    }
  }

  async function setStatus(u: RbacUser, status: string) {
    await act({ action: "user.update", id: u.id, status }, status === "SUSPENDED" ? `${u.fullName} suspended` : `${u.fullName} reactivated`);
  }

  const stats = useMemo(() => ({
    total: users.length,
    active: users.filter((u) => u.status === "ACTIVE").length,
    suspended: users.filter((u) => u.status === "SUSPENDED").length,
    custom: users.filter((u) => (u.overrides.extra?.length ?? 0) + (u.overrides.denied?.length ?? 0) > 0).length,
    customRoles: roles.filter((r) => r.isCustom).length,
    tuned: roles.filter((r) => !r.isCustom && r.active).length,
  }), [users, roles]);

  /* every role assignable right now: the static ladder + this school's custom roles */
  const roleOptions = useMemo(() => {
    const customs = roles.filter((r) => r.isCustom);
    return [
      ...ASSIGNABLE.map((r) => ({
        role: r,
        label: `${ROLE_SHORT[r]}${ROLE_LEVELS[r] !== undefined ? ` — ${ROLE_LEVEL_LABELS[ROLE_LEVELS[r]]}` : ""}`,
        level: ROLE_LEVELS[r] ?? null,
        custom: false as const,
      })),
      ...customs.map((c) => ({ role: c.role, label: `${c.label}${c.level !== null ? ` — Level ${c.level}` : " — custom"}`, level: c.level, custom: true as const })),
    ];
  }, [roles]);

  if (loading) return <LoadingBlock label="Loading accounts…" />;

  return (
    <div>
      <ModuleHeader
        title="Users & Access"
        description="The comprehensive permission centre — assign roles (levels 1–5), tune what each role may do at this school, mint custom roles, and override per user. Cashiers can be barred from billing invoices while accountants keep it — everything is permission-based. Changes take effect on the user's next sign-in."
        actions={<Button size="sm" className="gap-1.5" onClick={() => setCreateOpen(true)}><UserPlus className="size-4" /> Add user</Button>}
      />

      <Tabs value={tab} onValueChange={setTab} className="mt-0">
        <TabsList className="flex-wrap">
          <TabsTrigger value="users" className="gap-1.5"><Users className="size-4" /> Users <span className="text-[10px] text-muted-foreground">{users.length}</span></TabsTrigger>
          <TabsTrigger value="roles" className="gap-1.5"><ShieldCheck className="size-4" /> Roles & permissions <span className="text-[10px] text-muted-foreground">{roles.length}</span></TabsTrigger>
        </TabsList>

        <TabsContent value="users" className="mt-4">
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        {[
          { icon: <Users className="size-4" />, label: "Accounts", value: stats.total, sub: "this school" },
          { icon: <CheckCircle2 className="size-4" />, label: "Active", value: stats.active, sub: "can sign in" },
          { icon: <PauseCircle className="size-4" />, label: "Suspended", value: stats.suspended, sub: "blocked at the gate" },
          { icon: <Layers className="size-4" />, label: "Roles in play", value: stats.customRoles + stats.tuned, sub: `${stats.customRoles} custom · ${stats.tuned} tuned` },
        ].map((s) => (
          <div key={s.label} className="rounded-xl border bg-card p-4">
            <div className="flex items-center justify-between"><span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{s.label}</span>{s.icon}</div>
            <div className="mt-1.5 text-2xl font-bold tabular-nums">{s.value}</div>
            <div className="text-[11px] text-muted-foreground">{s.sub}</div>
          </div>
        ))}
      </div>

      <div className="rounded-xl border bg-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Account</TableHead>
              <TableHead>Role & level</TableHead>
              <TableHead>Portal link</TableHead>
              <TableHead className="hidden md:table-cell">Last sign-in</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="w-10" />
            </TableRow>
          </TableHeader>
          <TableBody>
            {users.map((u) => {
              const lvl = ROLE_LEVELS[u.role];
              const custom = (u.overrides.extra?.length ?? 0) + (u.overrides.denied?.length ?? 0) > 0;
              return (
                <TableRow key={u.id}>
                  <TableCell>
                    <div className="flex items-center gap-2.5">
                      <Avatar className="size-8"><AvatarFallback className="text-[10px] font-semibold bg-primary/10 text-primary">{initials(u.fullName)}</AvatarFallback></Avatar>
                      <div className="min-w-0">
                        <div className="font-medium truncate flex items-center gap-1.5">
                          {u.fullName}
                          {u.id === session?.id && <Badge variant="outline" className="text-[9px] px-1">you</Badge>}
                        </div>
                        <div className="text-xs text-muted-foreground truncate">{u.email}</div>
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Select value={u.role} onValueChange={(role) => u.id !== session?.id && act({ action: "user.update", id: u.id, role }, `${u.fullName} is now ${roleOptions.find((r) => r.role === role)?.label ?? role}`)}>
                      <SelectTrigger className="h-8 w-[180px] text-xs gap-1">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {roleOptions.map((r) => (
                          <SelectItem key={r.role} value={r.role} disabled={u.id === session?.id}>
                            {r.label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    <div className="mt-1 flex gap-1">
                      {custom && <Badge variant="secondary" className="text-[9px] px-1.5 gap-1"><ShieldCheck className="size-2.5" /> custom perms</Badge>}
                      {u.roleLabel && <Badge variant="outline" className="text-[9px] px-1.5 gap-1 text-violet-700 dark:text-violet-300 border-violet-400/50"><Sparkles className="size-2.5" /> custom role</Badge>}
                      {(lvl !== undefined || u.roleLabel) && <Badge variant="outline" className="text-[9px] px-1.5">L{lvl ?? "—"}</Badge>}
                    </div>
                  </TableCell>
                  <TableCell className="text-xs text-muted-foreground">
                    {u.staffName ? <span className="flex items-center gap-1"><Link2 className="size-3" /> {u.staffName}</span> :
                     u.studentId ? <Badge variant="outline" className="text-[9px]">learner account</Badge> :
                     u.linkedStudentIds.length ? <Badge variant="outline" className="text-[9px]">{u.linkedStudentIds.length} children</Badge> :
                     <span className="text-muted-foreground/50">—</span>}
                  </TableCell>
                  <TableCell className="hidden md:table-cell text-xs text-muted-foreground">{u.lastLoginAt ? fmtDateTime(u.lastLoginAt) : "never"}</TableCell>
                  <TableCell>
                    <Badge className={u.status === "ACTIVE" ? "bg-emerald-600" : "bg-zinc-500"}>{u.status === "ACTIVE" ? "Active" : "Suspended"}</Badge>
                  </TableCell>
                  <TableCell>
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild><Button variant="ghost" size="icon" className="size-8"><MoreHorizontal className="size-4" /></Button></DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem onClick={() => setEditUser(u)}><Pencil className="size-4" /> Edit details</DropdownMenuItem>
                        <DropdownMenuItem onClick={() => setPermUser(u)}><ShieldCheck className="size-4" /> Permissions{custom ? " (custom)" : ""}</DropdownMenuItem>
                        <DropdownMenuItem onClick={() => { const pw = prompt(`New password for ${u.fullName}:`); if (pw) act({ action: "user.password", id: u.id, password: pw }, "Password reset"); }}>
                          <KeyRound className="size-4" /> Reset password
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        {u.id !== session?.id && (
                          u.status === "ACTIVE" ? (
                            <DropdownMenuItem className="text-destructive" onClick={() => setStatus(u, "SUSPENDED")}><ShieldX className="size-4" /> Suspend account</DropdownMenuItem>
                          ) : (
                            <DropdownMenuItem onClick={() => setStatus(u, "ACTIVE")}><BadgeCheck className="size-4" /> Reactivate</DropdownMenuItem>
                          )
                        )}
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              );
            })}
          </TableBody>
        </Table>
        {!users.length && <EmptyState icon={<Users className="size-8" />} title="No accounts yet" hint="Add your first user to get started." />}
      </div>
        </TabsContent>

        <TabsContent value="roles" className="mt-4">
          <RolesTab roles={roles} users={users} meRole={session?.role ?? ""} onEdit={(card) => setRoleEdit({ mode: "edit", card })} onNew={() => setRoleEdit({ mode: "new" })} onSaved={() => load()} schoolId={currentSchoolId!} />
        </TabsContent>
      </Tabs>

      <UserDialog
        open={createOpen} onOpenChange={setCreateOpen} mode="create" staff={staff} schoolId={currentSchoolId!} roleOptions={roleOptions}
        onSaved={() => { setCreateOpen(false); load(); }}
      />
      <UserDialog
        open={!!editUser} onOpenChange={(o) => !o && setEditUser(null)} mode="edit" user={editUser} staff={staff} schoolId={currentSchoolId!} roleOptions={roleOptions}
        onSaved={() => { setEditUser(null); load(); }}
      />
      <PermissionsDialog user={permUser} onOpenChange={(o) => !o && setPermUser(null)} schoolId={currentSchoolId!} onSaved={() => { load(); setPermUser(null); }} />
      <RoleDialog edit={roleEdit} onClose={() => setRoleEdit(null)} onSaved={() => { setRoleEdit(null); load(); }} schoolId={currentSchoolId!} roles={roles} meRole={session?.role ?? ""} />
    </div>
  );
}

/* ── create / edit user dialog with staff & learner linking ── */
function UserDialog({ open, onOpenChange, mode, user, staff, schoolId, roleOptions, onSaved }: {
  open: boolean; onOpenChange: (o: boolean) => void; mode: "create" | "edit";
  user?: RbacUser | null; staff: StaffLite[]; schoolId: string; roleOptions: { role: string; label: string; level: number | null; custom: boolean }[]; onSaved: () => void;
}) {
  const [fullName, setFullName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [phone, setPhone] = useState("");
  const [title, setTitle] = useState("");
  const [role, setRole] = useState("CASHIER");
  const [staffId, setStaffId] = useState<string>("none");
  const [students, setStudents] = useState<StudentLite[]>([]);
  const [picked, setPicked] = useState<StudentLite[]>([]);
  const [search, setSearch] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (open) {
      setFullName(user?.fullName ?? "");
      setEmail(user?.email ?? "");
      setPassword("");
      setPhone(user?.phone ?? "");
      setTitle(user?.title ?? "");
      setRole(user?.role ?? "CASHIER");
      setStaffId(user?.staffId ?? "none");
      setPicked([]);
      setSearch("");
    }
  }, [open, user]);

  // learner search (for parent / learner accounts)
  useEffect(() => {
    if (!open || (role !== "PARENT" && role !== "STUDENT")) return;
    if (!search.trim()) { setStudents([]); return; }
    const t = setTimeout(() => {
      apiGet<{ students: StudentLite[] }>(`/api/school/${schoolId}/students?search=${encodeURIComponent(search)}`)
        .then((d) => setStudents(d.students.slice(0, 6)))
        .catch(() => setStudents([]));
    }, 250);
    return () => clearTimeout(t);
  }, [search, open, role, schoolId]);

  async function save() {
    setBusy(true);
    try {
      if (mode === "create") {
        await apiPost(`/api/school/${schoolId}/ops/rbac`, {
          action: "user.create", email, password: password || "nyansapo", fullName, phone, title, role,
          staffId: STAFF_ROLES.includes(role) && staffId !== "none" ? staffId : undefined,
          studentId: role === "STUDENT" ? picked[0]?.id : undefined,
          linkedStudentIds: role === "PARENT" ? picked.map((p) => p.id) : undefined,
        });
        toast({ title: `${fullName} added`, description: `${ROLE_SHORT[role]} account created.` });
      } else if (user) {
        await apiPost(`/api/school/${schoolId}/ops/rbac`, {
          action: "user.update", id: user.id, fullName, phone, title,
          staffId: STAFF_ROLES.includes(role) ? (staffId === "none" ? null : staffId) : null,
          studentId: role === "STUDENT" ? (picked[0]?.id ?? null) : null,
          linkedStudentIds: role === "PARENT" ? picked.map((p) => p.id) : [],
        });
        toast({ title: "Account updated" });
      }
      onSaved();
    } catch (e: any) {
      toast({ title: "Could not save", description: e.message, variant: "destructive" });
    } finally {
      setBusy(false);
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto nice-scroll">
        <DialogHeader>
          <DialogTitle>{mode === "create" ? "Add user account" : `Edit ${user?.fullName ?? ""}`}</DialogTitle>
          <DialogDescription>
            {mode === "create" ? "Create a staff, parent or learner account with a role from the admin ladder." : "Update details and portal links."}
          </DialogDescription>
        </DialogHeader>
        <div className="grid gap-3">
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5"><Label>Full name</Label><Input value={fullName} onChange={(e) => setFullName(e.target.value)} placeholder="Lawrencia Ahwireng" /></div>
            <div className="space-y-1.5"><Label>Phone</Label><Input value={phone} onChange={(e) => setPhone(e.target.value)} placeholder="+233 24 …" /></div>
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5"><Label>Email</Label><Input value={email} onChange={(e) => setEmail(e.target.value)} disabled={mode === "edit"} placeholder="cashier@nyansapo.gh" /></div>
            {mode === "create" && <div className="space-y-1.5"><Label>Password</Label><Input value={password} onChange={(e) => setPassword(e.target.value)} placeholder="default: nyansapo" /></div>}
            {mode === "edit" && <div className="space-y-1.5"><Label>Display title</Label><Input value={title} onChange={(e) => setTitle(e.target.value)} placeholder="Front-desk cashier" /></div>}
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Role</Label>
              <Select value={role} onValueChange={setRole} disabled={mode === "edit"}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {roleOptions.map((r) => <SelectItem key={r.role} value={r.role}>{r.label}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            {(STAFF_ROLES.includes(role) || roleOptions.some((r) => r.role === role && r.custom)) && (
              <div className="space-y-1.5">
                <Label>Link staff record</Label>
                <Select value={staffId} onValueChange={setStaffId}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">— none —</SelectItem>
                    {staff.map((s) => <SelectItem key={s.id} value={s.id}>{s.fullName}{s.position ? ` (${s.position})` : ""}</SelectItem>)}
                  </SelectContent>
                </Select>
              </div>
            )}
          </div>
          {(role === "PARENT" || role === "STUDENT") && (
            <div className="space-y-1.5 rounded-lg border p-3 bg-secondary/30">
              <Label className="flex items-center gap-1.5"><Search className="size-3.5" /> {role === "PARENT" ? "Link children (search by name or index)" : "Link learner record"}</Label>
              <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="e.g. Adjei or NY-2020-…" />
              {!!students.length && (
                <div className="space-y-1">
                  {students.map((s) => (
                    <button key={s.id} className="w-full flex items-center justify-between rounded-md border bg-card px-2.5 py-1.5 text-left hover:bg-secondary/60 text-sm"
                      onClick={() => {
                        if (role === "STUDENT") setPicked([s]);
                        else setPicked((p) => (p.some((x) => x.id === s.id) ? p : [...p, s]));
                        setSearch("");
                      }}>
                      <span>{s.name} <span className="text-xs text-muted-foreground">· {s.classroom}</span></span>
                      <Link2 className="size-3.5 text-muted-foreground" />
                    </button>
                  ))}
                </div>
              )}
              {!!picked.length && (
                <div className="flex flex-wrap gap-1.5 pt-1">
                  {picked.map((p) => (
                    <Badge key={p.id} variant="secondary" className="gap-1">
                      {p.name}
                      <button className="ml-0.5 hover:text-destructive" onClick={() => setPicked((x) => x.filter((y) => y.id !== p.id))}>×</button>
                    </Badge>
                  ))}
                </div>
              )}
            </div>
          )}
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={save} disabled={busy || !fullName || !email}>{mode === "create" ? "Create account" : "Save changes"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ── per-user permission matrix editor ── */
function PermissionsDialog({ user, onOpenChange, schoolId, onSaved }: {
  user: RbacUser | null; onOpenChange: (o: boolean) => void; schoolId: string; onSaved: () => void;
}) {
  const [effective, setEffective] = useState<Set<string>>(new Set());
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (user) setEffective(new Set(user.permissions));
  }, [user]);

  const roleDefaults = useMemo(() => {
    if (!user) return new Set<string>();
    // the school's actual base for this role — RoleConfig when active (may be a
    // deliberately EMPTY list), else the code defaults
    return new Set(Array.isArray(user.roleBase) ? user.roleBase : roleBasePerms(user.role));
  }, [user]);

  const groups = useMemo(() => {
    const g: Record<string, typeof PERMISSIONS> = {};
    PERMISSIONS.forEach((p) => { (g[p.group] ??= []).push(p); });
    return g;
  }, []);

  // permId → an action permission that implies it (for the "implied" badge)
  const impliedBy = useMemo(() => {
    const map: Record<string, string> = {};
    Object.entries(PERM_IMPLIES).forEach(([action, views]) => views.forEach((v) => { map[v] ??= action; }));
    return map;
  }, []);
  const manualExtras = useMemo(() => new Set(user?.overrides.extra ?? []), [user]);

  function toggle(permId: string) {
    setEffective((prev) => {
      const next = new Set(prev);
      if (next.has(permId)) {
        next.delete(permId);
      } else {
        next.add(permId);
        // granting an action auto-includes its view permission
        for (const impl of PERM_IMPLIES[permId] ?? []) next.add(impl);
      }
      return next;
    });
  }

  async function save() {
    if (!user) return;
    setBusy(true);
    const extra: string[] = [];
    const denied: string[] = [];
    PERMISSIONS.forEach((p) => {
      const isOn = effective.has(p.id);
      const byDefault = roleDefaults.has(p.id);
      if (isOn && !byDefault) extra.push(p.id);
      if (!isOn && byDefault) denied.push(p.id);
      // an OFF permission that an ON action would re-imply must be denied explicitly,
      // otherwise the server-side implication closure would silently re-grant it
      if (!isOn && !byDefault && !!impliedBy[p.id] && effective.has(impliedBy[p.id])) denied.push(p.id);
    });
    try {
      await apiPost(`/api/school/${schoolId}/ops/rbac`, { action: "user.perms", id: user.id, extra, denied });
      toast({
        title: "Permissions saved",
        description: extra.length || denied.length ? `${extra.length} extra grant(s), ${denied.length} denial(s).` : "Back to role defaults.",
      });
      onSaved();
    } catch (e: any) {
      toast({ title: "Could not save permissions", description: e.message, variant: "destructive" });
    } finally {
      setBusy(false);
    }
  }

  const overridesCount = (user?.overrides.extra?.length ?? 0) + (user?.overrides.denied?.length ?? 0);

  return (
    <Dialog open={!!user} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto nice-scroll">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><ShieldCheck className="size-4.5" /> Permissions — {user?.fullName}</DialogTitle>
          <DialogDescription>
            Role <b>{user ? user.roleLabel ?? ROLE_SHORT[user.role] ?? user.role : ""}</b> grants its defaults at this school; tick or untick to override for this person only. Actions auto-include their view permission (e.g. Enter marks ⇒ View gradebook) — untick the view to override.
            {overridesCount > 0 && <span className="ml-1 text-amber-600 font-medium">{overridesCount} override(s) active.</span>}
          </DialogDescription>
        </DialogHeader>
        <Tabs defaultValue={groups[Object.keys(groups)[0]] ? Object.keys(groups)[0] : "People"}>
          <TabsList className="h-auto w-full justify-start overflow-x-auto gap-0.5">
            {Object.keys(groups).map((g) => (
              <TabsTrigger key={g} value={g} className="text-xs shrink-0 whitespace-nowrap">
                {g}
                <span className="ml-1 text-[10px] text-muted-foreground">{groups[g].filter((p) => effective.has(p.id)).length}/{groups[g].length}</span>
              </TabsTrigger>
            ))}
          </TabsList>
          {Object.entries(groups).map(([g, perms]) => (
            <TabsContent key={g} value={g} className="mt-3">
              <div className="grid sm:grid-cols-2 gap-2">
                {perms.map((p) => {
                  const on = effective.has(p.id);
                  const isDefault = roleDefaults.has(p.id);
                  const isImplied = on && !isDefault && !manualExtras.has(p.id) && !!impliedBy[p.id] && effective.has(impliedBy[p.id]);
                  return (
                    <label key={p.id} className={`flex items-start gap-2.5 rounded-lg border p-2.5 cursor-pointer transition-colors ${on ? "border-primary/40 bg-primary/5" : "hover:bg-secondary/40"}`}>
                      <Checkbox checked={on} onCheckedChange={() => toggle(p.id)} className="mt-0.5" />
                      <div className="min-w-0">
                        <div className="text-sm font-medium flex items-center gap-1.5 flex-wrap">
                          {p.label}
                          {isDefault ? <Badge variant="outline" className="text-[9px] px-1">role default</Badge>
                            : isImplied ? <Badge variant="outline" className="text-[9px] px-1 text-sky-700 border-sky-300 bg-sky-50 dark:text-sky-300 dark:border-sky-800 dark:bg-sky-950">implied</Badge>
                            : <Badge variant="secondary" className="text-[9px] px-1">custom</Badge>}
                        </div>
                        <div className="text-[10px] text-muted-foreground font-mono">{p.id}</div>
                      </div>
                    </label>
                  );
                })}
              </div>
            </TabsContent>
          ))}
        </Tabs>
        <DialogFooter className="gap-2">
          <Button variant="outline" size="sm" className="gap-1.5" onClick={() => user && setEffective(new Set(roleDefaults))}>
            <RotateCcw className="size-3.5" /> Reset to role defaults
          </Button>
          <Button onClick={save} disabled={busy}>Save permissions</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ─────────────────────────────────────────────────────────────
   ROLES TAB — the comprehensive role/permission management grid.
   Every role in play at this school (ladder + custom), each with its
   effective permission set, user count and tuning controls. This is
   where "cashiers may not bill invoices — only accountants" happens.
   ───────────────────────────────────────────────────────────── */
function RolesTab({ roles, users, meRole, onEdit, onNew, onSaved, schoolId }: {
  roles: RoleCard[]; users: RbacUser[]; meRole: string;
  onEdit: (card: RoleCard) => void; onNew: () => void; onSaved: () => void; schoolId: string;
}) {
  const [confirmDelete, setConfirmDelete] = useState<RoleCard | null>(null);
  const [reassignTo, setReassignTo] = useState("CASHIER");
  const [busy, setBusy] = useState(false);

  async function save(body: Record<string, unknown>, okMsg: string) {
    setBusy(true);
    try {
      await apiPost(`/api/school/${schoolId}/ops/rbac`, body);
      toast({ title: okMsg });
      onSaved();
      return true;
    } catch (e: any) {
      toast({ title: "Could not save role", description: e.message, variant: "destructive" });
      return false;
    } finally { setBusy(false); }
  }

  const tunable = roles.filter((r) => !r.isCustom);
  const customs = roles.filter((r) => r.isCustom);

  const card = (r: RoleCard) => {
    const permPct = Math.round((r.permCount / PERMISSIONS.length) * 100);
    return (
      <div key={r.role} className={`rounded-xl border bg-card p-4 flex flex-col gap-3 ${r.active ? "border-primary/25" : ""}`}>
        <div className="flex items-start justify-between gap-2">
          <div className="min-w-0">
            <div className="font-semibold flex items-center gap-1.5 flex-wrap">
              {r.label}
              {r.isCustom && <Badge variant="outline" className="text-[9px] px-1.5 gap-0.5 text-violet-700 dark:text-violet-300 border-violet-400/50"><Sparkles className="size-2.5" /> custom</Badge>}
              {r.level !== null && <Badge variant="outline" className="text-[9px] px-1.5">L{r.level}</Badge>}
              {r.role === meRole && <Badge variant="secondary" className="text-[9px] px-1.5">your role</Badge>}
            </div>
            <div className="text-[11px] text-muted-foreground mt-0.5">
              {r.userCount} user{r.userCount === 1 ? "" : "s"} · lands on {r.landingModule}
              {r.updatedByName ? ` · tuned by ${r.updatedByName}` : ""}
            </div>
          </div>
          <div className="text-right shrink-0">
            <div className="text-lg font-bold tabular-nums leading-none">{r.permCount}<span className="text-xs font-normal text-muted-foreground">/{PERMISSIONS.length}</span></div>
            <div className="text-[10px] text-muted-foreground mt-0.5">permissions</div>
          </div>
        </div>
        <div className="h-1.5 rounded-full bg-secondary overflow-hidden">
          <div className={`h-full ${permPct > 66 ? "bg-emerald-500" : permPct > 33 ? "bg-amber-500" : "bg-destructive/70"}`} style={{ width: `${permPct}%` }} />
        </div>
        <div className="flex flex-wrap gap-1.5 mt-auto">
          <Button size="sm" variant={r.active ? "outline" : "default"} className="h-7 gap-1 text-xs" onClick={() => onEdit(r)}>
            {r.active ? <Pencil className="size-3" /> : <Layers className="size-3" />} {r.active ? "Tune permissions" : "Configure"}
          </Button>
          {r.active && !r.isCustom && (
            <Button size="sm" variant="ghost" className="h-7 gap-1 text-xs" disabled={busy}
              onClick={() => save({ action: "role.save", role: r.role, isCustom: false, label: r.label, level: r.level, active: false }, `${r.label} back to system defaults`)}>
              <RotateCcw className="size-3" /> Revert
            </Button>
          )}
          {r.isCustom && (
            <Button size="sm" variant="ghost" className="h-7 gap-1 text-xs text-destructive hover:text-destructive" disabled={busy}
              onClick={() => { setConfirmDelete(r); setReassignTo(r.userCount ? "CASHIER" : "CASHIER"); }}>
              <Trash2 className="size-3" /> Delete
            </Button>
          )}
        </div>
      </div>
    );
  };

  return (
    <div className="space-y-5">
      {/* explainer */}
      <div className="rounded-xl border border-primary/20 bg-primary/5 p-4 text-sm">
        <div className="font-semibold flex items-center gap-1.5"><ShieldCheck className="size-4 text-primary" /> Every module is permission-based</div>
        <p className="text-muted-foreground text-xs leading-relaxed mt-1">
          Tune what each role may do at this school — e.g. bar cashiers from creating invoices and receipts (billing stays with accountants),
          keep lunch opt-out approvals with admin, or mint a brand-new role for front-desk staff. Users still get per-person overrides on top.
          Untuned roles fall back to the iEduPro ladder defaults.
        </p>
      </div>

      <div>
        <h3 className="text-sm font-semibold mb-2">School ladder <span className="text-muted-foreground font-normal">— levels 1–5 + staff (iEduPro parity)</span></h3>
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">{tunable.map(card)}</div>
      </div>

      <div>
        <div className="flex items-center justify-between mb-2">
          <h3 className="text-sm font-semibold">Custom roles <span className="text-muted-foreground font-normal">— minted for this school</span></h3>
          <Button size="sm" className="gap-1.5" onClick={onNew}><Sparkles className="size-4" /> New custom role</Button>
        </div>
        {customs.length ? (
          <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">{customs.map(card)}</div>
        ) : (
          <EmptyState icon={<Sparkles className="size-8" />} title="No custom roles yet"
            hint="Mint one for positions the ladder doesn't cover — Bursar Assistant, Front Desk, Store Keeper…" />
        )}
      </div>

      {/* delete confirm */}
      <Dialog open={!!confirmDelete} onOpenChange={(o) => !o && setConfirmDelete(null)}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>Delete “{confirmDelete?.label}”?</DialogTitle>
            <DialogDescription>
              {confirmDelete?.userCount
                ? `${confirmDelete.userCount} user(s) hold this role. Pick the role they move to first — their per-person overrides carry over.`
                : "No users hold this role — it will be removed immediately."}
            </DialogDescription>
          </DialogHeader>
          {!!confirmDelete?.userCount && (
            <div className="space-y-1.5">
              <Label>Reassign users to</Label>
              <Select value={reassignTo} onValueChange={setReassignTo}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {ASSIGNABLE.filter((r) => r !== "PARENT" && r !== "STUDENT").map((r) => <SelectItem key={r} value={r}>{ROLE_SHORT[r]}</SelectItem>)}
                  {roles.filter((x) => x.isCustom && x.role !== confirmDelete?.role).map((x) => <SelectItem key={x.role} value={x.role}>{x.label} — custom</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
          )}
          <DialogFooter>
            <Button variant="outline" onClick={() => setConfirmDelete(null)}>Keep role</Button>
            <Button variant="destructive" disabled={busy}
              onClick={async () => {
                if (!confirmDelete) return;
                const ok = await save({ action: "role.delete", role: confirmDelete.role, reassignTo }, `${confirmDelete.label} deleted`);
                if (ok) setConfirmDelete(null);
              }}>
              Delete role
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

/* ── create/edit a role: identity + the permission matrix (config-aware) ── */
function RoleDialog({ edit, onClose, onSaved, schoolId, roles, meRole }: {
  edit: { mode: "new" | "edit"; card?: RoleCard } | null;
  onClose: () => void; onSaved: () => void; schoolId: string; roles: RoleCard[]; meRole: string;
}) {
  const isNew = edit?.mode === "new";
  const card = edit?.card;
  const [label, setLabel] = useState("");
  const [level, setLevel] = useState<string>("4");
  const [landing, setLanding] = useState("dashboard");
  const [effective, setEffective] = useState<Set<string>>(new Set());
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (!edit) return;
    if (isNew) {
      setLabel(""); setLevel("4"); setLanding("dashboard");
      setEffective(new Set(["students.view", "fees.view", "fees.collect", "daily.view", "daily.collect", "cashier.reports", "staffhub.self"]));
    } else if (card) {
      setLabel(card.label);
      setLevel(card.level === null ? "staff" : String(card.level));
      setLanding(card.landingModule || "dashboard");
      setEffective(new Set(card.basePerms));
    }
  }, [edit, isNew, card]);

  const groups = useMemo(() => {
    const g: Record<string, typeof PERMISSIONS> = {};
    PERMISSIONS.forEach((p) => { (g[p.group] ??= []).push(p); });
    return g;
  }, []);
  const impliedBy = useMemo(() => {
    const map: Record<string, string> = {};
    Object.entries(PERM_IMPLIES).forEach(([action, views]) => views.forEach((v) => { map[v] ??= action; }));
    return map;
  }, []);

  function toggle(permId: string) {
    setEffective((prev) => {
      const next = new Set(prev);
      if (next.has(permId)) next.delete(permId);
      else {
        next.add(permId);
        for (const impl of PERM_IMPLIES[permId] ?? []) next.add(impl);
      }
      return next;
    });
  }

  async function save() {
    if (!label.trim()) { toast({ title: "Give the role a name" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${schoolId}/ops/rbac`, {
        action: "role.save",
        isCustom: isNew ? true : !!card?.isCustom,
        role: isNew ? undefined : card?.role,
        label: label.trim(),
        level: level === "staff" ? null : Number(level),
        landingModule: landing,
        permissions: Array.from(effective),
      });
      toast({ title: isNew ? `${label} created` : `${label} updated`, description: `${effective.size} permission(s) — applies on each user's next sign-in.` });
      onSaved();
    } catch (e: any) {
      toast({ title: "Could not save role", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  const originalBase = useMemo(() => new Set(card?.basePerms ?? []), [card]);

  return (
    <Dialog open={!!edit} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="max-w-2xl max-h-[92vh] overflow-y-auto nice-scroll">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><Layers className="size-4.5" /> {isNew ? "New custom role" : `Tune ${card?.label ?? ""}`}</DialogTitle>
          <DialogDescription>
            {isNew
              ? "Mint a role for this school — name it, place it on the ladder, pick where its users land, then set its permissions."
              : card?.isCustom
                ? "Editing this custom role's identity and permission set. Users pick it up on their next sign-in."
                : "This overrides the ladder default for this role at this school. Revert any time — users keep their per-person overrides."}
          </DialogDescription>
        </DialogHeader>

        <div className="grid gap-3 sm:grid-cols-3">
          <div className="space-y-1.5 sm:col-span-1">
            <Label>Role name</Label>
            <Input value={label} onChange={(e) => setLabel(e.target.value)} placeholder="e.g. Bursar Assistant" disabled={!isNew && !card?.isCustom} />
          </div>
          <div className="space-y-1.5">
            <Label>Level</Label>
            <Select value={level} onValueChange={setLevel} disabled={!isNew && !card?.isCustom}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="2">Level 2 · Admin</SelectItem>
                <SelectItem value="3">Level 3 · Accountant</SelectItem>
                <SelectItem value="4">Level 4 · Cashier</SelectItem>
                <SelectItem value="5">Level 5 · Operations</SelectItem>
                <SelectItem value="staff">Staff — off the ladder</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Lands on</Label>
            <Select value={landing} onValueChange={setLanding} disabled={!isNew && !card?.isCustom}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="dashboard">Dashboard</SelectItem>
                <SelectItem value="cashier">Cashier Console</SelectItem>
                <SelectItem value="conductor">Conductor Portal</SelectItem>
                <SelectItem value="staffhub">Staff self-service</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <div className="rounded-lg border bg-secondary/30 px-3 py-2 text-xs text-muted-foreground flex flex-wrap gap-x-4 gap-y-1">
          <span><b className="text-foreground">{effective.size}</b> of {PERMISSIONS.length} permissions on</span>
          <span>Landing module: <b className="text-foreground">{landing}</b></span>
          {card && card.role === meRole && <span className="text-amber-600 font-medium">This is your own role — keep “Manage users, roles & permissions” ticked.</span>}
        </div>

        <Tabs defaultValue={Object.keys(groups)[0]}>
          <TabsList className="h-auto w-full justify-start overflow-x-auto gap-0.5">
            {Object.keys(groups).map((g) => (
              <TabsTrigger key={g} value={g} className="text-xs shrink-0 whitespace-nowrap">
                {g}
                <span className="ml-1 text-[10px] text-muted-foreground">{groups[g].filter((p) => effective.has(p.id)).length}/{groups[g].length}</span>
              </TabsTrigger>
            ))}
          </TabsList>
          {Object.entries(groups).map(([g, perms]) => (
            <TabsContent key={g} value={g} className="mt-3">
              <div className="grid sm:grid-cols-2 gap-2">
                {perms.map((p) => {
                  const on = effective.has(p.id);
                  const wasBase = originalBase.has(p.id);
                  return (
                    <label key={p.id} className={`flex items-start gap-2.5 rounded-lg border p-2.5 cursor-pointer transition-colors ${on ? "border-primary/40 bg-primary/5" : "hover:bg-secondary/40"}`}>
                      <Checkbox checked={on} onCheckedChange={() => toggle(p.id)} className="mt-0.5" />
                      <div className="min-w-0">
                        <div className="text-sm font-medium flex items-center gap-1.5 flex-wrap">
                          {p.label}
                          {wasBase ? <Badge variant="outline" className="text-[9px] px-1">current</Badge> : on && <Badge variant="secondary" className="text-[9px] px-1">added</Badge>}
                          {!on && wasBase && <Badge variant="outline" className="text-[9px] px-1 text-destructive border-destructive/40">removed</Badge>}
                        </div>
                        <div className="text-[10px] text-muted-foreground font-mono">{p.id}</div>
                      </div>
                    </label>
                  );
                })}
              </div>
            </TabsContent>
          ))}
        </Tabs>

        <DialogFooter className="gap-2">
          {!isNew && card && !card.isCustom && (
            <Button variant="outline" size="sm" className="gap-1.5" onClick={() => setEffective(new Set(roleBasePerms(card.role)))}>
              <RotateCcw className="size-3.5" /> System defaults
            </Button>
          )}
          {!isNew && card?.isCustom && (
            <Button variant="outline" size="sm" className="gap-1.5" onClick={() => setEffective(new Set(originalBase))}>
              <RotateCcw className="size-3.5" /> Undo changes
            </Button>
          )}
          <Button onClick={save} disabled={busy || !label.trim()}>
            {isNew ? "Create role" : "Save role"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

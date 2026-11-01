"use client";
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { PERMISSIONS, PERM_IMPLIES, ROLE_DEFAULTS, ROLE_LEVELS, ROLE_LEVEL_LABELS, effectivePermissions } from "@/lib/rbac";
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
import { ShieldCheck, UserPlus, MoreHorizontal, KeyRound, ShieldX, BadgeCheck, Pencil, Search, Link2, RotateCcw, Users, CheckCircle2, PauseCircle } from "lucide-react";

type RbacUser = {
  id: string; email: string; fullName: string; phone: string | null; role: string; title: string | null;
  status: string; lastLoginAt: string | null; permissions: string[];
  overrides: { extra?: string[]; denied?: string[] };
  staffId: string | null; studentId: string | null; linkedStudentIds: string[];
  staffName: string | null; createdAt: string;
};
type StaffLite = { id: string; fullName: string; position: string | null; category: string; role: string };
type StudentLite = { id: string; name: string; nyansapoIndex: string; classroom: string };

const ASSIGNABLE = ["SUPER_ADMIN", "SCHOOL_ADMIN", "ACCOUNTANT", "CASHIER", "BUS_CONDUCTOR", "TEACHER", "NON_TEACHING", "LIBRARIAN", "PARENT", "STUDENT"];
const ROLE_SHORT: Record<string, string> = {
  SUPER_ADMIN: "Super Admin", SCHOOL_ADMIN: "Administrator", ACCOUNTANT: "Accountant", CASHIER: "Cashier",
  BUS_CONDUCTOR: "Bus Conductor", TEACHER: "Teacher", NON_TEACHING: "Non-Teaching", LIBRARIAN: "Librarian",
  PARENT: "Parent", STUDENT: "Learner",
};
const STAFF_ROLES = ["SUPER_ADMIN", "SCHOOL_ADMIN", "ACCOUNTANT", "CASHIER", "BUS_CONDUCTOR", "TEACHER", "NON_TEACHING", "LIBRARIAN"];

export function RbacConsole() {
  const { currentSchoolId, session } = useStore();
  const [users, setUsers] = useState<RbacUser[]>([]);
  const [staff, setStaff] = useState<StaffLite[]>([]);
  const [loading, setLoading] = useState(true);
  const [createOpen, setCreateOpen] = useState(false);
  const [editUser, setEditUser] = useState<RbacUser | null>(null);
  const [permUser, setPermUser] = useState<RbacUser | null>(null);

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<{ users: RbacUser[]; staff: StaffLite[] }>(`/api/school/${currentSchoolId}/ops/rbac`)
      .then((d) => { setUsers(d.users); setStaff(d.staff); })
      .catch((e) => toast({ title: "Could not load users", description: e.message, variant: "destructive" }))
      .finally(() => setLoading(false));
  }, [currentSchoolId]);
  useEffect(() => { load(); }, [load]); // eslint-disable-line react-hooks/set-state-in-effect

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
  }), [users]);

  if (loading) return <LoadingBlock label="Loading accounts…" />;

  return (
    <div>
      <ModuleHeader
        title="Users & Access"
        description="iEduPro-style admin ladder — create accounts, assign roles (levels 1–5) and fine-tune permissions per user. Changes take effect on the user's next sign-in."
        actions={<Button size="sm" className="gap-1.5" onClick={() => setCreateOpen(true)}><UserPlus className="size-4" /> Add user</Button>}
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        {[
          { icon: <Users className="size-4" />, label: "Accounts", value: stats.total, sub: "this school" },
          { icon: <CheckCircle2 className="size-4" />, label: "Active", value: stats.active, sub: "can sign in" },
          { icon: <PauseCircle className="size-4" />, label: "Suspended", value: stats.suspended, sub: "blocked at the gate" },
          { icon: <BadgeCheck className="size-4" />, label: "Custom perms", value: stats.custom, sub: "overrides on defaults" },
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
                    <Select value={u.role} onValueChange={(role) => u.id !== session?.id && act({ action: "user.update", id: u.id, role }, `${u.fullName} is now ${ROLE_SHORT[role] ?? role}`)}>
                      <SelectTrigger className="h-8 w-[180px] text-xs gap-1">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {ASSIGNABLE.map((r) => (
                          <SelectItem key={r} value={r} disabled={u.id === session?.id}>
                            {ROLE_SHORT[r]}{ROLE_LEVELS[r] !== undefined ? ` — ${ROLE_LEVEL_LABELS[ROLE_LEVELS[r]]}` : ""}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    <div className="mt-1 flex gap-1">
                      {custom && <Badge variant="secondary" className="text-[9px] px-1.5 gap-1"><ShieldCheck className="size-2.5" /> custom perms</Badge>}
                      {lvl !== undefined && <Badge variant="outline" className="text-[9px] px-1.5">L{lvl}</Badge>}
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

      <UserDialog
        open={createOpen} onOpenChange={setCreateOpen} mode="create" staff={staff} schoolId={currentSchoolId!}
        onSaved={() => { setCreateOpen(false); load(); }}
      />
      <UserDialog
        open={!!editUser} onOpenChange={(o) => !o && setEditUser(null)} mode="edit" user={editUser} staff={staff} schoolId={currentSchoolId!}
        onSaved={() => { setEditUser(null); load(); }}
      />
      <PermissionsDialog user={permUser} onOpenChange={(o) => !o && setPermUser(null)} schoolId={currentSchoolId!} onSaved={() => { load(); setPermUser(null); }} />
    </div>
  );
}

/* ── create / edit user dialog with staff & learner linking ── */
function UserDialog({ open, onOpenChange, mode, user, staff, schoolId, onSaved }: {
  open: boolean; onOpenChange: (o: boolean) => void; mode: "create" | "edit";
  user?: RbacUser | null; staff: StaffLite[]; schoolId: string; onSaved: () => void;
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
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5"><Label>Full name</Label><Input value={fullName} onChange={(e) => setFullName(e.target.value)} placeholder="Lawrencia Ahwireng" /></div>
            <div className="space-y-1.5"><Label>Phone</Label><Input value={phone} onChange={(e) => setPhone(e.target.value)} placeholder="+233 24 …" /></div>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5"><Label>Email</Label><Input value={email} onChange={(e) => setEmail(e.target.value)} disabled={mode === "edit"} placeholder="cashier@nyansapo.gh" /></div>
            {mode === "create" && <div className="space-y-1.5"><Label>Password</Label><Input value={password} onChange={(e) => setPassword(e.target.value)} placeholder="default: nyansapo" /></div>}
            {mode === "edit" && <div className="space-y-1.5"><Label>Display title</Label><Input value={title} onChange={(e) => setTitle(e.target.value)} placeholder="Front-desk cashier" /></div>}
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label>Role</Label>
              <Select value={role} onValueChange={setRole} disabled={mode === "edit"}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {ASSIGNABLE.map((r) => <SelectItem key={r} value={r}>{ROLE_SHORT[r]}{ROLE_LEVELS[r] !== undefined ? ` — Level ${ROLE_LEVELS[r]}` : ""}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            {STAFF_ROLES.includes(role) && (
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
    const d = ROLE_DEFAULTS[user.role];
    return new Set(d === "*" ? PERMISSIONS.map((p) => p.id) : d);
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
            Role <b>{user ? ROLE_SHORT[user.role] ?? user.role : ""}</b> grants its defaults; tick or untick to override for this person only. Actions auto-include their view permission (e.g. Enter marks ⇒ View gradebook) — untick the view to override.
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
          <Button variant="outline" size="sm" className="gap-1.5" onClick={() => user && setEffective(new Set(effectivePermissions(user.role)))}>
            <RotateCcw className="size-3.5" /> Reset to role defaults
          </Button>
          <Button onClick={save} disabled={busy}>Save permissions</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

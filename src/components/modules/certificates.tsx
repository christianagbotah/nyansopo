"use client";
// ── Certificates & Documents (wave 30 — the headteacher's signature drawer) ──
// The signature drawer behind every Ghanaian school office: serial-numbered
// School Leaving Certificates, Testimonials, Admission Letters and
// Introductory Letters. Particulars are snapshotted at issue (name / index /
// class / session / DOB) so the printed sheet never drifts after rollover or
// graduation. Three tabs: the Issue Register (audit trail with revoke), the
// Issue Desk (create + print) and the Document Formats (A4 sample sheets).
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { ModuleHeader, LoadingBlock, EmptyState, StatCard, FormGrid, FormField } from "@/components/ui-helpers";
import { DateField } from "@/components/date-field";
import { canAny } from "@/lib/rbac";
import { useToast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import {
  FileBadge, Printer, Search, Stamp, ShieldBan, ShieldCheck, Trash2, ScrollText, Award, BookOpen, Send,
} from "lucide-react";

const OPS = "/ops/certificates";

const DOC_TYPES = ["LEAVING_CERT", "TESTIMONIAL", "ADMISSION_LETTER", "INTRO_LETTER"] as const;
type DocType = (typeof DOC_TYPES)[number];

const TYPE_META: Record<DocType, { label: string; prefix: string; blurb: string }> = {
  LEAVING_CERT: { label: "Leaving Certificate", prefix: "SLC", blurb: "Certifies the learner attended and states the reason for leaving — required by the receiving school." },
  TESTIMONIAL: { label: "Testimonial", prefix: "TSM", blurb: "A character & academic reference letter written on the school's letterhead." },
  ADMISSION_LETTER: { label: "Admission Letter", prefix: "ADL", blurb: "Formal offer of a place, with reporting requirements for the parent." },
  INTRO_LETTER: { label: "Introductory Letter", prefix: "INT", blurb: "Short 'to whom it may concern' letter for transfers & official errands." },
};

const TYPE_STYLES: Record<string, string> = {
  LEAVING_CERT: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300",
  TESTIMONIAL: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
  ADMISSION_LETTER: "bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300",
  INTRO_LETTER: "bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-300",
};

type DocRow = {
  id: string; type: string; serialNo: string; issueDate: string | null;
  studentId: string; studentName: string; studentIndex: string;
  className: string; sessionYear: string; dateOfBirth: string | null;
  reason: string | null; remarks: string | null; status: string;
  issuedByName: string | null; revokedByName: string | null; revokedAt: string | null;
};
type StudentRow = {
  id: string; name: string; index: string; gender: string; dateOfBirth: string | null;
  guardianName: string; className: string | null; classroomId: string | null; rollNo: number | null;
};
type SchoolLite = {
  name: string; code: string; motto: string | null; region: string; district: string;
  town: string; logoColor: string; establishedYear: number | null; gnacopsStatus: string; nasiaRegNo: string | null;
};
type Data = {
  school: SchoolLite | null;
  docs: DocRow[];
  stats: { total: number; issued: number; revoked: number; issuedThisYear: number; byType: { type: string; issued: number; revoked: number; total: number }[] };
  students: StudentRow[];
  classrooms: { id: string; name: string; gradeLevel: string }[];
  years: { id: string; name: string; isCurrent: boolean }[];
};

export function Certificates() {
  const { currentSchoolId, session, navTarget } = useStore();
  const { toast } = useToast();
  const schoolId = currentSchoolId ?? "";
  const canManage = canAny(session?.permissions, ["certificates.manage"]);

  const [tab, setTab] = useState("register");
  const [data, setData] = useState<Data | null>(null);
  const [loading, setLoading] = useState(true);
  const [tick, setTick] = useState(0);

  // register filters
  const [fType, setFType] = useState("all");
  const [fStatus, setFStatus] = useState("all");
  const [q, setQ] = useState("");

  // issue desk form
  const [form, setForm] = useState({
    studentId: "", type: "LEAVING_CERT", issueDate: new Date().toISOString().slice(0, 10),
    className: "", sessionYear: "", reason: "", remarks: "",
  });
  const [busy, setBusy] = useState(false);

  // print + confirm dialogs
  const [printDoc, setPrintDoc] = useState<DocRow | null>(null);
  const [confirmDoc, setConfirmDoc] = useState<{ doc: DocRow; op: "REVOKE" | "REINSTATE" | "DELETE" } | null>(null);

  const reload = useCallback(() => setTick((t) => t + 1), []);

  // initial load + deep-link
  useEffect(() => {
    if (!schoolId) return;
    if (navTarget?.tab && ["register", "issue", "formats"].includes(navTarget.tab)) {
      setTab(navTarget.tab);
    }
  }, [navTarget, schoolId]);

  useEffect(() => {
    if (!schoolId) return;
    let dead = false;
    setLoading(true);
    apiGet<Data>(`/api/school/${schoolId}${OPS}`)
      .then((d) => { if (!dead) setData(d); })
      .catch((e) => { if (!dead) toast({ title: "Could not load documents", description: String(e.message ?? e), variant: "destructive" }); })
      .finally(() => { if (!dead) setLoading(false); });
    return () => { dead = true; };
  }, [schoolId, tick, toast]);

  const docs = useMemo(() => {
    const rows = data?.docs ?? [];
    return rows.filter((r) => {
      if (fType !== "all" && r.type !== fType) return false;
      if (fStatus !== "all" && r.status !== fStatus) return false;
      return true;
    });
  }, [data, fType, fStatus]);

  const selectedStudent = useMemo(
    () => data?.students.find((s) => s.id === form.studentId) ?? null,
    [data, form.studentId],
  );

  // when the student changes, pre-fill class & session with today's truth —
  // the snapshot stays editable for graduates / transfers (class last attended)
  const pickStudent = (id: string) => {
    const s = data?.students.find((x) => x.id === id);
    setForm((f) => ({
      ...f,
      studentId: id,
      className: s?.className ?? f.className,
      sessionYear: data?.years.find((y) => y.isCurrent)?.name ?? f.sessionYear,
    }));
  };

  const issue = async () => {
    if (!schoolId) return;
    if (!form.studentId) { toast({ title: "Pick a learner first", variant: "destructive" }); return; }
    if (!form.className) { toast({ title: "Class is required", description: "Choose the class the document should state.", variant: "destructive" }); return; }
    if (!form.sessionYear) { toast({ title: "Academic year is required", variant: "destructive" }); return; }
    if ((form.type === "LEAVING_CERT" || form.type === "INTRO_LETTER") && !form.reason.trim()) {
      toast({ title: "Reason is required", description: TYPE_META[form.type as DocType].label === "Leaving Certificate" ? "State the reason for leaving." : "State the purpose of the letter.", variant: "destructive" });
      return;
    }
    setBusy(true);
    try {
      const res = await apiPost<{ ok: boolean; doc: DocRow }>(`/api/school/${schoolId}${OPS}`, { action: "ISSUE", ...form });
      toast({ title: "Document issued", description: `${res.doc.serialNo} · ${res.doc.studentName} — the print sheet is open.` });
      setForm((f) => ({ ...f, studentId: "", reason: "", remarks: "", className: "", issueDate: new Date().toISOString().slice(0, 10) }));
      setPrintDoc(res.doc);
      reload();
    } catch (e: any) {
      toast({ title: "Could not issue document", description: String(e.message ?? e), variant: "destructive" });
    } finally {
      setBusy(false);
    }
  };

  const doOp = async () => {
    if (!confirmDoc || !schoolId) return;
    const { doc, op } = confirmDoc;
    setBusy(true);
    try {
      if (op === "DELETE") {
        await apiPost(`/api/school/${schoolId}${OPS}`, { action: "DELETE", docId: doc.id });
        toast({ title: "Document deleted", description: `${doc.serialNo} removed from the register.` });
      } else {
        await apiPost(`/api/school/${schoolId}${OPS}`, { action: op, docId: doc.id });
        toast({ title: op === "REVOKE" ? "Document revoked" : "Document reinstated", description: `${doc.serialNo} is now ${op === "REVOKE" ? "REVOKED" : "ISSUED"}.` });
      }
      setConfirmDoc(null);
      reload();
    } catch (e: any) {
      toast({ title: "Action failed", description: String(e.message ?? e), variant: "destructive" });
    } finally {
      setBusy(false);
    }
  };

  if (loading && !data) return <LoadingBlock label="Loading certificates & documents…" />;

  const s = data?.stats;

  return (
    <div>
      <ModuleHeader
        title="Certificates & Documents"
        description="Serial-numbered official documents — leaving certificates, testimonials, admission & introductory letters. Particulars are snapshotted at issue, so printed sheets stay accurate after rollover or graduation."
      />

      <Tabs value={tab} onValueChange={setTab} className="space-y-4">
        <TabsList className="flex-wrap h-auto">
          <TabsTrigger value="register" className="gap-1.5"><ScrollText className="size-4" /> Issue Register</TabsTrigger>
          <TabsTrigger value="issue" className="gap-1.5"><Stamp className="size-4" /> Issue a Document</TabsTrigger>
          <TabsTrigger value="formats" className="gap-1.5"><FileBadge className="size-4" /> Formats</TabsTrigger>
        </TabsList>

        {/* ── Issue Register ── */}
        <TabsContent value="register" className="space-y-4">
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard icon={<FileBadge className="size-4" />} label="Documents on register" value={s?.total ?? 0} sub="all time" />
            <StatCard icon={<Stamp className="size-4" />} label="Issued this year" value={s?.issuedThisYear ?? 0} sub={`${new Date().getUTCFullYear()} serials`} tone="primary" />
            <StatCard icon={<ShieldCheck className="size-4" />} label="Active" value={s?.issued ?? 0} sub="valid sheets" />
            <StatCard icon={<ShieldBan className="size-4" />} label="Revoked" value={s?.revoked ?? 0} sub="voided by the school" tone={s?.revoked ? "danger" : "default"} />
          </div>

          <Card>
            <CardHeader className="pb-3">
              <div className="flex flex-wrap items-center gap-2">
                <Select value={fType} onValueChange={setFType}>
                  <SelectTrigger className="w-[190px]"><SelectValue placeholder="All types" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All types</SelectItem>
                    {DOC_TYPES.map((t) => <SelectItem key={t} value={t}>{TYPE_META[t].label} ({TYPE_META[t].prefix})</SelectItem>)}
                  </SelectContent>
                </Select>
                <Select value={fStatus} onValueChange={setFStatus}>
                  <SelectTrigger className="w-[150px]"><SelectValue placeholder="All statuses" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All statuses</SelectItem>
                    <SelectItem value="ISSUED">Issued</SelectItem>
                    <SelectItem value="REVOKED">Revoked</SelectItem>
                  </SelectContent>
                </Select>
                <div className="relative flex-1 min-w-[200px]">
                  <Search className="size-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                  <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search serial, learner, class, remarks…" className="pl-9" aria-label="Search documents" />
                </div>
                {canManage && (
                  <Button size="sm" className="gap-1.5" onClick={() => setTab("issue")}><Stamp className="size-4" /> Issue new</Button>
                )}
              </div>
            </CardHeader>
            <CardContent>
              {docs.length === 0 ? (
                <EmptyState icon={<FileBadge className="size-5" />} title="No documents match" hint={canManage ? "Issue the first certificate from the Issue Desk." : "No documents have been issued for this filter yet."} />
              ) : (
                <div className="overflow-x-auto max-h-[560px] overflow-y-auto nice-scroll">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="text-left text-xs uppercase tracking-wide text-muted-foreground border-b">
                        <th className="py-2 pr-3 font-semibold">Serial</th>
                        <th className="py-2 pr-3 font-semibold">Type</th>
                        <th className="py-2 pr-3 font-semibold">Learner</th>
                        <th className="py-2 pr-3 font-semibold">Class · Session</th>
                        <th className="py-2 pr-3 font-semibold">Issued</th>
                        <th className="py-2 pr-3 font-semibold">Status</th>
                        <th className="py-2 pr-3 text-right font-semibold">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {docs.map((d) => (
                        <tr key={d.id} className={cn("border-b last:border-0 hover:bg-muted/40", d.status === "REVOKED" && "opacity-60")}>
                          <td className="py-2.5 pr-3 font-mono text-xs font-semibold">{d.serialNo}</td>
                          <td className="py-2.5 pr-3"><Badge className={cn("border-transparent", TYPE_STYLES[d.type])}>{TYPE_META[d.type as DocType]?.label ?? d.type}</Badge></td>
                          <td className="py-2.5 pr-3">
                            <div className="font-medium leading-tight">{d.studentName}</div>
                            <div className="text-[11px] text-muted-foreground font-mono">{d.studentIndex}</div>
                          </td>
                          <td className="py-2.5 pr-3 leading-tight">
                            <div>{d.className}</div>
                            <div className="text-[11px] text-muted-foreground">{d.sessionYear}</div>
                          </td>
                          <td className="py-2.5 pr-3 whitespace-nowrap">{d.issueDate}</td>
                          <td className="py-2.5 pr-3">
                            {d.status === "ISSUED"
                              ? <Badge className="border-transparent bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">Issued</Badge>
                              : <Badge className="border-transparent bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300">Revoked</Badge>}
                          </td>
                          <td className="py-2.5 pr-3">
                            <div className="flex items-center justify-end gap-1">
                              <Button size="sm" variant="outline" className="gap-1 h-8" onClick={() => setPrintDoc(d)}><Printer className="size-3.5" /> Print</Button>
                              {canManage && (d.status === "ISSUED"
                                ? <Button size="sm" variant="outline" className="h-8 gap-1 text-destructive hover:text-destructive" onClick={() => setConfirmDoc({ doc: d, op: "REVOKE" })}><ShieldBan className="size-3.5" /> Revoke</Button>
                                : <Button size="sm" variant="outline" className="h-8 gap-1" onClick={() => setConfirmDoc({ doc: d, op: "REINSTATE" })}><ShieldCheck className="size-3.5" /> Reinstate</Button>)}
                              {canManage && (
                                <Button size="sm" variant="ghost" className="h-8 w-8 p-0 text-muted-foreground hover:text-destructive" onClick={() => setConfirmDoc({ doc: d, op: "DELETE" })} aria-label={`Delete ${d.serialNo}`}><Trash2 className="size-3.5" /></Button>
                              )}
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── Issue Desk ── */}
        <TabsContent value="issue" className="space-y-4">
          {!canManage ? (
            <Card><CardContent className="py-10"><EmptyState icon={<ShieldBan className="size-5" />} title="Issuing is restricted" hint="You need the certificates.manage permission to issue documents." /></CardContent></Card>
          ) : (
            <div className="grid gap-4 lg:grid-cols-[1fr_320px] items-start">
              <Card>
                <CardHeader className="pb-2"><CardTitle className="text-base">Issue desk</CardTitle></CardHeader>
                <CardContent className="space-y-4">
                  <FormGrid>
                    <FormField label="Document type" required>
                      <Select value={form.type} onValueChange={(v) => setForm((f) => ({ ...f, type: v }))}>
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                          {DOC_TYPES.map((t) => <SelectItem key={t} value={t}>{TYPE_META[t].label} — {TYPE_META[t].prefix}</SelectItem>)}
                        </SelectContent>
                      </Select>
                    </FormField>
                    <FormField label="Learner" required hint={selectedStudent ? `Index ${selectedStudent.index}${selectedStudent.className ? ` · ${selectedStudent.className}` : ""}` : "Pick from the active roll"}>
                      <Select value={form.studentId} onValueChange={pickStudent}>
                        <SelectTrigger><SelectValue placeholder="Select learner…" /></SelectTrigger>
                        <SelectContent className="max-h-72">
                          {data?.students.map((st) => (
                            <SelectItem key={st.id} value={st.id}>{st.name} — {st.className ?? "no class"}</SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </FormField>
                    <FormField label="Issue date" required>
                      <DateField value={form.issueDate} onChange={(v) => setForm((f) => ({ ...f, issueDate: v ?? "" }))} aria-label="Issue date" />
                    </FormField>
                    <FormField label="Class stated on document" required hint="Pre-filled from the roll — adjust for leavers & graduates">
                      <Select value={form.className} onValueChange={(v) => setForm((f) => ({ ...f, className: v }))}>
                        <SelectTrigger><SelectValue placeholder="Select class…" /></SelectTrigger>
                        <SelectContent className="max-h-72">
                          {data?.classrooms.map((c) => <SelectItem key={c.id} value={c.name}>{c.name}</SelectItem>)}
                        </SelectContent>
                      </Select>
                    </FormField>
                    <FormField label="Academic year" required>
                      <Select value={form.sessionYear} onValueChange={(v) => setForm((f) => ({ ...f, sessionYear: v }))}>
                        <SelectTrigger><SelectValue placeholder="Select year…" /></SelectTrigger>
                        <SelectContent>
                          {data?.years.map((y) => <SelectItem key={y.id} value={y.name}>{y.name}{y.isCurrent ? " · current" : ""}</SelectItem>)}
                        </SelectContent>
                      </Select>
                    </FormField>
                    <FormField label="Reason / purpose" required={form.type === "LEAVING_CERT" || form.type === "INTRO_LETTER"} hint={form.type === "LEAVING_CERT" ? "e.g. Relocation of parents, admission to XYZ SHS" : form.type === "INTRO_LETTER" ? "e.g. Transfer interview at ABC School" : "Optional"}>
                      <Textarea value={form.reason} onChange={(e) => setForm((f) => ({ ...f, reason: e.target.value }))} rows={2} placeholder="Stated on the printed sheet" />
                    </FormField>
                    <FormField label="Conduct & remarks" hint="Optional — woven into the testimonial & certificate prose" span={3}>
                      <Textarea value={form.remarks} onChange={(e) => setForm((f) => ({ ...f, remarks: e.target.value }))} rows={2} placeholder="e.g. Honest, punctual and obedient; represented the school in the district science fair." />
                    </FormField>
                  </FormGrid>
                  <div className="flex justify-end">
                    <Button onClick={issue} disabled={busy} className="gap-1.5"><Stamp className="size-4" /> {busy ? "Issuing…" : "Issue & print"}</Button>
                  </div>
                </CardContent>
              </Card>

              <div className="space-y-3">
                {DOC_TYPES.map((t) => (
                  <Card key={t} className={cn("py-4", form.type === t && "ring-2 ring-primary/40")}>
                    <CardContent className="px-4">
                      <div className="flex items-center gap-2 mb-1">
                        <Badge className={cn("border-transparent", TYPE_STYLES[t])}>{TYPE_META[t].prefix}</Badge>
                        <span className="text-sm font-semibold">{TYPE_META[t].label}</span>
                      </div>
                      <p className="text-xs text-muted-foreground leading-relaxed">{TYPE_META[t].blurb}</p>
                    </CardContent>
                  </Card>
                ))}
              </div>
            </div>
          )}
        </TabsContent>

        {/* ── Formats ── */}
        <TabsContent value="formats" className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            {DOC_TYPES.map((t) => (
              <Card key={t}>
                <CardHeader className="pb-2">
                  <CardTitle className="text-base flex items-center gap-2">
                    {t === "LEAVING_CERT" ? <Award className="size-4 text-amber-600" /> : t === "TESTIMONIAL" ? <ScrollText className="size-4 text-emerald-600" /> : t === "ADMISSION_LETTER" ? <BookOpen className="size-4 text-teal-600" /> : <Send className="size-4 text-stone-500" />}
                    {TYPE_META[t].label}
                    <Badge variant="outline" className="font-mono text-[10px]">{TYPE_META[t].prefix}-YYYY-####</Badge>
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <p className="text-xs text-muted-foreground leading-relaxed mb-3">{TYPE_META[t].blurb}</p>
                  <div className="rounded-lg border bg-white text-[9px] leading-snug text-stone-800 overflow-hidden pointer-events-none select-none" style={{ minHeight: 150 }}>
                    <DocumentSheet doc={sampleFor(t, data)} school={data?.school ?? null} sample />
                  </div>
                  <Button size="sm" variant="outline" className="mt-3 gap-1.5" onClick={() => setPrintDoc(sampleFor(t, data))}><Printer className="size-3.5" /> Print sample</Button>
                </CardContent>
              </Card>
            ))}
          </div>
        </TabsContent>
      </Tabs>

      {/* ── print dialog ── */}
      <Dialog open={!!printDoc} onOpenChange={(o) => !o && setPrintDoc(null)}>
        <DialogContent className="max-w-[820px] max-h-[92vh] overflow-y-auto nice-scroll">
          <DialogHeader className="no-print">
            <DialogTitle>{printDoc?.serialNo} · {TYPE_META[(printDoc?.type ?? "LEAVING_CERT") as DocType]?.label}</DialogTitle>
            <DialogDescription>A4 portrait. The sheet prints exactly as shown — letterhead, particulars and signatory block.</DialogDescription>
          </DialogHeader>
          <div className="print-area">
            {printDoc && <DocumentSheet doc={printDoc} school={data?.school ?? null} />}
          </div>
          <DialogFooter className="no-print">
            <Button variant="outline" onClick={() => setPrintDoc(null)}>Close</Button>
            <Button className="gap-1.5" onClick={() => window.print()}><Printer className="size-4" /> Print</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── revoke / reinstate / delete confirm ── */}
      <Dialog open={!!confirmDoc} onOpenChange={(o) => !o && setConfirmDoc(null)}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>{confirmDoc?.op === "REVOKE" ? "Revoke document?" : confirmDoc?.op === "REINSTATE" ? "Reinstate document?" : "Delete document?"}</DialogTitle>
            <DialogDescription>
              {confirmDoc?.op === "REVOKE" && `${confirmDoc.doc.serialNo} (${confirmDoc.doc.studentName}) will be marked REVOKED — reprints carry a red watermark. The register keeps the audit trail.`}
              {confirmDoc?.op === "REINSTATE" && `${confirmDoc.doc.serialNo} will return to ISSUED and may be printed again.`}
              {confirmDoc?.op === "DELETE" && `${confirmDoc.doc.serialNo} will be removed from the register permanently. Prefer revoke when the sheet was already handed out.`}
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setConfirmDoc(null)}>Cancel</Button>
            <Button variant={confirmDoc?.op === "DELETE" ? "destructive" : confirmDoc?.op === "REVOKE" ? "destructive" : "default"} onClick={doOp} disabled={busy}>
              {busy ? "Working…" : confirmDoc?.op === "REVOKE" ? "Revoke" : confirmDoc?.op === "REINSTATE" ? "Reinstate" : "Delete"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

/* ── canned sample row for the Formats tab ── */
function sampleFor(type: DocType, data: Data | null): DocRow {
  return {
    id: `sample-${type}`,
    type,
    serialNo: `${TYPE_META[type].prefix}-${new Date().getUTCFullYear()}-0001`,
    issueDate: new Date().toISOString().slice(0, 10),
    studentId: "sample",
    studentName: "MENSAH, Kwame Ohene",
    studentIndex: "NY-2019-014258",
    className: "JHS 3 Gold",
    sessionYear: data?.years.find((y) => y.isCurrent)?.name ?? "2026/2027",
    dateOfBirth: "2011-04-18",
    reason: type === "LEAVING_CERT" ? "Successful completion of JHS — proceeding to senior high school" : type === "INTRO_LETTER" ? "Admission interview at Ghana National SHS" : null,
    remarks: "Honest, punctual and respectful; represented the school at the district science & maths quiz.",
    status: "ISSUED",
    issuedByName: null, revokedByName: null, revokedAt: null,
  };
}

/* ── sheet internals (module scope — never created during render) ── */
function SheetParticulars({ rows }: { rows: [string, string][] }) {
  return (
    <table className="w-full my-4 text-[11px]" cellPadding={0}>
      <tbody>
        {rows.map(([k, v], i) => (
          <tr key={k} className={i % 2 ? "bg-stone-50" : ""}>
            <td className="py-1.5 pr-3 font-semibold uppercase tracking-wide text-stone-500 w-[38%] align-top">{k}</td>
            <td className="py-1.5 font-medium text-stone-900">{v}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}

function SheetProse({ children }: { children: React.ReactNode }) {
  return <div className="text-[11.5px] leading-relaxed text-stone-800 space-y-3 text-justify">{children}</div>;
}

/* ── the A4 print sheet — Ghana-format official document ── */
function DocumentSheet({ doc, school, sample }: { doc: DocRow; school: SchoolLite | null; sample?: boolean }) {
  const hue = school?.logoColor ?? "#0F6B47";
  const type = (doc.type in TYPE_META ? doc.type : "LEAVING_CERT") as DocType;
  const title =
    type === "LEAVING_CERT" ? "SCHOOL LEAVING CERTIFICATE" :
    type === "TESTIMONIAL" ? "TESTIMONIAL" :
    type === "ADMISSION_LETTER" ? "OFFER OF ADMISSION" : "INTRODUCTORY LETTER";
  const dob = doc.dateOfBirth
    ? new Date(`${doc.dateOfBirth}T00:00:00Z`).toLocaleDateString("en-GB", { day: "numeric", month: "long", year: "numeric", timeZone: "UTC" })
    : "—";
  const issued = doc.issueDate
    ? new Date(`${doc.issueDate}T00:00:00Z`).toLocaleDateString("en-GB", { day: "numeric", month: "long", year: "numeric", timeZone: "UTC" })
    : "—";
  const addressLine = school ? `${school.town}${school.district ? `, ${school.district}` : ""} · ${school.region} Region${school.nasiaRegNo ? ` · NASIA Reg. ${school.nasiaRegNo}` : ""}` : "";

  const body = (() => {
    if (type === "LEAVING_CERT") {
      return (
        <>
          <SheetProse>
            <p>This is to certify that <b>{doc.studentName}</b> was a bona fide pupil of this school, having attended <b>{school?.name ?? "the school"}</b> during the <b>{doc.sessionYear}</b> academic year in <b>{doc.className}</b>.</p>
            {doc.reason && <p>The pupil is leaving the school on account of: <b>{doc.reason}</b>.</p>}
            {doc.remarks && <p>During the period of enrolment the pupil was observed to be {doc.remarks.charAt(0).toLowerCase()}{doc.remarks.slice(1)}</p>}
            <p>This certificate is issued at the request of the parent/guardian for presentation to the receiving institution. Any alteration renders it invalid.</p>
          </SheetProse>
          <SheetParticulars rows={[
            ["Full name of pupil", doc.studentName],
            ["Index number", doc.studentIndex],
            ["Date of birth", dob],
            ["Class last attended", doc.className],
            ["Academic year", doc.sessionYear],
            ["Date of issue", issued],
          ]} />
        </>
      );
    }
    if (type === "TESTIMONIAL") {
      return (
        <>
          <SheetProse>
            <p>TO WHOM IT MAY CONCERN</p>
            <p>I write to testify that <b>{doc.studentName}</b> (Index No. {doc.studentIndex}) was enrolled at <b>{school?.name ?? "this school"}</b> and studied in <b>{doc.className}</b> during the <b>{doc.sessionYear}</b> academic year.</p>
            <p>{doc.remarks ? `The school remembers ${doc.studentName.split(",")[0].trim()} as ${doc.remarks.charAt(0).toLowerCase()}${doc.remarks.slice(1)}` : `${doc.studentName.split(",")[0].trim()} conducted themselves well throughout the period of enrolment`}.</p>
            <p>I recommend the bearer wholeheartedly and will gladly supply further particulars on request.</p>
          </SheetProse>
          <SheetParticulars rows={[
            ["Full name", doc.studentName],
            ["Index number", doc.studentIndex],
            ["Date of birth", dob],
            ["Class", doc.className],
            ["Academic year", doc.sessionYear],
          ]} />
        </>
      );
    }
    if (type === "ADMISSION_LETTER") {
      return (
        <>
          <SheetProse>
            <p>Dear Parent/Guardian,</p>
            <p><b>RE: OFFER OF ADMISSION — {doc.studentName}</b></p>
            <p>We are pleased to inform you that <b>{doc.studentName}</b> has been offered admission to <b>{school?.name ?? "the school"}</b> into <b>{doc.className}</b> for the <b>{doc.sessionYear}</b> academic year.</p>
            <p>To secure the placement, kindly complete the enrolment formalities at the school office within two weeks of the date of this letter. Please come along with:</p>
            <ol className="list-decimal ml-5 space-y-0.5">
              <li>A copy of the child&rsquo;s birth certificate;</li>
              <li>The last report card or terminal results slip;</li>
              <li>Two recent passport photographs;</li>
              <li>Payment of the term&rsquo;s fees at the school cashier.</li>
            </ol>
            <p>We look forward to welcoming the young one into the {school?.name ?? "school"} family.</p>
          </SheetProse>
          <SheetParticulars rows={[
            ["Candidate", doc.studentName],
            ["Index number", doc.studentIndex],
            ["Admitted to class", doc.className],
            ["Academic year", doc.sessionYear],
            ["Date of offer", issued],
          ]} />
        </>
      );
    }
    return (
      <>
        <SheetProse>
          <p>TO WHOM IT MAY CONCERN</p>
          <p>This letter serves to introduce <b>{doc.studentName}</b> (Index No. {doc.studentIndex}), a pupil of <b>{school?.name ?? "this school"}</b>, currently in <b>{doc.className}</b> for the <b>{doc.sessionYear}</b> academic year.</p>
          {doc.reason && <p>The bearer is presenting this letter for the following purpose: <b>{doc.reason}</b>. Kindly give the bearer all the necessary assistance.</p>}
          {doc.remarks && <p>{doc.remarks}</p>}
          <p>Thank you for your cooperation.</p>
        </SheetProse>
        <SheetParticulars rows={[
          ["Full name", doc.studentName],
          ["Index number", doc.studentIndex],
          ["Class", doc.className],
          ["Academic year", doc.sessionYear],
          ["Date issued", issued],
        ]} />
      </>
    );
  })();

  return (
    <div
      className="a4-cert relative mx-auto bg-white text-stone-900 shadow-sm"
      style={{ width: "186mm", padding: "12mm 14mm", fontFamily: "Georgia, 'Times New Roman', serif" }}
    >
      {/* revocation watermark — shows on screen AND in print */}
      {doc.status === "REVOKED" && (
        <div className="absolute inset-0 z-10 grid place-items-center pointer-events-none">
          <span className="text-6xl font-black tracking-[0.3em] text-red-600/25 -rotate-45 border-4 border-red-600/25 px-8 py-2">REVOKED</span>
        </div>
      )}

      {/* letterhead */}
      <header className="text-center pb-2">
        <div className="mx-auto grid size-12 place-items-center rounded-full text-white text-lg font-bold" style={{ backgroundColor: hue }}>
          {(school?.name ?? "NS").split(/\s+/).filter((w) => /^[A-Za-z]/.test(w)).slice(0, 2).map((w) => w[0]).join("") || "NS"}
        </div>
        <h1 className="text-lg font-bold tracking-wide mt-1" style={{ color: hue }}>{school?.name ?? "NYANSAPO SCHOOL"}</h1>
        {school?.motto && <p className="text-[9.5px] italic text-stone-600">&ldquo;{school.motto}&rdquo;</p>}
        <p className="text-[9px] text-stone-600 mt-0.5">{addressLine}{school?.phone || school?.code ? ` · Tel/SMS ${school?.phone ?? school?.code}` : ""}</p>
        <div className="mt-1.5 h-[3px] w-full" style={{ background: `linear-gradient(90deg, transparent, ${hue} 18%, ${hue} 82%, transparent)` }} />
        <div className="mt-3 flex items-center justify-between">
          <span className="text-[9px] font-mono text-stone-500">No. {doc.serialNo}</span>
          <h2 className="text-sm font-bold tracking-[0.18em] underline underline-offset-4">{title}</h2>
          <span className="text-[9px] font-mono text-stone-500">{doc.issueDate}</span>
        </div>
      </header>

      {body}

      {/* footer: date + signature + stamp */}
      <footer className="mt-8 pt-2">
        <div className="flex items-end justify-between">
          <div className="text-[10px] text-stone-700">
            <div className="font-semibold">Dated at {school?.town ?? "…"}, {issued}</div>
            {sample && <div className="mt-1 text-[8.5px] italic text-stone-400">Sample sheet — Formats tab</div>}
          </div>
          <div className="flex items-end gap-6">
            <div className="grid place-items-center size-16 rounded-full border-2 text-center opacity-70" style={{ borderColor: hue, color: hue }}>
              <div>
                <div className="text-[7px] font-bold uppercase tracking-widest leading-tight">{(school?.name ?? "SCHOOL").split(/\s+/).slice(0, 2).join(" ")}</div>
                <div className="text-[6.5px] text-stone-500">{school?.code ?? ""}</div>
              </div>
            </div>
            <div className="text-center text-[10px]">
              <div className="mb-4 italic text-stone-500">………………….signature & seal…………………</div>
              <div className="border-t border-stone-800 pt-1 font-semibold">{doc.issuedByName ?? "The Headteacher"}</div>
              <div className="text-[9px] text-stone-600">Headteacher, {school?.name ?? ""}</div>
            </div>
          </div>
        </div>
        <div className="mt-3 text-center text-[8px] text-stone-400">
          {doc.serialNo} · issued {doc.issueDate} · this document is valid only with the official embossed seal{doc.status === "REVOKED" ? " · REVOKED — VOID" : ""}
        </div>
      </footer>
    </div>
  );
}

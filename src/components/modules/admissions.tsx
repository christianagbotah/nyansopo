"use client";
import { useEffect, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { ModuleHeader, LoadingBlock, StatusBadge, SeverityBadge, EmptyState } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, fmtDateTime, FLAG_LABELS } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { Search, UserPlus, FileSearch, ShieldAlert, ShieldCheck, Inbox, Send, ArrowLeftRight, Phone, Mail, MapPin, School as SchoolIcon, MessageSquare, Ban, CheckCheck, FileDown } from "lucide-react";

export function Admissions() {
  const { currentSchoolId, schools } = useStore();
  const [tab, setTab] = useState("inquiries");
  const [inquiries, setInquiries] = useState<any[] | null>(null);
  const [requests, setRequests] = useState<{ inbox: any[]; outbox: any[] } | null>(null);
  const [transfers, setTransfers] = useState<{ outgoing: any[]; incoming: any[] } | null>(null);
  const [addOpen, setAddOpen] = useState(false);
  const [lookupQ, setLookupQ] = useState("");
  const [lookupBusy, setLookupBusy] = useState(false);
  const [matches, setMatches] = useState<any[] | null>(null);
  const [reqFor, setReqFor] = useState<any | null>(null);
  const [respondTo, setRespondTo] = useState<any | null>(null);

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<any>(`/api/admissions/inquiries?schoolId=${currentSchoolId}`).then((d) => setInquiries(d.inquiries)).catch(() => setInquiries([]));
    apiGet<any>(`/api/admissions/requests?schoolId=${currentSchoolId}`).then(setRequests).catch(() => setRequests({ inbox: [], outbox: [] }));
    apiGet<any>(`/api/admissions/transfers?schoolId=${currentSchoolId}`).then(setTransfers).catch(() => setTransfers({ outgoing: [], incoming: [] }));
  };
  useEffect(load, [currentSchoolId]);

  async function runLookup(q?: string) {
    const query = (q ?? lookupQ).trim();
    if (query.length < 3) { toast({ title: "Enter at least 3 characters", description: "Search by learner name, guardian MoMo number or Nyansapo Index." }); return; }
    setLookupBusy(true); setTab("registry");
    try {
      const d = await apiGet<any>(`/api/registry?q=${encodeURIComponent(query)}`);
      setMatches(d.matches);
    } catch (e: any) { toast({ title: "Lookup failed", description: e.message, variant: "destructive" }); }
    finally { setLookupBusy(false); }
  }

  if (!inquiries || !requests || !transfers) return <LoadingBlock label="Loading admissions desk…" />;

  const pendingIn = requests.inbox.filter((r) => r.status === "PENDING").length;
  const pendingOut = transfers.outgoing.filter((t) => t.status === "PENDING_RELEASE").length;

  return (
    <div>
      <ModuleHeader
        title="Admissions & Transfers"
        description="The cross-school desk: check applicants against the national registry, pull their history from every prior school, request records and manage transfers."
        actions={
          <>
            <Button size="sm" variant="outline" className="gap-1.5" onClick={() => runLookup()}><FileSearch className="size-4" /> Registry check</Button>
            <Button size="sm" className="gap-1.5" onClick={() => setAddOpen(true)}><UserPlus className="size-4" /> New inquiry</Button>
          </>
        }
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Inquiries</div><div className="text-2xl font-bold">{inquiries.length}</div></CardContent></Card>
        <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Flagged applicants</div><div className="text-2xl font-bold text-destructive">{inquiries.filter((i) => i.status === "FLAGGED").length}</div></CardContent></Card>
        <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Record requests (inbox)</div><div className="text-2xl font-bold text-amber-600">{pendingIn}</div></CardContent></Card>
        <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Transfers pending release</div><div className="text-2xl font-bold text-orange-600">{pendingOut}</div></CardContent></Card>
      </div>

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="flex-wrap">
          <TabsTrigger value="inquiries">Inquiries</TabsTrigger>
          <TabsTrigger value="registry">Registry lookup</TabsTrigger>
          <TabsTrigger value="requests">Record requests</TabsTrigger>
          <TabsTrigger value="transfers">Transfers</TabsTrigger>
        </TabsList>

        {/* ── INQUIRIES ── */}
        <TabsContent value="inquiries">
          <div className="space-y-3">
            {inquiries.length === 0 && <Card><CardContent><EmptyState icon={<UserPlus className="size-5" />} title="No admission inquiries yet" hint="Log every walk-in applicant — Nyansapo auto-checks the registry." /></CardContent></Card>}
            {inquiries.map((inq) => (
              <Card key={inq.id}>
                <CardContent className="pt-5">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <div className="font-semibold">{inq.studentName} <span className="text-sm font-normal text-muted-foreground">→ {inq.classAppliedFor}</span></div>
                      <div className="text-xs text-muted-foreground mt-0.5">
                        Guardian: {inq.guardianName} · {inq.guardianPhone} · applied {fmtDate(inq.createdAt)}
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      {inq.status === "FLAGGED" && <Badge variant="destructive" className="gap-1"><ShieldAlert className="size-3" /> Flags found</Badge>}
                      {inq.status === "CLEARED" && <Badge className="gap-1 bg-emerald-600"><ShieldCheck className="size-3" /> Cleared</Badge>}
                      {inq.status === "NEW" && <Badge variant="secondary">New — no registry match</Badge>}
                      {inq.status === "ENROLLED" && <Badge className="gap-1"><CheckCheck className="size-3" /> Enrolled</Badge>}
                      {inq.status === "DECLINED" && <Badge variant="destructive">Declined</Badge>}
                    </div>
                  </div>

                  {inq.registrySummary?.matched && (
                    <div className={`mt-3 rounded-lg border p-3 text-sm ${inq.status === "FLAGGED" ? "border-red-300 bg-red-50 dark:bg-red-950/40 dark:border-red-900" : "border-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 dark:border-emerald-900"}`}>
                      <div className="font-medium flex items-center gap-1.5"><FileSearch className="size-4" /> Nyansapo registry match — {inq.matchedStudentName} ({inq.registrySummary.nyansapoIndex})</div>
                      <div className="text-xs mt-1 text-muted-foreground">
                        Prior schools: {inq.registrySummary.priorSchools} · Active flags: {inq.registrySummary.activeFlags}
                        {inq.registrySummary.flagSummary && <> · {inq.registrySummary.flagSummary}</>}
                      </div>
                      {inq.matchedStudentId && (
                        <Button size="sm" variant="outline" className="mt-2 gap-1.5" onClick={() => runLookup(inq.matchedStudentName)}>
                          <Search className="size-3.5" /> Open full history
                        </Button>
                      )}
                    </div>
                  )}
                  {inq.notes && <div className="text-xs text-muted-foreground mt-2">{inq.notes}</div>}
                </CardContent>
              </Card>
            ))}
          </div>
        </TabsContent>

        {/* ── REGISTRY ── */}
        <TabsContent value="registry">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><FileSearch className="size-4" /> National learner registry (Nyansapo Index)</CardTitle></CardHeader>
            <CardContent>
              <div className="flex gap-2 mb-4">
                <Input placeholder="Learner name, guardian MoMo number or index (e.g. Kofi Boateng / 0244778123 / NY-2014-004872)" value={lookupQ} onChange={(e) => setLookupQ(e.target.value)} onKeyDown={(e) => e.key === "Enter" && runLookup()} />
                <Button className="gap-1.5" onClick={() => runLookup()} disabled={lookupBusy}>{lookupBusy ? "Searching…" : <><Search className="size-4" /> Search</>}</Button>
              </div>
              {lookupBusy && <LoadingBlock label="Scanning the national registry…" />}
              {matches === null && !lookupBusy && <EmptyState icon={<Search className="size-5" />} title="Search to reveal a learner's school history" hint="Try “Kofi Boateng” or his guardian's MoMo 0244778123 — see the defaulter story live." />}
              {matches !== null && matches.length === 0 && <EmptyState icon={<Search className="size-5" />} title="No registry match" hint="The learner may be entering private basic education for the first time (e.g. from a public school)." />}
              <div className="space-y-4">
                {matches?.map((m) => (
                  <div key={m.id} className={`rounded-xl border-2 p-4 ${m.activeFinancialFlags || m.activeDisciplineFlags ? "border-red-300 dark:border-red-900" : "border-emerald-300 dark:border-emerald-900"}`}>
                    <div className="flex flex-wrap items-start justify-between gap-3">
                      <div>
                        <div className="font-bold text-lg">{m.name}</div>
                        <div className="text-xs text-muted-foreground font-mono">{m.nyansapoIndex} · {m.gender === "M" ? "Boy" : "Girl"} · DOB {fmtDate(m.dob)}</div>
                        <div className="text-xs text-muted-foreground mt-0.5">Guardian: {m.guardianName} · {m.guardianPhone}</div>
                      </div>
                      <div className="flex flex-col items-end gap-1.5">
                        {m.activeFinancialFlags > 0 && <Badge variant="destructive" className="gap-1"><ShieldAlert className="size-3" /> {m.activeFinancialFlags} financial flag{m.activeFinancialFlags > 1 ? "s" : ""}</Badge>}
                        {m.activeDisciplineFlags > 0 && <Badge variant="destructive" className="gap-1"><ShieldAlert className="size-3" /> {m.activeDisciplineFlags} discipline flag{m.activeDisciplineFlags > 1 ? "s" : ""}</Badge>}
                        {!m.activeFinancialFlags && !m.activeDisciplineFlags && <Badge className="gap-1 bg-emerald-600"><ShieldCheck className="size-3" /> No active flags</Badge>}
                        {m.status === "TRACKED" && <Badge variant="secondary">Not currently enrolled</Badge>}
                      </div>
                    </div>

                    <div className="grid md:grid-cols-2 gap-4 mt-4">
                      <div>
                        <div className="text-xs font-semibold uppercase text-muted-foreground mb-2 flex items-center gap-1.5"><SchoolIcon className="size-3.5" /> School history & contacts</div>
                        <div className="space-y-2">
                          {m.history.map((h: any, i: number) => (
                            <div key={i} className="rounded-lg border p-2.5 text-sm">
                              <div className="flex items-center justify-between gap-2">
                                <div className="font-medium">{h.school.name}</div>
                                <StatusBadge status={h.status} />
                              </div>
                              <div className="text-xs text-muted-foreground">{h.classroom} · {fmtDate(h.admissionDate)} → {h.exitDate ? fmtDate(h.exitDate) : "present"}</div>
                              {h.exitReason && <div className="text-xs text-destructive mt-0.5">{h.exitReason}</div>}
                              <div className="text-xs text-muted-foreground mt-1 flex flex-wrap gap-x-3">
                                <span className="flex items-center gap-1"><Phone className="size-3" /> {h.school.phone}</span>
                                <span className="flex items-center gap-1"><Mail className="size-3" /> {h.school.email}</span>
                                <span className="flex items-center gap-1"><MapPin className="size-3" /> {h.school.town}, {h.school.region}</span>
                              </div>
                            </div>
                          ))}
                        </div>
                      </div>
                      <div>
                        <div className="text-xs font-semibold uppercase text-muted-foreground mb-2 flex items-center gap-1.5"><ShieldAlert className="size-3.5" /> Flags & transfers</div>
                        <div className="space-y-2">
                          {m.flags.map((f: any) => (
                            <div key={f.id} className={`rounded-lg border p-2.5 text-sm ${f.status === "ACTIVE" ? "border-red-300 dark:border-red-900" : "border-border"}`}>
                              <div className="flex items-center justify-between gap-2">
                                <span className="font-medium">{FLAG_LABELS[f.type] ?? f.type}{f.amountOwed ? ` — ${fmtCedi(f.amountOwed)}` : ""}</span>
                                <div className="flex gap-1.5"><SeverityBadge severity={f.severity} /><StatusBadge status={f.status} /></div>
                              </div>
                              <div className="text-xs text-muted-foreground mt-1">Raised by {f.school.name} · {fmtDate(f.raisedAt)}{f.verified === "CONFIRMED" && " · ✅ confirmed by association"}</div>
                              <p className="text-xs mt-1 text-muted-foreground">{f.description}</p>
                            </div>
                          ))}
                          {m.transfers.map((t: any, i: number) => (
                            <div key={i} className="rounded-lg border p-2.5 text-sm flex items-center justify-between">
                              <span>{t.from} → {t.to}</span>
                              <div className="flex items-center gap-2"><span className="text-xs text-muted-foreground">{fmtDate(t.date)}</span><StatusBadge status={t.status} /></div>
                            </div>
                          ))}
                          {!m.flags.length && !m.transfers.length && <div className="text-xs text-muted-foreground">Clean record — no flags or transfers.</div>}
                        </div>
                      </div>
                    </div>

                    {/* actions */}
                    <div className="flex flex-wrap gap-2 mt-4 pt-3 border-t">
                      <Button size="sm" className="gap-1.5" onClick={() => setReqFor(m)}>
                        <Send className="size-3.5" /> Request full records from a prior school
                      </Button>
                      <Button size="sm" variant="outline" className="gap-1.5" onClick={() => { setLookupQ(m.guardianPhone); runLookup(m.guardianPhone); }}>
                        <Phone className="size-3.5" /> Search this guardian&apos;s other wards
                      </Button>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── RECORD REQUESTS ── */}
        <TabsContent value="requests">
          <div className="grid lg:grid-cols-2 gap-4">
            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Inbox className="size-4" /> Inbox — requests to your school</CardTitle></CardHeader>
              <CardContent className="space-y-3 max-h-[480px] overflow-y-auto nice-scroll">
                {requests.inbox.length === 0 && <EmptyState title="No incoming requests" icon={<Inbox className="size-5" />} />}
                {requests.inbox.map((r) => (
                  <div key={r.id} className={`rounded-lg border p-3 ${r.status === "PENDING" ? "border-amber-400 dark:border-amber-700 bg-amber-50/50 dark:bg-amber-950/20" : ""}`}>
                    <div className="flex items-start justify-between gap-2">
                      <div>
                        <div className="font-medium text-sm">{r.student}</div>
                        <div className="text-xs text-muted-foreground">from <b>{r.school.name}</b> · {fmtDateTime(r.requestedAt)}</div>
                      </div>
                      <StatusBadge status={r.status} />
                    </div>
                    {r.notes && <p className="text-xs mt-2 text-muted-foreground line-clamp-3">{r.notes}</p>}
                    {r.messages.length > 0 && (
                      <div className="mt-2 space-y-1.5">
                        {r.messages.map((msg: any, mi: number) => (
                          <div key={msg.id ?? `in-${r.id}-${mi}`} className="rounded bg-secondary/60 px-2.5 py-2 text-xs">
                            <div className="font-semibold">{msg.senderName} <span className="font-normal text-muted-foreground">· {msg.senderTitle}</span></div>
                            <div className="mt-0.5">{msg.body}</div>
                          </div>
                        ))}
                      </div>
                    )}
                    {r.status === "PENDING" && (
                      <div className="flex gap-2 mt-2.5">
                        <Button size="sm" className="h-8 gap-1" onClick={() => setRespondTo({ ...r, decision: "FULFILLED" })}><CheckCheck className="size-3.5" /> Release records</Button>
                        <Button size="sm" variant="destructive" className="h-8 gap-1" onClick={() => setRespondTo({ ...r, decision: "DECLINED" })}><Ban className="size-3.5" /> Decline</Button>
                      </div>
                    )}
                  </div>
                ))}
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Send className="size-4" /> Outbox — your requests</CardTitle></CardHeader>
              <CardContent className="space-y-3 max-h-[480px] overflow-y-auto nice-scroll">
                {requests.outbox.length === 0 && <EmptyState title="No outgoing requests" hint="Use Registry lookup → Request full records." icon={<Send className="size-5" />} />}
                {requests.outbox.map((r) => (
                  <div key={r.id} className="rounded-lg border p-3">
                    <div className="flex items-start justify-between gap-2">
                      <div>
                        <div className="font-medium text-sm">{r.student}</div>
                        <div className="text-xs text-muted-foreground">to <b>{r.school.name}</b> · {fmtDateTime(r.requestedAt)}</div>
                      </div>
                      <StatusBadge status={r.status} />
                    </div>
                    {r.messages.map((msg: any, mi: number) => (
                      <div key={msg.id ?? `out-${r.id}-${mi}`} className="rounded bg-secondary/60 px-2.5 py-2 text-xs mt-2">
                        <div className="font-semibold">{msg.senderName}</div>
                        <div className="mt-0.5">{msg.body}</div>
                      </div>
                    ))}
                  </div>
                ))}
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* ── TRANSFERS ── */}
        <TabsContent value="transfers">
          <div className="grid lg:grid-cols-2 gap-4">
            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><ArrowLeftRight className="size-4" /> Outgoing — learners who left your school</CardTitle></CardHeader>
              <CardContent className="space-y-3">
                {transfers.outgoing.length === 0 && <EmptyState title="No outgoing transfers" />}
                {transfers.outgoing.map((t) => (
                  <div key={t.id} className={`rounded-lg border p-3 ${t.status === "PENDING_RELEASE" ? "border-amber-400 dark:border-amber-700" : ""}`}>
                    <div className="flex items-start justify-between gap-2">
                      <div>
                        <div className="font-medium text-sm">{t.student}</div>
                        <div className="text-xs text-muted-foreground">to <b>{t.to.name}</b> · {fmtDate(t.date)}</div>
                        {t.outstanding > 0 && <div className="text-xs text-destructive mt-0.5">Outstanding: {fmtCedi(t.outstanding)}</div>}
                        {t.reason && <div className="text-xs text-muted-foreground mt-0.5">{t.reason}</div>}
                      </div>
                      <StatusBadge status={t.status} />
                    </div>
                    {t.status === "PENDING_RELEASE" && (
                      <div className="flex gap-2 mt-2.5">
                        <Button size="sm" className="h-8 gap-1" onClick={async () => {
                          await apiPost(`/api/student/${t.studentId}`, { action: "release-transfer", transferId: t.id, approve: true, note: "Documents released." });
                          toast({ title: "Transfer released", description: "Documents released — the receiving school can now complete admission." });
                          load();
                        }}><CheckCheck className="size-3.5" /> Release documents</Button>
                        <Button size="sm" variant="destructive" className="h-8 gap-1" onClick={async () => {
                          await apiPost(`/api/student/${t.studentId}`, { action: "release-transfer", transferId: t.id, approve: false, note: "Release declined pending fee settlement." });
                          toast({ title: "Transfer declined", description: "The receiving school and the association will be notified." });
                          load();
                        }}><Ban className="size-3.5" /> Decline</Button>
                      </div>
                    )}
                  </div>
                ))}
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><ArrowLeftRight className="size-4" /> Incoming — learners who joined you</CardTitle></CardHeader>
              <CardContent className="space-y-3">
                {transfers.incoming.length === 0 && <EmptyState title="No incoming transfers" />}
                {transfers.incoming.map((t) => (
                  <div key={t.id} className="rounded-lg border p-3 flex items-start justify-between gap-2">
                    <div>
                      <div className="font-medium text-sm">{t.student}</div>
                      <div className="text-xs text-muted-foreground">from <b>{t.from.name}</b> · {fmtDate(t.date)}</div>
                      {t.reason && <div className="text-xs text-muted-foreground mt-0.5">{t.reason}</div>}
                    </div>
                    <StatusBadge status={t.status} />
                  </div>
                ))}
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>

      {/* new inquiry */}
      <InquiryDialog open={addOpen} onOpenChange={setAddOpen} onDone={load} onLookup={(name, phone) => { setTab("registry"); setLookupQ(name || phone); runLookup(phone || name); }} />
      {/* request records */}
      <RequestDialog student={reqFor} onOpenChange={(o) => !o && setReqFor(null)} onDone={load} />
      {/* respond */}
      <RespondDialog request={respondTo} onOpenChange={(o) => !o && setRespondTo(null)} onDone={load} />
    </div>
  );
}

function InquiryDialog({ open, onOpenChange, onDone, onLookup }: { open: boolean; onOpenChange: (o: boolean) => void; onDone: () => void; onLookup: (name: string, phone: string) => void }) {
  const { currentSchoolId } = useStore();
  const [busy, setBusy] = useState(false);
  const [form, setForm] = useState({ studentName: "", gender: "M", dateOfBirth: "", guardianName: "", guardianPhone: "", classAppliedFor: "" });
  const [result, setResult] = useState<any | null>(null);

  async function submit() {
    if (!form.studentName || !form.guardianPhone || !form.classAppliedFor) { toast({ title: "Name, guardian MoMo and class are required" }); return; }
    setBusy(true); setResult(null);
    try {
      const res = await apiPost<any>(`/api/admissions/inquiries`, { action: "create", schoolId: currentSchoolId, ...form });
      setResult({ status: res.inquiry.status, match: res.match });
      toast({
        title: res.inquiry.status === "FLAGGED" ? "⚠ Registry match with flags!" : res.inquiry.status === "CLEARED" ? "Registry match — cleared" : "No registry match",
        description: res.inquiry.status === "FLAGGED" ? "Admission should be held pending clearance from the prior school." : undefined,
        variant: res.inquiry.status === "FLAGGED" ? "destructive" : undefined,
      });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  }

  return (
    <Dialog open={open} onOpenChange={(o) => { onOpenChange(o); if (!o) { setResult(null); setForm({ studentName: "", gender: "M", dateOfBirth: "", guardianName: "", guardianPhone: "", classAppliedFor: "" }); } }}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>New admission inquiry</DialogTitle>
          <DialogDescription>Nyansapo automatically checks the national registry (by guardian MoMo & name) the moment you save.</DialogDescription>
        </DialogHeader>
        <div className="grid grid-cols-2 gap-3">
          <div className="space-y-1.5"><Label>Applicant name *</Label><Input value={form.studentName} onChange={(e) => setForm({ ...form, studentName: e.target.value })} placeholder="e.g. Ama Mensah" /></div>
          <div className="space-y-1.5">
            <Label>Class applied for *</Label>
            <Input value={form.classAppliedFor} onChange={(e) => setForm({ ...form, classAppliedFor: e.target.value })} placeholder="e.g. Basic 5" />
          </div>
          <div className="space-y-1.5">
            <Label>Gender</Label>
            <Select value={form.gender} onValueChange={(v) => setForm({ ...form, gender: v })}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent><SelectItem value="M">Boy</SelectItem><SelectItem value="F">Girl</SelectItem></SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5"><Label>Date of birth</Label><Input type="date" value={form.dateOfBirth} onChange={(e) => setForm({ ...form, dateOfBirth: e.target.value })} /></div>
          <div className="space-y-1.5"><Label>Guardian name</Label><Input value={form.guardianName} onChange={(e) => setForm({ ...form, guardianName: e.target.value })} /></div>
          <div className="space-y-1.5"><Label>Guardian MoMo *</Label><Input value={form.guardianPhone} onChange={(e) => setForm({ ...form, guardianPhone: e.target.value })} placeholder="024 000 0000" /></div>
        </div>
        {result?.match && (
          <div className={`rounded-lg border p-3 text-sm ${result.status === "FLAGGED" ? "border-red-300 bg-red-50 dark:bg-red-950/40" : "border-emerald-300 bg-emerald-50 dark:bg-emerald-950/40"}`}>
            <b>{result.match.name}</b> ({result.match.nyansapoIndex})
            {result.match.activeFlags?.length > 0 ? (
              <div className="text-xs mt-1">⚠ {result.match.activeFlags.length} active flag(s) — admission on hold pending clearance.</div>
            ) : <div className="text-xs mt-1">Clean record — no active flags.</div>}
          </div>
        )}
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Close</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Checking registry…" : "Save & check registry"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function RequestDialog({ student, onOpenChange, onDone }: { student: any | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId, schools, session } = useStore();
  const [targetSchoolId, setTargetSchoolId] = useState("");
  const [notes, setNotes] = useState("");
  const [busy, setBusy] = useState(false);

  const priorSchools = student?.history?.filter((h: any) => h.status !== "ACTIVE") ?? [];
  const activeSchool = student?.history?.find((h: any) => h.current)?.school;

  async function submit() {
    if (!student || !targetSchoolId) { toast({ title: "Choose the school to request from" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/admissions/requests`, { action: "create", studentId: student.id, targetSchoolId, requestingSchoolId: currentSchoolId, purpose: "ADMISSION", notes });
      toast({ title: "Record request sent", description: "GNACOPS etiquette: the target school should respond within 3 working days." });
      onOpenChange(false); onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  }

  return (
    <Dialog open={!!student} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><FileDown className="size-4" /> Request full records — {student?.name}</DialogTitle>
          <DialogDescription>Ask a prior school for academic records, fee position and conduct history. The request and the response are logged permanently.</DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <div className="space-y-1.5">
            <Label>Request from</Label>
            <Select value={targetSchoolId} onValueChange={setTargetSchoolId}>
              <SelectTrigger><SelectValue placeholder="Choose school" /></SelectTrigger>
              <SelectContent>
                {student?.history?.map((h: any, i: number) => (
                  <SelectItem key={i} value={h.school.id}>{h.school.name} ({h.classroom})</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Message</Label>
            <Textarea rows={4} value={notes} onChange={(e) => setNotes(e.target.value)} placeholder="We are processing an admission application. Kindly share the learner's academic records, fee position and any conduct notes. Thank you." />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy} className="gap-1.5"><Send className="size-4" /> {busy ? "Sending…" : "Send request"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function RespondDialog({ request, onOpenChange, onDone }: { request: any | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);
  const decision = request?.decision;
  useEffect(() => {
    if (request) setNote(request.decision === "DECLINED" ? "We are unable to release records at this time as the fee balance remains unsettled. Kindly contact the bursar to arrange clearance." : "Records released: term reports for 2024/25–2025/26, fee statement and conduct record. No outstanding issues after settlement.");
  }, [request]);

  async function submit() {
    if (!request) return;
    setBusy(true);
    try {
      await apiPost(`/api/admissions/requests`, { action: "respond", requestId: request.id, decision, note });
      toast({ title: decision === "DECLINED" ? "Request declined" : "Records released", description: "The requesting school has been notified." });
      onOpenChange(false); onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  }

  return (
    <Dialog open={!!request} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{decision === "DECLINED" ? "Decline request" : "Release records"} — {request?.student}</DialogTitle>
          <DialogDescription>Requested by {request?.school?.name}. Your response is logged and visible to the association if disputed.</DialogDescription>
        </DialogHeader>
        <div className="space-y-1.5">
          <Label>Response note</Label>
          <Textarea rows={5} value={note} onChange={(e) => setNote(e.target.value)} />
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button variant={decision === "DECLINED" ? "destructive" : "default"} onClick={submit} disabled={busy}>{busy ? "Sending…" : "Confirm"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

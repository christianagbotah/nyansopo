"use client";
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { ModuleHeader, LoadingBlock, EmptyState, FilterSelect } from "@/components/ui-helpers";
import { fmtDate } from "@/lib/format";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { Textarea } from "@/components/ui/textarea";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { toast } from "@/hooks/use-toast";
import { BookOpen, BookPlus, BookMarked, Library, AlertTriangle, Users, Search, RotateCcw, Pencil, Trash2, BookUp2, Check, X, Hourglass, XCircle, Inbox } from "lucide-react";

type Book = { id: string; title: string; author: string | null; isbn: string | null; category: string; copies: number; available: number; shelf: string | null; borrowers: string[] };
type Loan = {
  id: string; bookId: string; book: string; studentId: string; student: string; classroom: string | null;
  issuedAt: string; dueAt: string; returnedAt: string | null; issuedByName: string | null; conditionNote: string | null;
  overdue: boolean; daysOverdue: number;
};
// iEduPro parity: learner/parent book requests waiting for the librarian's decision
type BookRequestRow = {
  id: string; bookId: string; book: string; studentId: string; student: string; classroom: string | null;
  startAt: string; endAt: string; status: string; handledAt: string | null; handledByName: string | null;
  loanId: string | null; createdAt: string; overdue: boolean;
};
type LibData = { stats: { titles: number; copies: number; available: number; activeLoans: number; overdue: number; members: number }; books: Book[]; loans: Loan[]; requests: BookRequestRow[]; pendingRequests: number };
type StudentLite = { id: string; name: string; nyansapoIndex: string; classroom: string };

const CATEGORIES = ["Storybook", "Textbook", "Reference", "Fiction", "Science", "History", "Language", "General"];

export function LibraryPortal() {
  const { currentSchoolId } = useStore();
  const [data, setData] = useState<LibData | null>(null);
  const [loading, setLoading] = useState(true);
  const [bookDialog, setBookDialog] = useState<{ open: boolean; book: Book | null }>({ open: false, book: null });
  const [issueOpen, setIssueOpen] = useState(false);
  const [search, setSearch] = useState("");
  const [catFilter, setCatFilter] = useState("all");
  const [availFilter, setAvailFilter] = useState("all");

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<LibData>(`/api/school/${currentSchoolId}/ops/library`)
      .then(setData)
      .catch((e) => toast({ title: "Could not load library", description: e.message, variant: "destructive" }))
      .finally(() => setLoading(false));
  }, [currentSchoolId]);
  useEffect(() => { load(); }, [load]);

  async function act(body: Record<string, unknown>, okMsg: string) {
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/library`, body);
      toast({ title: okMsg });
      load();
      return true;
    } catch (e: any) {
      toast({ title: "Action failed", description: e.message, variant: "destructive" });
      return false;
    }
  }

  const filteredBooks = useMemo(() => {
    if (!data) return [];
    const q = search.toLowerCase();
    return data.books.filter((b) =>
      (!q || `${b.title} ${b.author ?? ""} ${b.category} ${b.isbn ?? ""}`.toLowerCase().includes(q)) &&
      (catFilter === "all" || b.category === catFilter) &&
      (availFilter === "all" || (availFilter === "in" ? b.available > 0 : b.available === 0)));
  }, [data, search, catFilter, availFilter]);
  const catFiltersActive = catFilter !== "all" || availFilter !== "all" || !!search.trim();

  if (loading || !data) return <LoadingBlock label="Opening the library…" />;

  const activeLoans = data.loans.filter((l) => !l.returnedAt);
  const returned = data.loans.filter((l) => l.returnedAt);

  return (
    <div>
      <ModuleHeader
        title="Library"
        description="Catalogue, circulation and members — issue books, track due dates and chase overdues."
        actions={
          <>
            <Button size="sm" variant="outline" className="gap-1.5" onClick={() => setIssueOpen(true)}><BookUp2 className="size-4" /> Issue book</Button>
            <Button size="sm" className="gap-1.5" onClick={() => setBookDialog({ open: true, book: null })}><BookPlus className="size-4" /> Add book</Button>
          </>
        }
      />

      <div className="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
        {[
          { icon: <BookOpen className="size-4" />, label: "Titles", value: data.stats.titles, sub: `${data.stats.copies} copies`, tone: "default" as const },
          { icon: <BookMarked className="size-4" />, label: "On loan", value: data.stats.activeLoans, sub: `${data.stats.available} on shelf`, tone: "default" as const },
          { icon: <AlertTriangle className="size-4" />, label: "Overdue", value: data.stats.overdue, sub: data.stats.overdue ? "needs follow-up" : "all clear", tone: data.stats.overdue ? ("danger" as const) : ("default" as const) },
          { icon: <Users className="size-4" />, label: "Active members", value: data.stats.members, sub: "borrowing now", tone: "default" as const },
          { icon: <Library className="size-4" />, label: "Returned", value: returned.length, sub: "in recent history", tone: "default" as const },
        ].map((s) => (
          <div key={s.label} className={`rounded-xl border bg-card p-4 ${s.tone === "danger" ? "border-destructive/30" : ""}`}>
            <div className="flex items-center justify-between"><span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{s.label}</span>{s.icon}</div>
            <div className={`mt-1.5 text-2xl font-bold tabular-nums ${s.tone === "danger" ? "text-destructive" : ""}`}>{s.value as number}</div>
            <div className="text-[11px] text-muted-foreground">{s.sub}</div>
          </div>
        ))}
      </div>

      <Tabs defaultValue="catalogue">
        <TabsList>
          <TabsTrigger value="catalogue">Catalogue ({data.books.length})</TabsTrigger>
          <TabsTrigger value="circulation">On loan ({activeLoans.length})</TabsTrigger>
          {data.stats.overdue > 0 && <TabsTrigger value="overdue" className="text-destructive data-[state=active]:text-destructive gap-1.5">Overdue <span className="inline-flex items-center justify-center rounded-full bg-destructive px-1.5 min-w-4 h-4 text-[10px] font-bold text-white">{data.stats.overdue}</span></TabsTrigger>}
          <TabsTrigger value="requests" className="gap-1.5">
            Requests
            {data.pendingRequests > 0 && <span className="inline-flex items-center justify-center rounded-full bg-amber-600 px-1.5 min-w-4 h-4 text-[10px] font-bold text-white">{data.pendingRequests}</span>}
          </TabsTrigger>
          <TabsTrigger value="history">History</TabsTrigger>
        </TabsList>

        <TabsContent value="catalogue" className="mt-3">
          <div className="rounded-xl border bg-card">
            <div className="p-3 border-b flex flex-wrap items-center gap-2">
              <div className="relative flex-1 max-w-sm">
                <Search className="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search title, author, ISBN…" className="pl-8" />
              </div>
              <FilterSelect label="Category" value={catFilter} onChange={setCatFilter}
                options={[{ value: "all", label: "All categories" }, ...CATEGORIES.map((c) => ({ value: c, label: c }))]} />
              <FilterSelect label="Shelf" value={availFilter} onChange={setAvailFilter}
                options={[{ value: "all", label: "All" }, { value: "in", label: "On shelf" }, { value: "out", label: "All copies out" }]} />
              {catFiltersActive && (
                <Button size="sm" variant="ghost" className="h-7 gap-1 text-xs" onClick={() => { setCatFilter("all"); setAvailFilter("all"); setSearch(""); }}><X className="size-3" /> Reset</Button>
              )}
              <Badge variant="secondary" className="text-[10px] tabular-nums ml-auto">{filteredBooks.length} of {data.books.length}</Badge>
            </div>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Title</TableHead>
                  <TableHead className="hidden md:table-cell">Category</TableHead>
                  <TableHead>Shelf copies</TableHead>
                  <TableHead className="hidden lg:table-cell">Shelf</TableHead>
                  <TableHead className="w-24" />
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredBooks.map((b) => (
                  <TableRow key={b.id}>
                    <TableCell>
                      <div className="font-medium">{b.title}</div>
                      <div className="text-xs text-muted-foreground">{b.author ?? "—"}{b.isbn ? ` · ISBN ${b.isbn}` : ""}</div>
                      {!!b.borrowers.length && (
                        <div className="text-[10px] text-amber-600 dark:text-amber-400 mt-0.5">with: {b.borrowers.join(", ")}</div>
                      )}
                    </TableCell>
                    <TableCell className="hidden md:table-cell"><Badge variant="outline">{b.category}</Badge></TableCell>
                    <TableCell>
                      <span className="font-semibold tabular-nums">{b.available}</span>
                      <span className="text-muted-foreground"> / {b.copies}</span>
                      {b.available === 0 && <Badge className="ml-2 bg-amber-600 text-[10px]">all out</Badge>}
                    </TableCell>
                    <TableCell className="hidden lg:table-cell text-xs text-muted-foreground">{b.shelf ?? "—"}</TableCell>
                    <TableCell>
                      <div className="flex gap-1">
                        <Button variant="ghost" size="icon" className="size-8" title="Edit" onClick={() => setBookDialog({ open: true, book: b })}><Pencil className="size-3.5" /></Button>
                        <Button variant="ghost" size="icon" className="size-8 text-destructive" title="Delete" onClick={() => confirm(`Remove "${b.title}" from the catalogue?`) && act({ action: "book.delete", id: b.id }, "Book removed")}><Trash2 className="size-3.5" /></Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
            {!filteredBooks.length && <EmptyState icon={<BookOpen className="size-8" />} title={search ? "No matches" : "Catalogue is empty"} hint={search ? "Try another search." : "Add your first book to get started."} />}
          </div>
        </TabsContent>

        <TabsContent value="circulation" className="mt-3">
          <LoanTable loans={activeLoans} onReturn={(id) => act({ action: "loan.return", loanId: id }, "Book returned")} />
        </TabsContent>

        {data.stats.overdue > 0 && (
          <TabsContent value="overdue" className="mt-3">
            <LoanTable loans={activeLoans.filter((l) => l.overdue)} onReturn={(id) => act({ action: "loan.return", loanId: id }, "Book returned")} highlight />
          </TabsContent>
        )}

        {/* ── REQUESTS (iEduPro parity): pending queue + decision history ── */}
        <TabsContent value="requests" className="mt-3">
          <RequestsTab
            requests={data.requests}
            books={data.books}
            onAccept={(id) => act({ action: "request.accept", id }, "Book issued — request accepted")}
            onReject={(id) => act({ action: "request.reject", id }, "Request declined")}
          />
        </TabsContent>

        <TabsContent value="history" className="mt-3">
          <LoanTable loans={returned} />
        </TabsContent>
      </Tabs>

      <BookDialog
        open={bookDialog.open} book={bookDialog.book} onOpenChange={(o) => setBookDialog({ open: o, book: bookDialog.book })}
        onSave={async (payload) => {
          const ok = await act({ action: "book.save", ...payload }, bookDialog.book ? "Book updated" : "Book added");
          if (ok) setBookDialog({ open: false, book: null });
        }}
      />
      <IssueDialog
        open={issueOpen} onOpenChange={setIssueOpen} books={data.books.filter((b) => b.available > 0)} schoolId={currentSchoolId!}
        onIssue={async (payload) => {
          const ok = await act({ action: "loan.issue", ...payload }, "Book issued");
          if (ok) setIssueOpen(false);
        }}
      />
    </div>
  );
}

function LoanTable({ loans, onReturn, highlight }: { loans: Loan[]; onReturn?: (id: string) => void; highlight?: boolean }) {
  if (!loans.length) return <EmptyState icon={<BookMarked className="size-8" />} title="Nothing here" hint={highlight ? "No overdue books — great!" : "No loans to show."} />;
  return (
    <div className="rounded-xl border bg-card">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Book</TableHead>
            <TableHead>Learner</TableHead>
            <TableHead className="hidden md:table-cell">Issued</TableHead>
            <TableHead>Due</TableHead>
            <TableHead className="hidden lg:table-cell">By</TableHead>
            {onReturn && <TableHead className="w-24" />}
          </TableRow>
        </TableHeader>
        <TableBody>
          {loans.map((l) => (
            <TableRow key={l.id} className={l.overdue ? "bg-destructive/5" : undefined}>
              <TableCell className="font-medium">{l.book}</TableCell>
              <TableCell>
                <div>{l.student}</div>
                <div className="text-xs text-muted-foreground">{l.classroom ?? "—"}</div>
              </TableCell>
              <TableCell className="hidden md:table-cell text-xs text-muted-foreground">{fmtDate(l.issuedAt)}</TableCell>
              <TableCell>
                {l.returnedAt ? (
                  <Badge variant="secondary" className="gap-1"><RotateCcw className="size-3" /> returned {fmtDate(l.returnedAt)}</Badge>
                ) : l.overdue ? (
                  <Badge className="bg-destructive gap-1"><AlertTriangle className="size-3" /> {l.daysOverdue}d overdue</Badge>
                ) : (
                  <span className="text-xs text-muted-foreground">{fmtDate(l.dueAt)}</span>
                )}
              </TableCell>
              <TableCell className="hidden lg:table-cell text-xs text-muted-foreground">{l.issuedByName ?? "—"}</TableCell>
              {onReturn && (
                <TableCell>
                  <Button size="sm" variant="outline" className="h-7 gap-1 text-xs" onClick={() => onReturn(l.id)}>
                    <RotateCcw className="size-3" /> Return
                  </Button>
                </TableCell>
              )}
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}

/* ── REQUESTS tab (iEduPro parity) — pending queue with Accept/Reject plus the
    decision history. Accept issues the book on the requested dates (blocked when
    no copies are on the shelf); Reject closes the request. ── */
function RequestsTab({ requests, books, onAccept, onReject }: {
  requests: BookRequestRow[]; books: Book[];
  onAccept: (id: string) => Promise<boolean>; onReject: (id: string) => Promise<boolean>;
}) {
  const [busyId, setBusyId] = useState<string | null>(null);
  const pending = requests.filter((r) => r.status === "PENDING");
  const history = requests.filter((r) => r.status !== "PENDING");

  async function run(fn: (id: string) => Promise<boolean>, id: string) {
    setBusyId(id);
    await fn(id);
    setBusyId(null);
  }

  return (
    <div className="space-y-4">
      <div className="rounded-xl border bg-card">
        <div className="p-3 border-b flex items-center justify-between gap-2">
          <div className="text-sm font-semibold flex items-center gap-2"><Inbox className="size-4 text-amber-600" /> Pending queue</div>
          <Badge variant="secondary">{pending.length} waiting</Badge>
        </div>
        {pending.length ? (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Book</TableHead>
                <TableHead>Learner</TableHead>
                <TableHead className="hidden md:table-cell">Requested dates</TableHead>
                <TableHead className="hidden lg:table-cell">Requested at</TableHead>
                <TableHead className="w-40" />
              </TableRow>
            </TableHeader>
            <TableBody>
              {pending.map((r) => {
                const book = books.find((b) => b.id === r.bookId) ?? null;
                const noCopies = !book || book.available < 1;
                return (
                  <TableRow key={r.id}>
                    <TableCell>
                      <div className="font-medium">{r.book}</div>
                      <div className="text-xs text-muted-foreground">
                        {book ? `${book.available} of ${book.copies} on shelf` : "—"}
                        {noCopies && <span className="text-amber-600 dark:text-amber-400"> · none available</span>}
                      </div>
                    </TableCell>
                    <TableCell>
                      <div>{r.student}</div>
                      <div className="text-xs text-muted-foreground">{r.classroom ?? "—"}</div>
                    </TableCell>
                    <TableCell className="hidden md:table-cell text-xs text-muted-foreground">
                      {fmtDate(r.startAt)} → {fmtDate(r.endAt)}
                    </TableCell>
                    <TableCell className="hidden lg:table-cell text-xs text-muted-foreground">{fmtDate(r.createdAt)}</TableCell>
                    <TableCell>
                      <div className="flex gap-1.5 justify-end">
                        <Button size="sm" className="h-7 gap-1 text-xs" disabled={busyId === r.id || noCopies}
                          title={noCopies ? "No copy of this book is available" : "Issue the book for the requested dates"}
                          onClick={() => run(onAccept, r.id)}>
                          <Check className="size-3.5" /> Accept
                        </Button>
                        <Button size="sm" variant="outline" className="h-7 gap-1 text-xs text-destructive" disabled={busyId === r.id}
                          onClick={() => confirm(`Decline ${r.student}'s request for "${r.book}"?`) && run(onReject, r.id)}>
                          <X className="size-3.5" /> Reject
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        ) : (
          <EmptyState icon={<Inbox className="size-8" />} title="No pending requests" hint="Requests from learners and parents land here for a decision." />
        )}
      </div>

      <div className="rounded-xl border bg-card">
        <div className="p-3 border-b text-sm font-semibold flex items-center gap-2"><Hourglass className="size-4 text-muted-foreground" /> Decision history</div>
        {history.length ? (
          <div className="max-h-96 overflow-y-auto nice-scroll">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Book</TableHead>
                  <TableHead>Learner</TableHead>
                  <TableHead className="hidden md:table-cell">Dates</TableHead>
                  <TableHead>Decision</TableHead>
                  <TableHead className="hidden lg:table-cell">Handled</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {history.map((r) => (
                  <TableRow key={r.id} className={r.overdue ? "bg-destructive/5" : undefined}>
                    <TableCell className="font-medium">{r.book}</TableCell>
                    <TableCell>
                      <div>{r.student}</div>
                      <div className="text-xs text-muted-foreground">{r.classroom ?? "—"}</div>
                    </TableCell>
                    <TableCell className="hidden md:table-cell text-xs text-muted-foreground">{fmtDate(r.startAt)} → {fmtDate(r.endAt)}</TableCell>
                    <TableCell>
                      <div className="flex flex-col gap-1 items-start">
                        {r.status === "ISSUED"
                          ? <Badge className="bg-emerald-600 gap-1"><Check className="size-3" /> issued</Badge>
                          : <Badge variant="destructive" className="gap-1"><XCircle className="size-3" /> declined</Badge>}
                        {r.overdue && <Badge className="bg-destructive gap-1"><AlertTriangle className="size-3" /> overdue</Badge>}
                      </div>
                    </TableCell>
                    <TableCell className="hidden lg:table-cell text-xs text-muted-foreground">
                      {r.handledByName ?? "—"}{r.handledAt ? ` · ${fmtDate(r.handledAt)}` : ""}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        ) : (
          <EmptyState title="No decisions yet" hint="Accepted and declined requests appear here." />
        )}
      </div>
    </div>
  );
}

function BookDialog({ open, book, onOpenChange, onSave }: {
  open: boolean; book: Book | null; onOpenChange: (o: boolean) => void;
  onSave: (payload: Record<string, unknown>) => Promise<void>;
}) {
  const [title, setTitle] = useState("");
  const [author, setAuthor] = useState("");
  const [isbn, setIsbn] = useState("");
  const [category, setCategory] = useState("Storybook");
  const [copies, setCopies] = useState("1");
  const [shelf, setShelf] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (open) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setTitle(book?.title ?? ""); setAuthor(book?.author ?? ""); setIsbn(book?.isbn ?? "");
      setCategory(book?.category ?? "Storybook"); setCopies(String(book?.copies ?? 1)); setShelf(book?.shelf ?? "");
    }
  }, [open, book]);

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle>{book ? "Edit book" : "Add book"}</DialogTitle>
          <DialogDescription>Catalogue entry — copies tracked against active loans.</DialogDescription>
        </DialogHeader>
        <div className="grid gap-3">
          <div className="space-y-1.5"><Label>Title *</Label><Input value={title} onChange={(e) => setTitle(e.target.value)} placeholder="Anansi the Spider Tales" /></div>
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5"><Label>Author</Label><Input value={author} onChange={(e) => setAuthor(e.target.value)} placeholder="Ghanaian folktale" /></div>
            <div className="space-y-1.5"><Label>ISBN</Label><Input value={isbn} onChange={(e) => setIsbn(e.target.value)} /></div>
          </div>
          <div className="grid grid-cols-3 gap-3">
            <div className="space-y-1.5 col-span-2">
              <Label>Category</Label>
              <Select value={category} onValueChange={setCategory}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>{CATEGORIES.map((c) => <SelectItem key={c} value={c}>{c}</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5"><Label>Copies</Label><Input type="number" min={1} value={copies} onChange={(e) => setCopies(e.target.value)} /></div>
          </div>
          <div className="space-y-1.5"><Label>Shelf / location</Label><Input value={shelf} onChange={(e) => setShelf(e.target.value)} placeholder="Shelf B-3" /></div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button disabled={busy || !title} onClick={async () => { setBusy(true); await onSave({ id: book?.id, title, author, isbn, category, copies, shelf }); setBusy(false); }}>
            {book ? "Save changes" : "Add to catalogue"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function IssueDialog({ open, onOpenChange, books, schoolId, onIssue }: {
  open: boolean; onOpenChange: (o: boolean) => void; books: Book[]; schoolId: string;
  onIssue: (payload: Record<string, unknown>) => Promise<void>;
}) {
  const [search, setSearch] = useState("");
  const [students, setStudents] = useState<StudentLite[]>([]);
  const [student, setStudent] = useState<StudentLite | null>(null);
  const [bookId, setBookId] = useState("");
  const [days, setDays] = useState("14");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (!open) { setSearch(""); setStudents([]); setStudent(null); setBookId(""); setDays("14"); }
  }, [open]);

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (!search.trim()) { setStudents([]); return; }
    const t = setTimeout(() => {
      apiGet<{ students: StudentLite[] }>(`/api/school/${schoolId}/students?search=${encodeURIComponent(search)}`)
        .then((d) => setStudents(d.students.slice(0, 6)))
        .catch(() => setStudents([]));
    }, 250);
    return () => clearTimeout(t);
  }, [search, schoolId]);

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><BookUp2 className="size-4.5" /> Issue a book</DialogTitle>
          <DialogDescription>Search the learner, pick a book with copies on shelf, set the loan period.</DialogDescription>
        </DialogHeader>
        <div className="grid gap-3">
          <div className="space-y-1.5">
            <Label>Learner *</Label>
            {student ? (
              <div className="flex items-center justify-between rounded-md border bg-secondary/40 px-3 py-2">
                <div className="text-sm"><b>{student.name}</b> <span className="text-xs text-muted-foreground">· {student.classroom}</span></div>
                <Button variant="ghost" size="sm" className="h-6 text-xs" onClick={() => setStudent(null)}>change</Button>
              </div>
            ) : (
              <>
                <div className="relative">
                  <Search className="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                  <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search by name or Nyansapo index…" className="pl-8" autoFocus />
                </div>
                {!!students.length && (
                  <div className="space-y-1">
                    {students.map((s) => (
                      <button key={s.id} className="w-full flex items-center justify-between rounded-md border bg-card px-2.5 py-1.5 text-left hover:bg-secondary/60 text-sm"
                        onClick={() => { setStudent(s); setSearch(""); }}>
                        <span>{s.name} <span className="text-xs text-muted-foreground">· {s.classroom}</span></span>
                        <span className="text-[10px] font-mono text-muted-foreground">{s.nyansapoIndex}</span>
                      </button>
                    ))}
                  </div>
                )}
              </>
            )}
          </div>
          <div className="space-y-1.5">
            <Label>Book *</Label>
            <Select value={bookId} onValueChange={setBookId}>
              <SelectTrigger><SelectValue placeholder="Choose a book…" /></SelectTrigger>
              <SelectContent>
                {books.map((b) => (
                  <SelectItem key={b.id} value={b.id}>{b.title} — {b.available} on shelf</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Loan period (days)</Label>
            <Input type="number" min={1} max={90} value={days} onChange={(e) => setDays(e.target.value)} />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button disabled={busy || !student || !bookId} onClick={async () => { setBusy(true); await onIssue({ studentId: student!.id, bookId, days }); setBusy(false); }}>
            Issue book
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

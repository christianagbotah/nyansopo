import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { sendSms } from "@/lib/sms";
import { nextInvoiceNo } from "@/lib/codes";
import { audit } from "@/lib/audit";

// ── wave 74: fine policy lives in OpsSetting so each school tunes its own rate ──
const FINE_KEYS = ["LIB_FINE_PER_DAY_CENTS", "LIB_FINE_GRACE_DAYS", "LIB_FINE_CAP_CENTS"] as const;
const FINE_DEFAULTS = { LIB_FINE_PER_DAY_CENTS: 50, LIB_FINE_GRACE_DAYS: 0, LIB_FINE_CAP_CENTS: 2000 } as const;

type FinePolicy = { perDayCents: number; graceDays: number; capCents: number };

async function loadFinePolicy(schoolId: string): Promise<FinePolicy> {
  const rows = await db.opsSetting.findMany({ where: { schoolId, key: { in: [...FINE_KEYS] } } });
  const get = (key: string, dflt: number) => {
    const row = rows.find((r) => r.key === key);
    const n = Number(row?.value);
    return Number.isFinite(n) && n >= 0 ? n : dflt;
  };
  return { perDayCents: get("LIB_FINE_PER_DAY_CENTS", FINE_DEFAULTS.LIB_FINE_PER_DAY_CENTS), graceDays: get("LIB_FINE_GRACE_DAYS", FINE_DEFAULTS.LIB_FINE_GRACE_DAYS), capCents: get("LIB_FINE_CAP_CENTS", FINE_DEFAULTS.LIB_FINE_CAP_CENTS) };
}

/** Fine for a loan at `now`: (days overdue − grace) × rate, floored at 0, capped. */
function computeFineCents(dueAt: Date, now: Date, p: FinePolicy, returnedAt?: Date | null): number {
  const end = returnedAt ?? now;
  const daysOver = Math.ceil((end.getTime() - dueAt.getTime()) / 864e5);
  if (daysOver <= p.graceDays) return 0;
  return Math.min((daysOver - p.graceDays) * p.perDayCents, p.capCents);
}

// ── GET /api/school/[id]/ops/library — catalogue, circulation, stats, fines ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["library.view"] });
  if (!auth.ok) return auth.res;

  const books = await db.libraryBook.findMany({
    where: { schoolId },
    include: { loans: { where: { returnedAt: null }, include: { student: true } } },
    orderBy: { title: "asc" },
  });
  const loans = await db.libraryLoan.findMany({
    where: { schoolId },
    include: { book: true, student: { include: { enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: true } } } } },
    orderBy: { issuedAt: "desc" },
    take: 200,
  });
  const finePolicy = await loadFinePolicy(schoolId);

  // ── book requests (iEduPro parity): pending queue first (oldest first), then recent history ──
  const requestRows = await db.bookRequest.findMany({
    where: { schoolId },
    include: { book: true, student: { include: { enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: true } } } } },
    orderBy: { createdAt: "desc" },
    take: 250,
  });
  const pendingReqs = requestRows
    .filter((r) => r.status === "PENDING")
    .sort((a, b) => a.createdAt.getTime() - b.createdAt.getTime());
  const handledReqs = requestRows.filter((r) => r.status !== "PENDING").slice(0, 50);
  const mapRequest = (r: (typeof requestRows)[number]) => ({
    id: r.id, bookId: r.bookId, book: r.book.title, studentId: r.studentId,
    student: `${r.student.firstName} ${r.student.lastName}`,
    classroom: r.student.enrollments[0]?.classroom?.name ?? null,
    startAt: r.startAt, endAt: r.endAt, status: r.status,
    handledAt: r.handledAt, handledByName: r.handledByName, loanId: r.loanId,
    createdAt: r.createdAt,
    overdue: r.status === "ISSUED" && r.endAt < now, // requested window passed while the book is still out
  });

  const now = new Date();
  const activeLoans = loans.filter((l) => !l.returnedAt);
  const overdue = activeLoans.filter((l) => l.dueAt < now);
  const members = new Set(activeLoans.map((l) => l.studentId));

  return NextResponse.json({
    stats: {
      titles: books.length,
      copies: books.reduce((s, b) => s + b.copies, 0),
      available: books.reduce((s, b) => s + b.available, 0),
      activeLoans: activeLoans.length,
      overdue: overdue.length,
      members: members.size,
    },
    books: books.map((b) => ({
      id: b.id, title: b.title, author: b.author, isbn: b.isbn, category: b.category,
      copies: b.copies, available: b.available, shelf: b.shelf,
      borrowers: b.loans.map((l) => `${l.student.firstName} ${l.student.lastName}`),
    })),
    loans: loans.map((l) => ({
      id: l.id, bookId: l.bookId, book: l.book.title, studentId: l.studentId,
      student: `${l.student.firstName} ${l.student.lastName}`,
      classroom: l.student.enrollments[0]?.classroom?.name ?? null,
      issuedAt: l.issuedAt, dueAt: l.dueAt, returnedAt: l.returnedAt,
      issuedByName: l.issuedByName, conditionNote: l.conditionNote,
      overdue: !l.returnedAt && l.dueAt < now,
      daysOverdue: !l.returnedAt && l.dueAt < now ? Math.ceil((now.getTime() - l.dueAt.getTime()) / 864e5) : 0,
      // ── wave 74: fine trail (computed live while still out, recorded at return) ──
      fineCents: l.fineCents,
      finePostedAt: l.finePostedAt,
      computedFineCents: l.fineCents || computeFineCents(l.dueAt, now, finePolicy, l.returnedAt),
      overdueSmsAt: l.overdueSmsAt,
      overdueSmsCount: l.overdueSmsCount,
    })),
    finePolicy,
    fines: {
      recorded: loans.filter((l) => l.fineCents > 0 && !l.finePostedAt).length,
      recordedTotalCents: loans.filter((l) => l.fineCents > 0 && !l.finePostedAt).reduce((s, l) => s + l.fineCents, 0),
      posted: loans.filter((l) => l.finePostedAt).length,
      postedTotalCents: loans.filter((l) => l.finePostedAt).reduce((s, l) => s + l.fineCents, 0),
    },
    // ── book requests: pending queue first, then recent history + pending count ──
    requests: [...pendingReqs, ...handledReqs].map(mapRequest),
    pendingRequests: pendingReqs.length,
  });
}

// ── POST /api/school/[id]/ops/library — book CRUD, issue & return, requests ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  // circulation (issue/return + requests + overdue chase & fines) is librarian work; catalogue edits need library.manage
  const circulation = action === "loan.issue" || action === "loan.return" || action === "request.accept" || action === "request.reject"
    || action === "overdue.scan" || action === "fine.post" || action === "fine.waive";
  const auth = await opsAuth(req, schoolId, "POST", {
    perms: circulation ? ["library.issue", "library.manage"] : ["library.manage"],
  });
  if (!auth.ok) return auth.res;
  const user = auth.user;

  if (action === "book.save") {
    const { id, title, author, isbn, category, copies, shelf } = body;
    if (!title) return NextResponse.json({ error: "Title is required" }, { status: 400 });
    const n = Math.max(1, Number(copies) || 1);
    if (id) {
      const existing = await db.libraryBook.findUnique({ where: { id }, include: { loans: { where: { returnedAt: null } } } });
      if (!existing || existing.schoolId !== schoolId) return NextResponse.json({ error: "Book not found" }, { status: 404 });
      const onLoan = existing.loans.length;
      if (n < onLoan) return NextResponse.json({ error: `${onLoan} copies are on loan — copies cannot be fewer than that` }, { status: 400 });
      const book = await db.libraryBook.update({ where: { id }, data: { title, author: author ?? null, isbn: isbn ?? null, category: category ?? "General", copies: n, available: n - onLoan, shelf: shelf ?? null } });
      return NextResponse.json({ book });
    }
    const book = await db.libraryBook.create({ data: { schoolId, title, author: author ?? null, isbn: isbn ?? null, category: category ?? "General", copies: n, available: n, shelf: shelf ?? null } });
    return NextResponse.json({ book });
  }

  if (action === "book.delete") {
    const b = await db.libraryBook.findUnique({ where: { id: body.id }, include: { loans: { where: { returnedAt: null } } } });
    if (!b || b.schoolId !== schoolId) return NextResponse.json({ error: "Book not found" }, { status: 404 });
    if (b.loans.length) return NextResponse.json({ error: "Cannot delete — copies are still on loan" }, { status: 400 });
    await db.libraryBook.delete({ where: { id: b.id } });
    return NextResponse.json({ ok: true });
  }

  if (action === "loan.issue") {
    const { bookId, studentId, days, conditionNote } = body;
    if (!bookId || !studentId) return NextResponse.json({ error: "Book and learner are required" }, { status: 400 });
    const book = await db.libraryBook.findUnique({ where: { id: bookId } });
    if (!book || book.schoolId !== schoolId) return NextResponse.json({ error: "Book not found" }, { status: 404 });
    if (book.available < 1) return NextResponse.json({ error: "No copies available" }, { status: 400 });
    const student = await db.student.findUnique({ where: { id: studentId } });
    if (!student) return NextResponse.json({ error: "Learner not found" }, { status: 404 });
    const dueAt = new Date(Date.now() + (Number(days) || 14) * 864e5);
    const loan = await db.libraryLoan.create({ data: { bookId, studentId, schoolId, dueAt, issuedByName: user.fullName, conditionNote: conditionNote ?? null } });
    await db.libraryBook.update({ where: { id: bookId }, data: { available: { decrement: 1 } } });
    return NextResponse.json({ loan });
  }

  if (action === "loan.return") {
    const { loanId, conditionNote } = body;
    const loan = await db.libraryLoan.findUnique({ where: { id: loanId }, include: { book: true } });
    if (!loan || loan.schoolId !== schoolId) return NextResponse.json({ error: "Loan not found" }, { status: 404 });
    if (loan.returnedAt) return NextResponse.json({ error: "Already returned" }, { status: 400 });
    const now = new Date();
    const policy = await loadFinePolicy(schoolId);
    const fineCents = loan.fineCents || computeFineCents(loan.dueAt, now, policy); // recorded once — not recomputed on repeat
    const updated = await db.libraryLoan.update({ where: { id: loanId }, data: { returnedAt: now, conditionNote: conditionNote ?? loan.conditionNote, fineCents } });
    await db.libraryBook.update({ where: { id: loan.bookId }, data: { available: { increment: 1 } } });
    return NextResponse.json({ loan: updated, fineCents, finePolicy: policy });
  }

  // ── wave 74: overdue chase — guardian SMS reminders, throttled to one per loan every 3 days ──
  if (action === "overdue.scan") {
    const now = new Date();
    const policy = await loadFinePolicy(schoolId);
    const stale = await db.libraryLoan.findMany({
      where: { schoolId, returnedAt: null, dueAt: { lt: now } },
      include: { book: { select: { title: true } }, student: { select: { firstName: true, lastName: true, guardianName: true, guardianPhone: true } } },
      orderBy: { dueAt: "asc" },
    });
    const THROTTLE_MS = 3 * 864e5;
    const due = stale.filter((l) => !l.overdueSmsAt || now.getTime() - l.overdueSmsAt.getTime() >= THROTTLE_MS);
    let sent = 0, failed = 0;
    for (const l of due) {
      const studentName = `${l.student.firstName} ${l.student.lastName}`;
      const days = Math.ceil((now.getTime() - l.dueAt.getTime()) / 864e5);
      const fine = computeFineCents(l.dueAt, now, policy);
      const fineNote = policy.perDayCents > 0 ? ` A fine of GHS ${(policy.perDayCents / 100).toFixed(2)}/day is accruing${policy.capCents ? ` (max GHS ${(policy.capCents / 100).toFixed(2)})` : ""} — currently GHS ${(fine / 100).toFixed(2)}.` : "";
      const r = await sendSms({
        db, schoolId, studentId: l.studentId,
        recipientName: l.student.guardianName || studentName,
        recipientPhone: l.student.guardianPhone,
        body: `Library reminder: ${studentName}'s book "${l.book.title}" is ${days} day${days === 1 ? "" : "s"} overdue.${fineNote} Kindly return it to the school library. Thank you.`,
        category: "LIBRARY_OVERDUE",
        sentByName: user.fullName,
      });
      if (r.status === "SENT") sent++; else failed++;
      await db.libraryLoan.update({ where: { id: l.id }, data: { overdueSmsAt: now, overdueSmsCount: { increment: 1 } } });
    }
    await audit({ schoolId, actor: user, action: "CREATE", entityType: "LibraryLoan", entityId: "overdue-scan", req, summary: `Overdue scan: ${due.length} stale loan(s) chased, ${sent} SMS sent, ${failed} failed` });
    return NextResponse.json({ checked: stale.length, chased: due.length, sent, failed, skipped: stale.length - due.length });
  }

  // ── wave 74: fines ride the receipt engine — append an invoice item on the learner's current-term invoice ──
  if (action === "fine.post") {
    const loanId: string | undefined = body.loanId;
    const policy = await loadFinePolicy(schoolId);
    const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
    if (!term) return NextResponse.json({ error: "No current billing term found — set one under Sessions & Terms first" }, { status: 400 });

    const targets = loanId
      ? await db.libraryLoan.findMany({ where: { schoolId, id: loanId, fineCents: { gt: 0 }, finePostedAt: null }, include: { book: true, student: true } })
      : await db.libraryLoan.findMany({ where: { schoolId, fineCents: { gt: 0 }, finePostedAt: null }, include: { book: true, student: true } });
    if (!targets.length) return NextResponse.json({ error: "No unposted fines to bill" }, { status: 400 });

    const enrollmentRows = await db.enrollment.findMany({
      where: { schoolId, studentId: { in: [...new Set(targets.map((t) => t.studentId))], }, status: "ACTIVE" },
      include: { classroom: { select: { name: true } } },
    });
    const classByStudent = new Map(enrollmentRows.map((e) => [e.studentId, e.classroom.name]));

    let posted = 0, postedTotal = 0;
    for (const t of targets) {
      const title = `Library fine — ${t.book.title}`.toUpperCase();
      const existing = await db.invoice.findFirst({ where: { schoolId, termId: term.id, studentId: t.studentId }, include: { items: true } });
      if (existing) {
        if (existing.items.some((i) => i.name === title)) {
          // already billed this book on this invoice — just mark the loan so it stops showing as unposted
          await db.libraryLoan.update({ where: { id: t.id }, data: { finePostedAt: new Date() } });
          continue;
        }
        await db.invoiceItem.create({ data: { invoiceId: existing.id, name: title, amountCents: t.fineCents } });
        const total = existing.totalCents + t.fineCents;
        await db.invoice.update({ where: { id: existing.id }, data: { totalCents: total, status: existing.paidCents >= total - existing.discountCents ? existing.status : existing.status === "PAID" ? "PARTIAL" : existing.status } });
      } else {
        const inv = await db.invoice.create({
          data: { invoiceNo: await nextInvoiceNo(), studentId: t.studentId, schoolId, termId: term.id, classroomName: classByStudent.get(t.studentId) ?? "—", totalCents: t.fineCents, status: "UNPAID", dueDate: new Date(Date.now() + 14 * 864e5) },
        });
        await db.invoiceItem.create({ data: { invoiceId: inv.id, name: title, amountCents: t.fineCents } });
      }
      await db.libraryLoan.update({ where: { id: t.id }, data: { finePostedAt: new Date() } });
      await audit({ schoolId, actor: user, action: "CREATE", entityType: "Invoice", entityId: t.id, req, summary: `Library fine GHS ${(t.fineCents / 100).toFixed(2)} for ${t.student.firstName} ${t.student.lastName} ("${t.book.title}") posted to the ${term.name} invoice` });
      posted++;
      postedTotal += t.fineCents;
    }
    return NextResponse.json({ posted, postedTotalCents: postedTotal, finePolicy: policy });
  }

  // ── wave 74: waive — head's mercy; only before the fine has been billed ──
  if (action === "fine.waive") {
    const loan = await db.libraryLoan.findUnique({ where: { id: body.loanId } });
    if (!loan || loan.schoolId !== schoolId) return NextResponse.json({ error: "Loan not found" }, { status: 404 });
    if (loan.finePostedAt) return NextResponse.json({ error: "This fine is already on an invoice — credit it through the cashier instead" }, { status: 400 });
    if (loan.fineCents === 0) return NextResponse.json({ error: "No fine recorded on this loan" }, { status: 400 });
    const updated = await db.libraryLoan.update({ where: { id: loan.id }, data: { fineCents: 0 } });
    await audit({ schoolId, actor: user, action: "UPDATE", entityType: "LibraryLoan", entityId: loan.id, req, summary: `Waived library fine of GHS ${(loan.fineCents / 100).toFixed(2)}` });
    return NextResponse.json({ loan: updated });
  }

  // ── wave 74: fine policy editor (library.manage) ──
  if (action === "policy.save") {
    const perDay = Math.max(0, Math.round(Number(body.perDayCents)));
    const grace = Math.max(0, Math.round(Number(body.graceDays)));
    const cap = Math.max(0, Math.round(Number(body.capCents)));
    if (![perDay, grace, cap].every(Number.isFinite)) return NextResponse.json({ error: "Invalid policy values" }, { status: 400 });
    const updates: Array<[string, string]> = [["LIB_FINE_PER_DAY_CENTS", String(perDay)], ["LIB_FINE_GRACE_DAYS", String(grace)], ["LIB_FINE_CAP_CENTS", String(cap)]];
    for (const [key, value] of updates) {
      await db.opsSetting.upsert({ where: { schoolId_key: { schoolId, key } }, update: { value }, create: { schoolId, key, value } });
    }
    await audit({ schoolId, actor: user, action: "UPDATE", entityType: "OpsSetting", entityId: "library-fine-policy", req, summary: `Fine policy updated: GHS ${(perDay / 100).toFixed(2)}/day, ${grace} grace day(s), cap GHS ${(cap / 100).toFixed(2)}` });
    return NextResponse.json({ finePolicy: { perDayCents: perDay, graceDays: grace, capCents: cap } });
  }

  // ── book requests (iEduPro parity): accept issues the book on the learner's
  //    requested dates; reject closes the request. Both are librarian actions. ──
  if (action === "request.accept") {
    const request = await db.bookRequest.findUnique({ where: { id: body.id }, include: { book: true } });
    if (!request || request.schoolId !== schoolId) return NextResponse.json({ error: "Request not found" }, { status: 404 });
    if (request.status !== "PENDING") return NextResponse.json({ error: "This request has already been handled" }, { status: 400 });
    if (request.book.available < 1) return NextResponse.json({ error: "No copy of this book is available" }, { status: 409 });
    const now = new Date();
    const requestedStart = new Date(request.startAt);
    try {
      const loan = await db.$transaction(async (tx) => {
        // re-check inside the transaction — another loan may have taken the last copy
        const book = await tx.libraryBook.findUnique({ where: { id: request.bookId } });
        if (!book || book.available < 1) throw new Error("NO_COPIES");
        const loan = await tx.libraryLoan.create({
          data: {
            bookId: request.bookId, studentId: request.studentId, schoolId,
            issuedAt: requestedStart > now ? requestedStart : now, // future-dated request issues now
            dueAt: new Date(request.endAt),
            issuedByName: user.fullName,
          },
        });
        await tx.libraryBook.update({ where: { id: request.bookId }, data: { available: { decrement: 1 } } });
        await tx.bookRequest.update({
          where: { id: request.id },
          data: { status: "ISSUED", handledAt: now, handledByName: user.fullName, loanId: loan.id },
        });
        return loan;
      });
      return NextResponse.json({ loan, request: { id: request.id, status: "ISSUED" } });
    } catch (e: any) {
      if (e?.message === "NO_COPIES") return NextResponse.json({ error: "No copy of this book is available" }, { status: 409 });
      throw e;
    }
  }

  if (action === "request.reject") {
    const request = await db.bookRequest.findUnique({ where: { id: body.id } });
    if (!request || request.schoolId !== schoolId) return NextResponse.json({ error: "Request not found" }, { status: 404 });
    if (request.status !== "PENDING") return NextResponse.json({ error: "This request has already been handled" }, { status: 400 });
    const updated = await db.bookRequest.update({
      where: { id: request.id },
      data: { status: "REJECTED", handledAt: new Date(), handledByName: user.fullName },
    });
    return NextResponse.json({ request: updated });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

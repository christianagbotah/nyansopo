import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

// ── GET /api/school/[id]/ops/library — catalogue, circulation, stats ──
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
    })),
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
  // circulation (issue/return + request handling) is librarian work; catalogue edits need library.manage
  const auth = await opsAuth(req, schoolId, "POST", {
    perms: action === "loan.issue" || action === "loan.return" || action === "request.accept" || action === "request.reject"
      ? ["library.issue", "library.manage"]
      : ["library.manage"],
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
    const updated = await db.libraryLoan.update({ where: { id: loanId }, data: { returnedAt: new Date(), conditionNote: conditionNote ?? loan.conditionNote } });
    await db.libraryBook.update({ where: { id: loan.bookId }, data: { available: { increment: 1 } } });
    return NextResponse.json({ loan: updated });
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

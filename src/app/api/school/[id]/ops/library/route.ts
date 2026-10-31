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
  });
}

// ── POST /api/school/[id]/ops/library — book CRUD, issue & return ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  // circulation (issue/return) is librarian work; catalogue edits need library.manage
  const auth = await opsAuth(req, schoolId, "POST", {
    perms: action === "loan.issue" || action === "loan.return" ? ["library.issue", "library.manage"] : ["library.manage"],
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

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

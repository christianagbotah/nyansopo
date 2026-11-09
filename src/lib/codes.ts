// ─────────────────────────────────────────────────────────────
// SYNC-SAFE CODE GENERATION
//
// Why: naive schemes ("SELECT max(...) + 1" or "count() + 1001") race:
// two clerks invoicing at the same moment — or a device coming back
// online after working offline — read the same "next" number and mint
// duplicate codes. With @unique constraints that is a hard failure; the
// offline device would re-mint numbers that already exist online
// (exactly the INV-2026-006 → INV-2026-001 collision problem).
//
// Design:
//   • Primary keys stay cuid() — globally unique, no coordination needed.
//   • Human-readable codes are minted SERVER-SIDE from a CodeCounter row
//     (one row per code kind + year). The increment is a single atomic
//     SQL UPDATE, so concurrent minters can never receive the same number.
//   • The first allocation for a scope SEEDS the counter from the highest
//     existing code of that kind, so legacy/seed data keeps its numbers
//     and the sequence simply continues.
//   • Codes created while offline: the client shows a provisional
//     OFFLINE-xx label; the permanent pretty code is minted by the server
//     when the queued op flushes (see /api/sync), keyed idempotently by
//     the op's clientId — so a re-flush never duplicates a receipt.
//   • Numbers restart at 0001 each calendar year (new scope per year).
//
// Full design + verification: docs/sync-safe-code-generation.md
// (23-check proof suite: scripts/smoke-codes.ts)
// ─────────────────────────────────────────────────────────────
import { db } from "@/lib/db";
import { Prisma } from "@prisma/client";

const currentYear = () => new Date().getFullYear();

async function seedValue(kind: "INV" | "RCP" | "NYIDX", year: number): Promise<number> {
  if (kind === "INV") {
    const last = await db.invoice.findFirst({
      where: { invoiceNo: { startsWith: `INV-${year}-` } },
      orderBy: { invoiceNo: "desc" },
      select: { invoiceNo: true },
    });
    return last ? parseInt(last.invoiceNo.slice(`INV-${year}-`.length), 10) : 0;
  }
  if (kind === "RCP") {
    const last = await db.payment.findFirst({
      where: { receiptNo: { startsWith: `RCP-${year}-` } },
      orderBy: { receiptNo: "desc" },
      select: { receiptNo: true },
    });
    return last ? parseInt(last.receiptNo.slice(`RCP-${year}-`.length), 10) : 0;
  }
  const last = await db.student.findFirst({
    where: { nyansapoIndex: { startsWith: `NY-${year}-` } },
    orderBy: { nyansapoIndex: "desc" },
    select: { nyansapoIndex: true },
  });
  return last ? parseInt(last.nyansapoIndex.slice(`NY-${year}-`.length), 10) : 0;
}

/** Atomically allocate the next sequence number for `kind` in `year`.
 *  Seeded from existing data on first use; monotonic from then on. */
export async function nextSeq(kind: "INV" | "RCP" | "NYIDX", year = currentYear()): Promise<number> {
  const scope = `${kind}:${year}`;
  const seed = await seedValue(kind, year);
  // First allocation: create the counter at the legacy maximum. If we win
  // the create, `seed + 1` is ours and the row remembers it as last-used.
  try {
    await db.codeCounter.create({ data: { scope, next: seed + 1 } });
    return seed + 1;
  } catch (e) {
    if (!(e instanceof Prisma.PrismaClientKnownRequestError && e.code === "P2002")) throw e;
  }
  // Counter exists: atomic increment. The returned row value IS the number
  // we just allocated (counter stores "last allocated").
  const row = await db.codeCounter.update({
    where: { scope },
    data: { next: { increment: 1 } },
  });
  return row.next;
}

/** INV-YYYY-NNNN — invoice number (global per calendar year). */
export async function nextInvoiceNo(): Promise<string> {
  const year = currentYear();
  const n = await nextSeq("INV", year);
  return `INV-${year}-${String(n).padStart(4, "0")}`;
}

/** RCP-YYYY-NNNNN — receipt number (global per calendar year).
 *  Use for EVERY payment receipt: invoice payments, daily services,
 *  POS sales, transport fares, and offline-flushed collections. */
export async function nextReceiptNo(): Promise<string> {
  const year = currentYear();
  const n = await nextSeq("RCP", year);
  return `RCP-${year}-${String(n).padStart(5, "0")}`;
}

/** NY-YYYY-NNNNNN — national student registry index.
 *  `year` is the student's birth year (registry semantics). */
export async function nextStudentIndex(birthYear?: number): Promise<string> {
  const year = birthYear ?? currentYear();
  const n = await nextSeq("NYIDX", year);
  return `NY-${year}-${String(n).padStart(6, "0")}`;
}

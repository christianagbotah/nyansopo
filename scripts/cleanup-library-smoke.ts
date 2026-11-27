/* eslint-disable @typescript-eslint/no-require-imports */
// ── wave 74 cleanup: remove the smoke book, its loans and any invoice the
//    fine.post test created (invoice items carry the smoke book title). ──
const { PrismaClient } = require("@prisma/client");
const db = new PrismaClient();

(async () => {
  const tag = process.argv[2];
  if (!tag) { console.error("usage: cleanup-library-smoke.ts <tag>"); process.exit(1); }

  const books = await db.libraryBook.findMany({ where: { title: { contains: tag } }, include: { loans: true } });
  const loanIds = books.flatMap((b) => b.loans.map((l) => l.id));

  // invoices whose items name the smoke fine lines
  const itemNames = books.map((b) => `LIBRARY FINE — ${b.title}`.toUpperCase());
  const fineItems = await db.invoiceItem.findMany({ where: { name: { in: itemNames } }, include: { invoice: { include: { items: true } } } });

  for (const item of fineItems) {
    const inv = item.invoice;
    const remaining = inv.items.filter((i) => i.id !== item.id);
    if (remaining.length === 0) {
      await db.invoice.delete({ where: { id: inv.id } }); // invoice held only the smoke fine
    } else {
      await db.invoiceItem.delete({ where: { id: item.id } });
      const total = inv.totalCents - item.amountCents;
      await db.invoice.update({ where: { id: inv.id }, data: { totalCents: total } });
    }
  }

  if (loanIds.length) await db.libraryLoan.deleteMany({ where: { id: { in: loanIds } } });
  if (books.length) await db.libraryBook.deleteMany({ where: { id: { in: books.map((b) => b.id) } } });

  console.log(`cleaned: ${books.length} book(s), ${loanIds.length} loan(s), ${fineItems.length} fine item(s)`);
  process.exit(0);
})().catch((e) => { console.error(e.message); process.exit(1); });

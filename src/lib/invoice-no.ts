import { db } from "@/lib/db";

/** Next global invoice number for the current year: INV-<year>-<NNNN>.
 *  InvoiceNo is GLOBALLY unique and legacy/seed data numbers invoices across
 *  ALL schools in one running sequence, so the next number must be derived
 *  from the global max for the year — never from a per-school count.
 *  Numbers restart at 0001 each calendar year. */
export async function nextInvoiceNo(): Promise<string> {
  const year = new Date().getFullYear();
  const prefix = `INV-${year}-`;
  const last = await db.invoice.findFirst({
    where: { invoiceNo: { startsWith: prefix } },
    orderBy: { invoiceNo: "desc" },
    select: { invoiceNo: true },
  });
  const seq = last ? Number(last.invoiceNo.slice(prefix.length)) + 1 : 1;
  return `${prefix}${String(seq).padStart(4, "0")}`;
}

/* Server-side receipt payload builder — shared by fees, daily, POS & transport APIs.
   Returns the exact shape the <ReceiptDocument /> component renders. */
import { PrismaClient } from "@prisma/client";

export type ReceiptPayload = {
  type: "INVOICE_PAYMENT" | "DAILY_SERVICES" | "POS_SALE" | "TRANSPORT_FARE";
  receiptNo: string;
  date: string;
  method: string;
  reference?: string | null;
  cashier?: string | null;
  student?: { name: string; index?: string | null; classroom?: string | null; guardian?: string | null };
  buyer?: string | null;
  invoice?: { no: string; term: string; items: { name: string; amount: number }[]; total: number; discount: number; paid: number; balance: number };
  services?: { name: string; count: number; amount: number }[];
  lines?: { item: string; qty: number; amount: number }[];
  transport?: { route: string; vehicle?: string | null; term: string; fare: number };
  amount: number;
  school: { name: string; motto?: string | null; town?: string | null; region?: string | null; phone?: string | null; email?: string | null };
  // print styling / watermarks
  style?: "A5" | "THERMAL";
  multiInvoice?: boolean;
  arrears?: boolean;
  creditCreated?: number;
};

export async function receiptSchool(db: PrismaClient, schoolId: string) {
  const s = await db.school.findUnique({ where: { id: schoolId } });
  if (!s) throw new Error("School not found");
  return {
    name: s.name,
    motto: s.motto ?? null,
    town: s.town ?? null,
    region: s.region ?? null,
    phone: s.phone ?? null,
    email: s.email ?? null,
  };
}

/** Invoice payment receipt (term billing). */
export async function invoicePaymentReceipt(db: PrismaClient, paymentId: string) {
  const p = await db.payment.findUnique({
    where: { id: paymentId },
    include: {
      student: true,
      invoice: { include: { items: true, student: true, term: { include: { academicYear: true } } } },
    },
  });
  if (!p) throw new Error("Payment not found");
  // legacy payments may lack a direct studentId — fall back to the invoice's learner
  const st = p.student ?? p.invoice?.student ?? null;
  if (!st) throw new Error("Payment has no linked learner");
  const school = await receiptSchool(db, p.schoolId);
  const inv = p.invoice;
  const net = inv ? inv.totalCents - inv.discountCents : p.amountCents;
  const balance = inv ? Math.max(0, net - inv.paidCents) : 0;
  // arrears watermark: paying a bill that is not from the current term
  const arrears = inv ? !inv.term.isCurrent : false;
  // overpayment credit created by THIS receipt (re-derived so reprints show it too)
  const creditRow = await db.studentCredit.findFirst({
    where: { sourcePaymentId: p.id, kind: "CREDIT" },
    select: { amountCents: true },
  });
  const creditCreated = creditRow ? creditRow.amountCents : undefined;
  return {
    type: "INVOICE_PAYMENT" as const,
    receiptNo: p.receiptNo,
    date: p.paidAt.toISOString(),
    method: p.method,
    reference: p.reference,
    cashier: p.receivedByName,
    student: {
      name: `${st.firstName} ${st.lastName}`,
      index: st.nyansapoIndex,
      classroom: inv?.classroomName ?? null,
      guardian: st.guardianName,
    },
    invoice: inv
      ? {
          no: inv.invoiceNo,
          term: `${inv.term.name} · ${inv.term.academicYear.name}`,
          items: inv.items.map((i) => ({ name: i.name, amount: i.amountCents })),
          total: inv.totalCents,
          discount: inv.discountCents,
          paid: inv.paidCents,
          balance,
        }
      : undefined,
    amount: p.amountCents,
    creditCreated,
    school,
    arrears,
  } satisfies ReceiptPayload;
}

/** Daily services counter receipt. paymentId must be category DAILY. */
export async function dailyServicesReceipt(db: PrismaClient, paymentId: string) {
  const p = await db.payment.findUnique({ where: { id: paymentId }, include: { student: true, dailyCharges: { include: { service: true } } } });
  if (!p) throw new Error("Payment not found");
  if (!p.student) throw new Error("Payment has no linked learner");
  const school = await receiptSchool(db, p.schoolId);
  // group settled charges by service
  const byName: Record<string, { count: number; amount: number }> = {};
  for (const c of p.dailyCharges) {
    byName[c.service.name] ??= { count: 0, amount: 0 };
    byName[c.service.name].count += 1;
    byName[c.service.name].amount += c.amountCents;
  }
  return {
    type: "DAILY_SERVICES" as const,
    receiptNo: p.receiptNo,
    date: p.paidAt.toISOString(),
    method: p.method,
    reference: p.reference,
    cashier: p.receivedByName,
    student: {
      name: `${p.student.firstName} ${p.student.lastName}`,
      index: p.student.nyansapoIndex,
      guardian: p.student.guardianName,
    },
    services: Object.entries(byName).map(([name, v]) => ({ name, count: v.count, amount: v.amount })),
    amount: p.amountCents,
    school,
  } satisfies ReceiptPayload;
}

/** Store POS sale receipt. */
export function posSaleReceipt(
  school: ReceiptPayload["school"],
  sale: { saleNo: string; buyer: string; method: string; total: number; lines: { item: string; qty: number; amount: number }[]; at?: string },
): ReceiptPayload {
  return {
    type: "POS_SALE",
    receiptNo: sale.saleNo,
    date: sale.at ?? new Date().toISOString(),
    method: sale.method,
    buyer: sale.buyer,
    lines: sale.lines,
    amount: sale.total,
    school,
  };
}

/** Transport fare receipt. */
export async function transportFareReceipt(
  db: PrismaClient,
  paymentId: string,
  route: { name: string; vehicleReg: string | null },
  termLabel: string,
  fareCents: number,
) {
  const p = await db.payment.findUnique({ where: { id: paymentId }, include: { student: true } });
  if (!p) throw new Error("Payment not found");
  if (!p.student) throw new Error("Payment has no linked learner");
  const school = await receiptSchool(db, p.schoolId);
  return {
    type: "TRANSPORT_FARE" as const,
    receiptNo: p.receiptNo,
    date: p.paidAt.toISOString(),
    method: p.method,
    reference: p.reference,
    cashier: p.receivedByName,
    student: { name: `${p.student.firstName} ${p.student.lastName}`, index: p.student.nyansapoIndex, guardian: p.student.guardianName },
    transport: { route: route.name, vehicle: route.vehicleReg, term: termLabel, fare: fareCents },
    amount: p.amountCents,
    school,
  } satisfies ReceiptPayload;
}

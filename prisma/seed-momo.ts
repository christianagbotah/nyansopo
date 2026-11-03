// Seed demo momo payment requests (SIMULATED provider) so the MoMo & Payment
// Links module has realistic data: a completed invoice payment (ledger-applied),
// a completed per-item daily payment, a pending payment link, a pending momo
// prompt and a failed request. Idempotent per scenario — safe to re-run.
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

async function main() {
  // target the demo head's school (head@nyansapo.gh), falling back to the
  // school with the most learners so the module always has rich demo data
  const head = await db.user.findFirst({ where: { email: "head@nyansapo.gh" }, select: { schoolId: true } });
  const school = head?.schoolId
    ? await db.school.findUnique({ where: { id: head.schoolId } })
    : (await db.school.findFirst({ include: { _count: { select: { enrollments: true } } }, orderBy: { enrollments: { _count: "desc" } } }));
  if (!school) throw new Error("No school found");
  const schoolId = school.id;

  // lazy-import the engine (it reads HUBTEL_* env at call time)
  const { createPaymentRequest, applyCompletion, failRequest } = await import("../src/lib/payment-requests");

  const enrollments = await db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, include: { student: true }, orderBy: { rollNo: "asc" }, take: 80 });
  if (!enrollments.length) throw new Error("No enrollments — run prisma/seed.ts first");

  const has = async (purpose: string, status: string) => !!(await db.paymentRequest.findFirst({ where: { schoolId, purpose, status } }));

  const openInvoice = async (offset: number, minBalance: number) => {
    for (let i = offset; i < enrollments.length; i += 2) {
      const inv = await db.invoice.findFirst({
        where: { studentId: enrollments[i].studentId, schoolId, status: { in: ["UNPAID", "PARTIAL", "OVERDUE"] }, writtenOff: false },
        orderBy: { createdAt: "desc" },
      });
      if (inv && inv.totalCents - inv.discountCents - inv.paidCents >= minBalance) return inv;
    }
    return null;
  };

  // ── 1. completed termly-fee payment (invoice applied, receipt minted) ──
  if (!(await has("INVOICE", "COMPLETED"))) {
    const inv = await openInvoice(0, 500);
    if (inv) {
      const balance = inv.totalCents - inv.discountCents - inv.paidCents;
      const r = await createPaymentRequest(db, {
        schoolId, studentId: inv.studentId, purpose: "INVOICE", invoiceId: inv.id,
        amountCents: balance, method: "PAYMENT_LINK", createdByName: "Madam Akosua Dede (Bursar)",
      });
      if (r.ok) {
        const at = new Date(Date.now() - 2 * 864e5);
        await db.paymentRequest.update({ where: { id: r.request.id }, data: { createdAt: at, completedAt: at } });
        await applyCompletion(db, r.request.id, "simulate");
        console.log(`completed invoice request: ${r.request.clientReference} (${(balance / 100).toFixed(2)})`);
      }
    }
  }

  // ── 2. completed per-item daily payment (FIFO settle + advance) ──
  if (!(await has("DAILY_ITEMS", "COMPLETED"))) {
    const svc = await db.dailyService.findFirst({ where: { schoolId, category: { in: ["MEAL", "DRINK", "CARE"] } } });
    if (svc) {
      for (let i = 1; i < enrollments.length; i++) {
        const e = enrollments[i];
        const owed = await db.dailyCharge.aggregate({ where: { studentId: e.studentId, serviceId: svc.id, status: "OWED" }, _sum: { amountCents: true } });
        const owedCents = owed._sum.amountCents ?? 0;
        if (owedCents < 200) continue;
        const r = await createPaymentRequest(db, {
          schoolId, studentId: e.studentId, purpose: "DAILY_ITEMS",
          items: [{ serviceId: svc.id, amountCents: owedCents }], amountCents: owedCents,
          method: "MOMO_PROMPT", createdByName: "Kwame Boateng (Cashier)",
        });
        if (r.ok) {
          const at = new Date(Date.now() - 1 * 864e5);
          await db.paymentRequest.update({ where: { id: r.request.id }, data: { createdAt: at, completedAt: at } });
          await applyCompletion(db, r.request.id, "simulate");
          console.log(`completed daily-items request: ${r.request.clientReference}`);
          break;
        }
      }
    }
  }

  // ── 3. pending payment link ──
  if (!(await has("INVOICE", "PENDING"))) {
    const inv = await openInvoice(5, 1000);
    if (inv) {
      const balance = Math.min(inv.totalCents - inv.discountCents - inv.paidCents, 15000);
      const r = await createPaymentRequest(db, {
        schoolId, studentId: inv.studentId, purpose: "INVOICE", invoiceId: inv.id,
        amountCents: balance, method: "PAYMENT_LINK", createdByName: "Madam Akosua Dede (Bursar)",
      });
      if (r.ok) console.log(`pending link: ${r.request.clientReference}`);
    }
  }

  // ── 4. pending momo prompt (item advance) ──
  if (!(await has("ITEM_BALANCE", "PENDING"))) {
    const svc = await db.dailyService.findFirst({ where: { schoolId, category: { in: ["MEAL", "DRINK", "CARE"] } } });
    const e = enrollments[9] ?? enrollments[1];
    if (svc && e) {
      const r = await createPaymentRequest(db, {
        schoolId, studentId: e.studentId, purpose: "ITEM_BALANCE",
        items: [{ serviceId: svc.id, amountCents: 2000 }], amountCents: 2000,
        method: "MOMO_PROMPT", createdByName: "Kwame Boateng (Cashier)",
      });
      if (r.ok) console.log(`pending prompt: ${r.request.clientReference}`);
    }
  }

  // ── 5. failed request (declined at the network) ──
  if (!(await has("WALLET", "FAILED"))) {
    const e = enrollments[13] ?? enrollments[2];
    const r = await createPaymentRequest(db, {
      schoolId, studentId: e.studentId, purpose: "WALLET", walletKind: "PREPAID",
      amountCents: 5000, method: "MOMO_PROMPT", createdByName: "Kwame Boateng (Cashier)",
    });
    if (r.ok) {
      await failRequest(db, r.request.id, "Insufficient funds on the momo wallet");
      console.log(`failed request: ${r.request.clientReference}`);
    }
  }

  const count = await db.paymentRequest.count({ where: { schoolId } });
  console.log(`momo payment requests for ${school.name}: ${count}`);
}

main()
  .catch((e) => { console.error(e); process.exit(1); })
  .finally(() => db.$disconnect());

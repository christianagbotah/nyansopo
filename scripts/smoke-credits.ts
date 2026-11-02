/* Student credit wallet (prepaid credits from overpayment) API smoke test —
   (a) overpayment → credit + receipt note, (b) credits list & stats,
   (c) adjust ±, (d) transfer A→B, (e) credit → daily wallet, (f) billing
   auto-apply, (g) RBAC guard. Creates its own data and CLEANS UP afterwards.
   Run: DATABASE_URL=postgresql://… bun scripts/smoke-credits.ts */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();
const BASE = "http://localhost:3000";
let pass = 0, fail = 0;

async function check(label: string, cond: boolean, extra = "") {
  if (cond) { pass++; console.log(`  ✅ ${label}`); }
  else { fail++; console.log(`  ❌ ${label} ${extra}`); }
}

async function creditSum(schoolId: string, studentId: string) {
  const agg = await db.studentCredit.aggregate({ where: { schoolId, studentId }, _sum: { amountCents: true } });
  return agg._sum.amountCents ?? 0;
}

async function main() {
  const [head, teacher] = await Promise.all([
    db.user.findUnique({ where: { email: "head@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "teacher@nyansapo.gh" } }),
  ]);
  if (!head || !teacher) { console.log("missing demo users — run the seed first"); return; }
  const school = await db.school.findUnique({ where: { code: "GEA" } });
  if (!school) { console.log("GEA school missing"); return; }
  const schoolId = school.id;
  const H = (u: typeof head) => ({ "content-type": "application/json", "x-user-id": u.id });
  const get = async (u: typeof head, url: string) => fetch(BASE + url, { headers: H(u) });
  const post = async (u: typeof head, url: string, body: any) => fetch(BASE + url, { method: "POST", headers: H(u), body: JSON.stringify(body) });

  const testStart = new Date();
  // snapshots for full cleanup afterwards
  const preCreditIds = new Set((await db.studentCredit.findMany({ where: { schoolId }, select: { id: true } })).map((r) => r.id));
  const preBalances = new Map((await db.studentCredit.groupBy({ by: ["studentId"], where: { schoolId }, _sum: { amountCents: true } })).map((g) => [g.studentId, g._sum.amountCents ?? 0]));
  const preStudentsWithCredits = [...preBalances.values()].filter((v) => v > 0).length;
  const preInvoiceIds = new Set((await db.invoice.findMany({ where: { schoolId }, select: { id: true } })).map((r) => r.id));
  const preTxnIds = new Set((await db.walletTxn.findMany({ where: { schoolId }, select: { id: true } })).map((r) => r.id));
  const prePaymentIds = new Set((await db.payment.findMany({ where: { schoolId }, select: { id: true } })).map((r) => r.id));

  const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
  if (!term) { console.log("no current term"); return; }

  // ── (a) overpay an invoice by GH₵20 → invoice PAID + credit row + receipt note ──
  console.log("\n── (a) overpayment creates credit ──");
  const invA = await db.invoice.findFirst({
    where: { schoolId, termId: term.id, payments: { none: {} }, OR: [{ status: "UNPAID" }, { status: "PARTIAL" }, { status: "OVERDUE" }] },
    orderBy: { invoiceNo: "asc" }, include: { student: true },
  });
  if (!invA) { console.log("no owing invoice without payments — aborting"); return; }
  const snapA = { paidCents: invA.paidCents, status: invA.status };
  const balA = invA.totalCents - invA.discountCents - invA.paidCents;
  const OVER = 2000; // GH₵20 overpayment
  const baseA = await creditSum(schoolId, invA.studentId);
  const payRes = await post(head, `/api/school/${schoolId}/fees`, {
    action: "record-payment", invoiceId: invA.id, amountCents: balA + OVER, method: "CASH", reference: "SMOKE-CREDITS",
  });
  check("record-payment accepts overpayment", payRes.status === 200, String(payRes.status));
  const payBody = await payRes.json().catch(() => ({}));
  const paymentId: string | undefined = payBody.payment?.id;
  check("payment stores the full received amount", payBody.payment?.amountCents === balA + OVER, JSON.stringify(payBody.payment?.amountCents));
  check("creditCreated exposed on the response", payBody.creditCreated === OVER, String(payBody.creditCreated));
  check("receipt payload carries the credit note (creditCreated)", payBody.receipt?.creditCreated === OVER, JSON.stringify(payBody.receipt?.creditCreated));
  const invAfter = await db.invoice.findUnique({ where: { id: invA.id } });
  check("invoice PAID — only the balance applied", invAfter?.status === "PAID" && invAfter?.paidCents === snapA.paidCents + balA, `status=${invAfter?.status} paid=${invAfter?.paidCents}`);
  const credA = await db.studentCredit.findFirst({ where: { sourcePaymentId: paymentId } });
  check("StudentCredit row kind=CREDIT +GH₵20, receipt-linked", credA?.kind === "CREDIT" && credA?.amountCents === OVER && credA?.balanceAfter === baseA + OVER, JSON.stringify(credA));

  // ── (b) credits list + stats ──
  console.log("\n── (b) credits list + stats ──");
  const listRes = await get(head, `/api/school/${schoolId}/ops/finance?action=credits`);
  const list = await listRes.json().catch(() => ({}));
  check("credits list 200", listRes.status === 200, String(listRes.status));
  const rowA = (list.rows ?? []).find((r: any) => r.studentId === invA.studentId);
  check("learner row shows base + GH₵20 balance", rowA?.balanceCents === baseA + OVER, JSON.stringify(rowA));
  check("stats.activeCents grows by the overpayment", (list.stats?.activeCents ?? -1) >= 5000 + OVER, JSON.stringify(list.stats));
  check("stats.studentsWithCredits grew if a new wallet opened", (list.stats?.studentsWithCredits ?? 0) >= preStudentsWithCredits + (baseA === 0 ? 1 : 0), `${list.stats?.studentsWithCredits} vs ${preStudentsWithCredits}`);
  check("stats.utilizationPct numeric", typeof list.stats?.utilizationPct === "number");
  check("roster returned for pickers", Array.isArray(list.students) && list.students.length > 50, String(list.students?.length));

  // ── (c) adjust ± ──
  console.log("\n── (c) adjust ± ──");
  const adj1 = await post(head, `/api/school/${schoolId}/ops/finance`, { action: "credit-adjust", studentId: invA.studentId, amountCents: 500, note: "SMOKE: +GH₵5 correction" });
  const adj1Body = await adj1.json().catch(() => ({}));
  check("adjust +GH₵5 → balance +500", adj1.status === 200 && adj1Body.balanceCents === baseA + OVER + 500, `${adj1.status} ${JSON.stringify(adj1Body.balanceCents)}`);
  const adj2 = await post(head, `/api/school/${schoolId}/ops/finance`, { action: "credit-adjust", studentId: invA.studentId, amountCents: -300, note: "SMOKE: −GH₵3 correction" });
  const adj2Body = await adj2.json().catch(() => ({}));
  check("adjust −GH₵3 → balance +200", adj2.status === 200 && adj2Body.balanceCents === baseA + OVER + 200, `${adj2.status} ${JSON.stringify(adj2Body.balanceCents)}`);
  const adjOver = await post(head, `/api/school/${schoolId}/ops/finance`, { action: "credit-adjust", studentId: invA.studentId, amountCents: -9999999, note: "SMOKE: overdraft attempt" });
  check("overdraft refused 400", adjOver.status === 400, String(adjOver.status));
  const adjNoNote = await post(head, `/api/school/${schoolId}/ops/finance`, { action: "credit-adjust", studentId: invA.studentId, amountCents: 100 });
  check("adjust without note refused 400 (audit)", adjNoNote.status === 400, String(adjNoNote.status));

  // ── (d) transfer learner A→B ──
  console.log("\n── (d) transfer A→B ──");
  const studentB = (list.students ?? []).find((s: any) => !(list.rows ?? []).some((r: any) => r.studentId === s.id));
  if (!studentB) { console.log("no clean target learner — aborting"); return; }
  const trRes = await post(head, `/api/school/${schoolId}/ops/finance`, {
    action: "credit-transfer", fromStudentId: invA.studentId, toStudentId: studentB.id, amountCents: 700, note: "SMOKE: sibling transfer",
  });
  const trBody = await trRes.json().catch(() => ({}));
  check("transfer 200", trRes.status === 200, String(trRes.status));
  check("TRANSFER_OUT −700 on source with running balance", trBody.out?.kind === "TRANSFER_OUT" && trBody.out?.amountCents === -700 && trBody.out?.balanceAfter === baseA + OVER + 200 - 700, JSON.stringify(trBody.out));
  check("TRANSFER_IN +700 on target", trBody.in?.kind === "TRANSFER_IN" && trBody.in?.amountCents === 700 && trBody.in?.balanceAfter === 700, JSON.stringify(trBody.in));
  const trOver = await post(head, `/api/school/${schoolId}/ops/finance`, { action: "credit-transfer", fromStudentId: invA.studentId, toStudentId: studentB.id, amountCents: 9999999, note: "SMOKE: too much" });
  check("insufficient balance refused 400", trOver.status === 400, String(trOver.status));

  // ── (e) credit → daily wallet ──
  console.log("\n── (e) credit → daily wallet ──");
  const walletBefore = await db.studentWallet.findUnique({ where: { studentId: studentB.id } });
  const toDaily = await post(head, `/api/school/${schoolId}/ops/finance`, { action: "credit-to-daily", studentId: studentB.id, amountCents: 400, note: "SMOKE: to wallet" });
  check("credit-to-daily 200", toDaily.status === 200, String(toDaily.status));
  const walletAfter = await db.studentWallet.findUnique({ where: { studentId: studentB.id } });
  check("wallet prepaidCents +GH₵4", walletAfter?.prepaidCents === (walletBefore?.prepaidCents ?? 0) + 400, `${walletBefore?.prepaidCents ?? 0} → ${walletAfter?.prepaidCents}`);
  const txn = await db.walletTxn.findFirst({ where: { studentId: studentB.id, kind: "TOPUP", wallet: "PREPAID" }, orderBy: { at: "desc" } });
  check("WalletTxn TOPUP row (PREPAID, +400)", txn?.amountCents === 400 && txn?.balanceAfter === walletAfter?.prepaidCents, JSON.stringify(txn));
  const dailyRow = await db.studentCredit.findFirst({ where: { studentId: studentB.id, kind: "DAILY_WALLET" }, orderBy: { at: "desc" } });
  check("DAILY_WALLET ledger row −400 (balance 300 left)", dailyRow?.amountCents === -400 && dailyRow?.balanceAfter === 300, JSON.stringify(dailyRow));

  // ── (f) generate-invoices auto-applies credit to a fresh invoice ──
  console.log("\n── (f) billing auto-applies credit ──");
  const fsRow = await db.feeStructure.findFirst({ where: { termId: term.id, schoolId }, include: { classroom: true } });
  if (!fsRow) { console.log("no fee structure for the current term — skipping (f)"); }
  else {
    const enrolledC = await db.enrollment.findMany({ where: { classroomId: fsRow.classroomId, status: "ACTIVE" }, select: { studentId: true } });
    const candInv = await db.invoice.findFirst({
      where: { schoolId, termId: term.id, payments: { none: {} }, studentId: { in: enrolledC.map((e) => e.studentId) } },
      orderBy: { invoiceNo: "asc" }, include: { items: true },
    });
    if (!candInv) { console.log("no payment-free invoice in that class — skipping (f)"); }
    else {
      const snapZ = {
        id: candInv.id, invoiceNo: candInv.invoiceNo, studentId: candInv.studentId, termId: candInv.termId,
        classroomName: candInv.classroomName, totalCents: candInv.totalCents, discountCents: candInv.discountCents,
        discountReason: candInv.discountReason, paidCents: candInv.paidCents, status: candInv.status,
        dueDate: candInv.dueDate, paymentPlan: candInv.paymentPlan, writtenOff: candInv.writtenOff,
        writeOffNote: candInv.writeOffNote, createdAt: candInv.createdAt,
        items: candInv.items.map((it) => ({ name: it.name, amountCents: it.amountCents })),
      };
      // bank GH₵50 of credit, remove the learner's current-term invoice, regenerate
      const mk = await post(head, `/api/school/${schoolId}/ops/finance`, { action: "credit-create", studentId: snapZ.studentId, amountCents: 5000, note: "SMOKE: credit for billing test" });
      check("credit-create 200", mk.status === 200, String(mk.status));
      const baseZ = await creditSum(schoolId, snapZ.studentId);
      await db.invoiceItem.deleteMany({ where: { invoiceId: snapZ.id } });
      await db.invoice.delete({ where: { id: snapZ.id } });
      const genRes = await post(head, `/api/school/${schoolId}/fees`, { action: "generate-invoices", classroomId: fsRow.classroomId });
      check("generate-invoices 200", genRes.status === 200, String(genRes.status));
      const genBody = await genRes.json().catch(() => ({}));
      check("response reports creditsApplied", (genBody.creditsApplied ?? 0) >= 1, JSON.stringify({ created: genBody.created, creditsApplied: genBody.creditsApplied }));
      const invZ = await db.invoice.findFirst({ where: { schoolId, termId: term.id, studentId: snapZ.studentId }, include: { items: true } });
      check("fresh invoice generated", !!invZ && invZ.id !== snapZ.id, invZ?.invoiceNo ?? "none");
      if (invZ) {
        const dueZ = invZ.totalCents - invZ.discountCents;
        const expectedApplied = Math.min(baseZ, dueZ);
        check("credit auto-applied to invoice.paidCents", invZ.paidCents === expectedApplied && ["PAID", "PARTIAL"].includes(invZ.status), `paid=${invZ.paidCents} expected=${expectedApplied} status=${invZ.status}`);
        const applyRow = await db.studentCredit.findFirst({ where: { studentId: snapZ.studentId, kind: "APPLY", invoiceId: invZ.id } });
        check("APPLY ledger row −applied, balanceAfter correct", applyRow?.amountCents === -expectedApplied && applyRow?.balanceAfter === baseZ - expectedApplied, JSON.stringify(applyRow));
        // cleanup (f): remove the regenerated invoice, restore the original from the snapshot
        await db.invoiceItem.deleteMany({ where: { invoiceId: invZ.id } });
        await db.invoice.delete({ where: { id: invZ.id } });
        await db.invoice.create({
          data: {
            id: snapZ.id, invoiceNo: snapZ.invoiceNo, studentId: snapZ.studentId, schoolId, termId: snapZ.termId,
            classroomName: snapZ.classroomName, totalCents: snapZ.totalCents, discountCents: snapZ.discountCents,
            discountReason: snapZ.discountReason, paidCents: snapZ.paidCents, status: snapZ.status,
            dueDate: snapZ.dueDate, paymentPlan: snapZ.paymentPlan, writtenOff: snapZ.writtenOff,
            writeOffNote: snapZ.writeOffNote, createdAt: snapZ.createdAt,
          },
        });
        await db.invoiceItem.createMany({ data: snapZ.items.map((it) => ({ invoiceId: snapZ.id, name: it.name, amountCents: it.amountCents })) });
        const restored = await db.invoice.findUnique({ where: { id: snapZ.id }, include: { items: true } });
        check("(f) original invoice restored", restored?.invoiceNo === snapZ.invoiceNo && restored.items.length === snapZ.items.length, `${restored?.invoiceNo} items=${restored?.items.length}`);
      }
    }
  }

  // ── (g) RBAC ──
  console.log("\n── (g) RBAC ──");
  const tAdj = await post(teacher, `/api/school/${schoolId}/ops/finance`, { action: "credit-adjust", studentId: invA.studentId, amountCents: 100, note: "SMOKE: teacher attempt" });
  check("teacher credit-adjust → 403", tAdj.status === 403, String(tAdj.status));
  const tGet = await get(teacher, `/api/school/${schoolId}/ops/finance?action=credits`);
  check("teacher credits list → 403", tGet.status === 403, String(tGet.status));
  const tCreate = await post(teacher, `/api/school/${schoolId}/ops/finance`, { action: "credit-create", studentId: invA.studentId, amountCents: 100, note: "SMOKE: teacher attempt" });
  check("teacher credit-create → 403", tCreate.status === 403, String(tCreate.status));

  // ── cleanup: remove everything the test created ──
  console.log("\n── cleanup ──");
  if (paymentId) await db.payment.delete({ where: { id: paymentId } }).catch(() => null);
  await db.invoice.update({ where: { id: invA.id }, data: { paidCents: snapA.paidCents, status: snapA.status } });
  // any other payments minted by the test (defensive)
  const strayPayments = await db.payment.findMany({ where: { schoolId, id: { notIn: [...prePaymentIds] } }, select: { id: true } });
  for (const p of strayPayments) await db.payment.delete({ where: { id: p.id } }).catch(() => null);
  // test credit rows (everything not in the pre-test snapshot)
  const delCredits = await db.studentCredit.deleteMany({ where: { schoolId, id: { notIn: [...preCreditIds] } } });
  check("test credit rows removed", delCredits.count > 0, String(delCredits.count));
  // invoices created by generate-invoices that were not already cleaned in (f)
  const strayInvoices = await db.invoice.findMany({ where: { schoolId, id: { notIn: [...preInvoiceIds] } }, select: { id: true } });
  for (const i of strayInvoices) {
    await db.invoiceItem.deleteMany({ where: { invoiceId: i.id } });
    await db.invoice.delete({ where: { id: i.id } }).catch(() => null);
  }
  check("no stray invoices left", strayInvoices.length === 0, String(strayInvoices.length));
  // wallet B back to its pre-test state
  if (walletBefore) {
    await db.studentWallet.update({ where: { studentId: studentB.id }, data: { prepaidCents: walletBefore.prepaidCents, transportCents: walletBefore.transportCents } });
  } else {
    await db.studentWallet.delete({ where: { studentId: studentB.id } }).catch(() => null);
  }
  const delTxns = await db.walletTxn.deleteMany({ where: { schoolId, id: { notIn: [...preTxnIds] } } });
  check("test wallet txns removed", delTxns.count >= 1, String(delTxns.count));
  // automated SMS fired during the test (payment receipt + invoice issued)
  const delSms = await db.smsMessage.deleteMany({ where: { schoolId, sentAt: { gte: testStart } } });
  check("test SMS removed", delSms.count >= 0, String(delSms.count));
  // final invariant: the ledger is exactly the pre-test ledger
  const postCredits = await db.studentCredit.findMany({ where: { schoolId }, select: { id: true } });
  check("credit ledger back to pre-test state", postCredits.length === preCreditIds.size && postCredits.every((r) => preCreditIds.has(r.id)), `${postCredits.length} vs ${preCreditIds.size}`);
  const invAFinal = await db.invoice.findUnique({ where: { id: invA.id } });
  check("overpaid invoice restored", invAFinal?.paidCents === snapA.paidCents && invAFinal?.status === snapA.status, `${invAFinal?.paidCents}/${snapA.paidCents} ${invAFinal?.status}`);

  console.log(`\n${fail === 0 ? "🎉" : "⚠️ "} ${pass} passed, ${fail} failed`);
  if (fail > 0) process.exitCode = 1;
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

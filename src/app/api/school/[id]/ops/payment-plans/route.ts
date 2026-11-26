import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { audit } from "@/lib/audit";
import { sendSms } from "@/lib/sms";

// ── Payment Plans (wave 69) — the Ghanaian way of paying school fees ─────
// Split an invoice's remaining balance into dated installments the bursar
// tracks, reminds (SMS) and settles against real receipts. One ACTIVE plan
// per invoice; OVERDUE is derived (dueDate < today && status OPEN), never
// stored. Settlement records which receipt covered the installment — the
// money itself is always receipted through the normal collection flow.
//   GET                       — active plans + eligible invoices + stats
//   POST plan.create          — manual installment schedule (sums == balance)
//   POST plan.settle          — mark one installment paid (receipt ref)
//   POST plan.cancel          — retire a plan (invoice reverts to FULL)
//   POST plan.remind          — SMS the outstanding schedule to the guardian

const bad = (error: string, status = 400) => NextResponse.json({ error }, { status });
const optStr = (v: unknown): string | null => {
  if (typeof v !== "string") return null;
  const t = v.trim();
  return t.length ? t : null;
};
const dayStart = (v: unknown): Date | null => {
  if (typeof v !== "string" || !/^\d{4}-\d{2}-\d{2}/.test(String(v).trim())) return null;
  const d = new Date(`${String(v).trim().slice(0, 10)}T00:00:00.000Z`);
  return isNaN(d.getTime()) ? null : d;
};
const isoDay = (d: Date | string | null | undefined) => (d ? new Date(d).toISOString().slice(0, 10) : null);
const TODAY = () => new Date(`${new Date().toISOString().slice(0, 10)}T00:00:00.000Z`);

async function loadPlans(schoolId: string) {
  const rows = await db.paymentPlan.findMany({
    where: { schoolId, status: "ACTIVE" },
    orderBy: { updatedAt: "desc" },
    take: 100,
    include: {
      installments: { orderBy: { sequence: "asc" } },
      invoice: { include: { student: { select: { firstName: true, lastName: true, nyansapoIndex: true, guardianName: true, guardianPhone: true } } } },
    },
  });
  const today = TODAY();
  const dueSoonEnd = new Date(today.getTime() + 3 * 86400000);
  const plans = rows.map((p) => {
    const open = p.installments.filter((i) => i.status === "OPEN");
    return {
      id: p.id, status: p.status, createdByName: p.createdByName, note: p.note, createdAt: p.createdAt,
      invoice: {
        id: p.invoice.id, invoiceNo: p.invoice.invoiceNo,
        termBalance: p.invoice.totalCents - p.invoice.discountCents - p.invoice.paidCents,
        total: p.invoice.totalCents, paid: p.invoice.paidCents, discount: p.invoice.discountCents,
        status: p.invoice.status,
        student: {
          name: `${p.invoice.student.firstName} ${p.invoice.student.lastName}`,
          nyansapoIndex: p.invoice.student.nyansapoIndex,
          guardianName: p.invoice.student.guardianName,
          guardianPhone: p.invoice.student.guardianPhone,
        },
      },
      installments: p.installments.map((i) => ({
        id: i.id, sequence: i.sequence, label: i.label, amountCents: i.amountCents,
        dueDate: isoDay(i.dueDate)!, status: i.status,
        overdue: i.status === "OPEN" && i.dueDate < today,
        paidCents: i.paidCents, paidAt: i.paidAt ? isoDay(i.paidAt) : null,
        receiptNo: i.receiptNo, settledByName: i.settledByName,
      })),
      openCount: open.length,
      dueSoonCents: open.filter((i) => i.dueDate < dueSoonEnd).reduce((s, i) => s + (i.amountCents - i.paidCents), 0),
      overdueCount: open.filter((i) => i.dueDate < today).length,
    };
  });
  return {
    plans,
    stats: {
      active: plans.length,
      dueSoon: plans.reduce((s, p) => s + p.installments.filter((i) => i.status === "OPEN" && i.dueDate >= isoDay(today)! && i.dueDate <= isoDay(dueSoonEnd)!).length, 0),
      overdue: plans.filter((p) => p.overdueCount > 0).length,
      plannedCents: plans.reduce((s, p) => s + p.installments.filter((i) => i.status === "OPEN").reduce((x, i) => x + (i.amountCents - i.paidCents), 0), 0),
    },
  };
}

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["fees.view"] });
  if (!auth.ok) return auth.res;

  const [data, eligible] = await Promise.all([
    loadPlans(schoolId),
    db.invoice.findMany({
      where: { schoolId, status: { in: ["UNPAID", "PARTIAL", "OVERDUE"] }, writtenOff: false },
      orderBy: { createdAt: "desc" },
      take: 300,
      include: { student: { select: { firstName: true, lastName: true } } },
    }),
  ]);

  return NextResponse.json({
    ...data,
    eligible: eligible
      .filter((i) => i.totalCents - i.discountCents - i.paidCents > 0)
      .map((i) => ({
        id: i.id, invoiceNo: i.invoiceNo,
        balance: i.totalCents - i.discountCents - i.paidCents,
        status: i.status, dueDate: isoDay(i.dueDate),
        student: `${i.student.firstName} ${i.student.lastName}`,
      }))
      .slice(0, 120),
  });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["fees.manage"] });
  if (!auth.ok) return auth.res;
  const { user } = auth;
  const body = (await req.json().catch(() => ({}))) as Record<string, unknown>;
  const action = String(body.action ?? "");

  /* ── plan.create — manual schedule, must sum exactly to the balance ── */
  if (action === "plan.create") {
    const invoiceId = optStr(body.invoiceId);
    if (!invoiceId) return bad("invoiceId is required");
    const invoice = await db.invoice.findFirst({
      where: { id: invoiceId, schoolId },
      include: { student: { select: { firstName: true, lastName: true, guardianName: true, guardianPhone: true } } },
    });
    if (!invoice) return bad("Invoice not found", 404);
    const balance = invoice.totalCents - invoice.discountCents - invoice.paidCents;
    if (invoice.writtenOff || balance <= 0) return bad("This invoice has nothing outstanding — no plan needed");
    const clash = await db.paymentPlan.findFirst({ where: { schoolId, invoiceId, status: "ACTIVE" } });
    if (clash) return bad("This invoice already has an active payment plan — cancel it first");

    const rawParts = Array.isArray(body.installments) ? body.installments as { amountCents?: number; dueDate?: string; label?: string }[] : [];
    if (rawParts.length < 2) return bad("A payment plan needs at least two installments");
    const items: { sequence: number; label: string | null; amountCents: number; dueDate: Date }[] = [];
    let lastDue: Date | null = null;
    for (let ix = 0; ix < rawParts.length; ix++) {
      const m = rawParts[ix];
      const cents = Math.round(Number(m.amountCents ?? 0));
      const due = dayStart(m.dueDate);
      if (!due) return bad(`Installment ${ix + 1} needs a due date`);
      if (!(cents > 0)) return bad(`Installment ${ix + 1} needs an amount above zero`);
      if (lastDue && due <= lastDue) return bad(`Installment ${ix + 1} must fall after installment ${ix}`);
      items.push({ sequence: ix + 1, label: optStr(m.label), amountCents: cents, dueDate: due });
      lastDue = due;
    }
    const sum = items.reduce((s, i) => s + i.amountCents, 0);
    if (sum !== balance) return bad(`The parts sum to ${(sum / 100).toFixed(2)} but the balance is ${(balance / 100).toFixed(2)} — they must match exactly`);

    const plan = await db.paymentPlan.create({
      data: {
        schoolId, invoiceId, status: "ACTIVE", createdByName: user.fullName,
        note: optStr(body.note),
        installments: { create: items.map((i) => ({ sequence: i.sequence, label: i.label, amountCents: i.amountCents, dueDate: i.dueDate })) },
      },
    });
    if (invoice.paymentPlan !== "INSTALLMENTS") {
      await db.invoice.update({ where: { id: invoice.id }, data: { paymentPlan: "INSTALLMENTS" } });
    }
    await audit({
      schoolId, actor: { id: user.id, fullName: user.fullName, role: user.role },
      action: "CREATE", entityType: "PaymentPlan", entityId: plan.id,
      entityLabel: `${invoice.invoiceNo} — ${items.length} installments`,
      summary: `Payment plan for ${invoice.invoiceNo} (${invoice.student.firstName} ${invoice.student.lastName}): ${items.length} installments totalling ${(balance / 100).toFixed(2)} GHS, first due ${isoDay(items[0].dueDate)}${optStr(body.note) ? ` · "${optStr(body.note)}"` : ""}`, req,
    });
    const data = await loadPlans(schoolId);
    return NextResponse.json({ ok: true, ...data });
  }

  /* ── plan.settle — record a receipt against one installment ── */
  if (action === "plan.settle") {
    const installmentId = optStr(body.installmentId);
    if (!installmentId) return bad("installmentId is required");
    const inst = await db.paymentPlanInstallment.findFirst({
      where: { id: installmentId },
      include: { plan: { include: { invoice: true } } },
    });
    if (!inst || inst.plan.schoolId !== schoolId) return bad("Installment not found", 404);
    if (inst.status !== "OPEN") return bad("This installment is already settled");
    const invoice = inst.plan.invoice;
    const remaining = inst.amountCents - inst.paidCents;
    const receiptNo = optStr(body.receiptNo);
    if (receiptNo) {
      const receipt = await db.payment.findUnique({ where: { receiptNo } });
      if (!receipt || receipt.schoolId !== schoolId) return bad(`Receipt ${receiptNo} does not exist in this school`);
      if (receipt.invoiceId !== invoice.id) return bad(`Receipt ${receiptNo} belongs to a different invoice — the money must be receipted on ${invoice.invoiceNo}`);
    }
    await db.paymentPlanInstallment.update({
      where: { id: installmentId },
      data: { paidCents: remaining, status: "PAID", paidAt: new Date(), receiptNo: receiptNo ?? inst.receiptNo, settledByName: user.fullName },
    });
    const siblings = await db.paymentPlanInstallment.findMany({ where: { planId: inst.planId } });
    if (siblings.length && siblings.every((s) => s.status === "PAID")) {
      await db.paymentPlan.update({ where: { id: inst.planId }, data: { status: "SETTLED" } });
      if (invoice.paymentPlan === "INSTALLMENTS") {
        await db.invoice.update({ where: { id: invoice.id }, data: { paymentPlan: "FULL" } });
      }
    }
    await audit({
      schoolId, actor: { id: user.id, fullName: user.fullName, role: user.role },
      action: "UPDATE", entityType: "PaymentPlanInstallment", entityId: installmentId,
      entityLabel: `${invoice.invoiceNo} installment ${inst.sequence}`,
      summary: `Installment ${inst.sequence} on ${invoice.invoiceNo} settled: ${(remaining / 100).toFixed(2)} GHS${receiptNo ? ` (receipt ${receiptNo})` : " (no receipt ref)"} — by ${user.fullName}`, req,
    });
    const data = await loadPlans(schoolId);
    return NextResponse.json({ ok: true, ...data });
  }

  /* ── plan.cancel — retire the plan (invoice reverts to FULL) ── */
  if (action === "plan.cancel") {
    const planId = optStr(body.planId);
    if (!planId) return bad("planId is required");
    const plan = await db.paymentPlan.findFirst({ where: { id: planId, schoolId }, include: { invoice: true } });
    if (!plan) return bad("Plan not found", 404);
    if (plan.status !== "ACTIVE") return bad("Only an active plan can be cancelled");
    const note = optStr(body.note);
    await db.paymentPlan.update({ where: { id: planId }, data: { status: "CANCELLED", ...(note ? { note: `Cancelled — ${note}` } : {}) } });
    const stillActive = await db.paymentPlan.findFirst({ where: { invoiceId: plan.invoiceId, status: "ACTIVE" } });
    if (!stillActive && plan.invoice.paymentPlan === "INSTALLMENTS") {
      await db.invoice.update({ where: { id: plan.invoiceId }, data: { paymentPlan: "FULL" } });
    }
    await audit({
      schoolId, actor: { id: user.id, fullName: user.fullName, role: user.role },
      action: "UPDATE", entityType: "PaymentPlan", entityId: planId,
      entityLabel: plan.invoice.invoiceNo,
      summary: `Cancelled the payment plan on ${plan.invoice.invoiceNo}${note ? ` — ${note}` : ""}`, req,
    });
    const data = await loadPlans(schoolId);
    return NextResponse.json({ ok: true, ...data });
  }

  /* ── plan.remind — SMS the outstanding schedule to the guardian ── */
  if (action === "plan.remind") {
    const planId = optStr(body.planId);
    if (!planId) return bad("planId is required");
    const plan = await db.paymentPlan.findFirst({
      where: { id: planId, schoolId, status: "ACTIVE" },
      include: {
        installments: { orderBy: { sequence: "asc" } },
        invoice: { include: { student: { select: { firstName: true, lastName: true, guardianName: true, guardianPhone: true } } } },
      },
    });
    if (!plan) return bad("Plan not found", 404);
    const today = TODAY();
    const open = plan.installments.filter((i) => i.status === "OPEN");
    if (!open.length) return bad("Every installment on this plan is settled — nothing to remind about");
    const student = plan.invoice.student;
    const phone = student.guardianPhone;
    if (!phone) return bad("The guardian has no phone number on record");
    const school = await db.school.findUnique({ where: { id: schoolId }, select: { name: true } });
    const list = open.map((i) => `${i.label ?? `Part ${i.sequence}`} ${(i.amountCents / 100).toFixed(2)} due ${isoDay(i.dueDate)}${i.dueDate < today ? " (OVERDUE)" : ""}`).join("; ");
    const r = await sendSms({
      db, schoolId, studentId: plan.invoice.studentId,
      recipientName: student.guardianName || `${student.firstName} ${student.lastName}`,
      recipientPhone: phone,
      body: `${school?.name ?? "School"}: Payment plan for ${student.firstName} (${plan.invoice.invoiceNo}) — ${list}. Kindly settle at the school office or by MoMo. — ${user.fullName}`.slice(0, 320),
      category: "FEES", sentByName: user.fullName,
    });
    await audit({
      schoolId, actor: { id: user.id, fullName: user.fullName, role: user.role },
      action: "UPDATE", entityType: "PaymentPlan", entityId: planId,
      entityLabel: plan.invoice.invoiceNo,
      summary: `Payment-plan SMS to ${student.guardianName || student.firstName} (${r.status}) — ${open.length} open installment(s) on ${plan.invoice.invoiceNo}`, req,
    });
    return NextResponse.json({ ok: true, sms: r.status, chased: open.length });
  }

  return bad("Unknown action");
}

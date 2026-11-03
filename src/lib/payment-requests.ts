// ── MoMo payment requests — engine (Hubtel + SIMULATED) ───────
// Shared core behind the payments module API, the Hubtel webhook and the
// public /pay status page:
//   • createRequest      — validate + persist + call Hubtel (or simulate)
//   • checkRequest       — poll Hubtel (checkout token / transaction id)
//   • applyCompletion    — idempotently post a completed request to the
//                          right ledger (invoice, daily items FIFO, per-item
//                          balance, wallet) + receipt SMS + audit event
// SIMULATED mode (no Hubtel env credentials) mirrors the SMS gateway
// approach: requests are created locally, the public /pay page carries a
// "simulate payment" button, and everything else behaves identically.

import { PrismaClient, Payment, PaymentRequest } from "@prisma/client";
import { collectDailyItems, creditItemBalance } from "@/lib/daily-billing";
import { sendAutomated } from "@/lib/sms";
import { nextReceiptNo } from "@/lib/codes";
import {
  hubtelPaymentsConfigured, hubtelReceiveMoney, hubtelCreateCheckout, hubtelCheckoutStatus,
  hubtelTransactionStatus, hubtelWebhookUrl, hubtelCallbackBase, newClientReference, detectMomoChannel,
} from "@/lib/hubtel";
import { toGhanaIntl } from "@/lib/phone";

export const PAYMENT_PURPOSES = ["INVOICE", "DAILY_ITEMS", "ITEM_BALANCE", "WALLET"] as const;
export type PaymentPurpose = (typeof PAYMENT_PURPOSES)[number];

export const PURPOSE_LABELS: Record<string, string> = {
  INVOICE: "Termly fees",
  DAILY_ITEMS: "Daily fee items",
  ITEM_BALANCE: "Fee-item advance",
  WALLET: "Wallet top-up",
};

const MOMO_LABEL: Record<string, string> = {
  "mtn-gh": "MTN MoMo",
  "vodafone-gh": "Telecel Cash",
  "tigo-gh": "AT Money",
};

const fmtGH = (cents: number) => `GH₵${(cents / 100).toFixed(2)}`;

type RequestItem = { serviceId: string; amountCents: number };

export function paymentsProvider(): "HUBTEL" | "SIMULATED" {
  return hubtelPaymentsConfigured() ? "HUBTEL" : "SIMULATED";
}

/** Public pay-page URL for a request (the local, friendly status page the
 *  parent lands on; live checkouts redirect back here after paying). */
export function publicPayUrl(req: { clientReference: string }): string | null {
  const base = hubtelCallbackBase();
  return base ? `${base}/pay/${req.clientReference}` : null;
}

export async function logEvent(db: PrismaClient, requestId: string, kind: string, payload?: unknown, note?: string) {
  await db.paymentEvent.create({
    data: { requestId, kind, payload: payload === undefined ? undefined : (payload as any), note: note ?? null },
  }).catch(() => {}); // event logging must never break the flow
}

// ── creation ───────────────────────────────────────────────────
export type CreateRequestInput = {
  schoolId: string;
  studentId: string;
  purpose: PaymentPurpose;
  invoiceId?: string | null;
  items?: RequestItem[];
  walletKind?: "PREPAID" | "TRANSPORT" | "ITEM" | null;
  amountCents: number;
  method: "MOMO_PROMPT" | "PAYMENT_LINK";
  payerMsisdn?: string;
  channel?: string | null;
  createdByName: string;
  description?: string;
};

export async function createPaymentRequest(db: PrismaClient, input: CreateRequestInput): Promise<
  { ok: true; request: PaymentRequest } | { ok: false; error: string }
> {
  const student = await db.student.findUnique({ where: { id: input.studentId } });
  if (!student) return { ok: false, error: "Learner not found" };
  const enrolled = await db.enrollment.findFirst({ where: { studentId: student.id, schoolId: input.schoolId } });
  if (!enrolled) return { ok: false, error: "This learner is not enrolled in this school" };
  const amount = Math.round(Number(input.amountCents) || 0);
  if (amount <= 0) return { ok: false, error: "Enter an amount greater than zero" };
  if (amount < 50) return { ok: false, error: "Hubtel collects a minimum of GH₵0.50 per transaction" };

  // purpose validation + description
  let itemsJson: RequestItem[] | null = null;
  let description = input.description ?? "";
  let invoiceId: string | null = null;
  let walletKind: string | null = null;

  if (input.purpose === "INVOICE") {
    if (!input.invoiceId) return { ok: false, error: "Choose the invoice this request settles" };
    const inv = await db.invoice.findFirst({ where: { id: input.invoiceId, schoolId: input.schoolId, studentId: input.studentId } });
    if (!inv) return { ok: false, error: "Invoice not found for this learner" };
    const balance = inv.totalCents - inv.discountCents - inv.paidCents;
    if (balance <= 0) return { ok: false, error: "This invoice is already settled" };
    if (amount > balance) return { ok: false, error: `Amount exceeds the invoice balance of ${fmtGH(balance)}` };
    invoiceId = inv.id;
    description = description || `School fees — invoice ${inv.invoiceNo}`;
  } else if (input.purpose === "DAILY_ITEMS") {
    const parsed = (input.items ?? []).filter((i) => i.serviceId && i.amountCents > 0);
    if (!parsed.length) return { ok: false, error: "Choose at least one fee item and amount" };
    const svc = await db.dailyService.findMany({ where: { schoolId: input.schoolId, id: { in: parsed.map((i) => i.serviceId) } } });
    if (svc.length !== parsed.length) return { ok: false, error: "One of the fee items does not belong to this school" };
    const total = parsed.reduce((s, i) => s + i.amountCents, 0);
    if (total !== amount) return { ok: false, error: "Item amounts must add up to the request amount" };
    itemsJson = parsed;
    description = description || `Daily fees — ${svc.map((s) => s.name).join(", ")}`;
  } else if (input.purpose === "ITEM_BALANCE") {
    const one = (input.items ?? []).filter((i) => i.serviceId && i.amountCents > 0)[0];
    if (!one || input.items!.filter((i) => i.amountCents > 0).length !== 1) return { ok: false, error: "Choose exactly one fee item to credit" };
    const svc = await db.dailyService.findFirst({ where: { id: one.serviceId, schoolId: input.schoolId } });
    if (!svc) return { ok: false, error: "Fee item not found" };
    itemsJson = [one];
    description = description || `Advance payment — ${svc.name}`;
  } else if (input.purpose === "WALLET") {
    const kind = input.walletKind ?? "PREPAID";
    if (!["PREPAID", "TRANSPORT"].includes(kind)) return { ok: false, error: "Choose a wallet (prepaid or transport)" };
    walletKind = kind;
    description = description || (kind === "PREPAID" ? "Prepaid wallet top-up" : "Transport wallet top-up");
  } else {
    return { ok: false, error: "Unknown purpose" };
  }

  // payer
  const payerMsisdn = toGhanaIntl(input.payerMsisdn || student.guardianPhone);
  if (!payerMsisdn) return { ok: false, error: "A valid Ghana mobile-money number is required" };
  const payerName = student.guardianName || `${student.firstName} ${student.lastName}`;

  const provider = paymentsProvider();
  const method = input.method === "PAYMENT_LINK" ? "PAYMENT_LINK" : "MOMO_PROMPT";
  if (method === "MOMO_PROMPT" && provider === "HUBTEL" && !hubtelWebhookUrl())
    return { ok: false, error: "MoMo prompts need HUBTEL_CALLBACK_BASE (the public URL of this deployment) — use a payment link instead" };

  const clientReference = newClientReference();
  const channel = method === "MOMO_PROMPT" ? (input.channel || detectMomoChannel(payerMsisdn).channel) : null;

  // call Hubtel FIRST (live mode) so a gateway failure never leaves a dangling row
  let providerTxnId: string | null = null;
  let checkoutToken: string | null = null;
  let checkoutUrl: string | null = null;
  if (provider === "HUBTEL") {
    if (method === "MOMO_PROMPT") {
      const r = await hubtelReceiveMoney({
        customerName: payerName,
        customerMsisdn: payerMsisdn,
        amount: amount / 100,
        description: description.slice(0, 120),
        clientReference,
        channel: channel ?? "mtn-gh",
        callbackUrl: hubtelWebhookUrl(),
      });
      if (!r.ok) return { ok: false, error: r.error ?? "Hubtel rejected the request" };
      providerTxnId = r.data?.transactionId ?? null;
    } else {
      const payUrl = publicPayUrl({ clientReference });
      if (!payUrl) return { ok: false, error: "Payment links need HUBTEL_CALLBACK_BASE (the public URL of this deployment)" };
      const r = await hubtelCreateCheckout({
        description: description.slice(0, 160),
        items: [{ name: description.slice(0, 60) || "School payment", quantity: 1, unitPrice: amount / 100, totalPrice: amount / 100 }],
        totalAmount: amount / 100,
        callbackUrl: hubtelWebhookUrl(),
        returnUrl: `${payUrl}?done=1`,
        cancellationUrl: `${payUrl}?cancelled=1`,
        payeeName: payerName,
        payeeMsisdn: payerMsisdn,
        clientReference,
      });
      if (!r.ok) return { ok: false, error: r.error ?? "Hubtel rejected the checkout" };
      checkoutToken = r.data?.token ?? null;
      checkoutUrl = r.data?.checkoutUrl ?? null;
    }
  } else {
    // SIMULATED — local link behaves like the hosted checkout
    if (method === "PAYMENT_LINK") {
      const payUrl = publicPayUrl({ clientReference });
      if (payUrl) checkoutUrl = payUrl;
      else checkoutUrl = `/pay/${clientReference}`; // relative fallback (client resolves origin)
      checkoutToken = `SIM-${clientReference}`;
    } else {
      providerTxnId = `SIM-${clientReference}`;
    }
  }

  const request = await db.paymentRequest.create({
    data: {
      schoolId: input.schoolId, studentId: input.studentId, purpose: input.purpose,
      invoiceId, items: itemsJson as any, walletKind,
      amountCents: amount, method, channel, payerName, payerMsisdn,
      status: "PENDING", provider, clientReference,
      providerTxnId, checkoutToken, checkoutUrl,
      createdByName: input.createdByName,
    },
  });
  await logEvent(db, request.id, "INITIATED", {
    provider, method, amountCents: amount, purpose: input.purpose,
    channel, providerTxnId, checkoutToken,
  }, `${provider} ${method === "MOMO_PROMPT" ? `momo prompt → ${payerMsisdn} (${MOMO_LABEL[channel ?? ""] ?? channel})` : "checkout link"}`);
  return { ok: true, request };
}

// ── status checking ────────────────────────────────────────────
/** Poll Hubtel (live) for the authoritative status; SIMULATED stays at
 *  PENDING until the public page's simulate button completes it. */
export async function checkPaymentRequest(db: PrismaClient, request: PaymentRequest): Promise<PaymentRequest> {
  if (request.status !== "PENDING") return request;
  await db.paymentRequest.update({ where: { id: request.id }, data: { lastCheckedAt: new Date() } });

  if (request.provider === "HUBTEL") {
    let status: "PENDING" | "COMPLETED" | "FAILED" | "CANCELLED" | null = null;
    if (request.checkoutToken) {
      const r = await hubtelCheckoutStatus(request.checkoutToken);
      if (r.ok) status = r.data!.status;
    } else {
      const r = await hubtelTransactionStatus({ transactionId: request.providerTxnId, clientReference: request.clientReference });
      if (r.ok) status = r.data!.status;
    }
    if (status === "COMPLETED") return applyCompletion(db, request.id, "status check");
    if (status === "FAILED") return failRequest(db, request.id, "Declined / failed at the mobile-money network");
    if (status === "CANCELLED") return cancelRequest(db, request.id, "Cancelled by the payer");
  }
  return (await db.paymentRequest.findUnique({ where: { id: request.id } })) ?? request;
}

export async function failRequest(db: PrismaClient, requestId: string, reason: string): Promise<PaymentRequest> {
  const upd = await db.paymentRequest.updateMany({ where: { id: requestId, status: "PENDING" }, data: { status: "FAILED", failureReason: reason.slice(0, 300) } });
  if (upd.count) await logEvent(db, requestId, "FAILED", undefined, reason);
  return (await db.paymentRequest.findUnique({ where: { id: requestId } }))!;
}

export async function cancelRequest(db: PrismaClient, requestId: string, reason: string): Promise<PaymentRequest> {
  const upd = await db.paymentRequest.updateMany({ where: { id: requestId, status: "PENDING" }, data: { status: "CANCELLED", failureReason: reason.slice(0, 300) } });
  if (upd.count) await logEvent(db, requestId, "CANCELLED", undefined, reason);
  return (await db.paymentRequest.findUnique({ where: { id: requestId } }))!;
}

// ── completion (idempotent ledger application) ─────────────────
/** Post a completed request into the school ledgers. Safe to call from the
 *  webhook AND the status checker concurrently — the status transition is an
 *  atomic compare-and-set, only the winner applies. */
export async function applyCompletion(db: PrismaClient, requestId: string, source: "webhook" | "status check" | "simulate"): Promise<PaymentRequest> {
  // atomic claim: only a PENDING row transitions to COMPLETED
  const claim = await db.paymentRequest.updateMany({
    where: { id: requestId, status: "PENDING" },
    data: { status: "COMPLETED", completedAt: new Date() },
  });
  const request = await db.paymentRequest.findUnique({ where: { id: requestId } });
  if (!request) throw new Error("request vanished");
  if (!claim.count) return request; // someone else already applied it

  try {
    const student = request.studentId ? await db.student.findUnique({ where: { id: request.studentId } }) : null;
    const momoChannel = MOMO_LABEL[request.channel ?? ""] ?? "Mobile Money";
    const method = `MOMO_${(request.channel ?? "").split("-")[0]?.toUpperCase() || "GH"}`;
    const byName = `Hubtel MoMo — ${request.payerName}`;
    let payment: Payment | null = null;
    let note = "";

    if (request.purpose === "INVOICE" && request.invoiceId) {
      // termly fees — mirrors the record-payment core (excess → credit)
      const inv = await db.invoice.findUnique({ where: { id: request.invoiceId } });
      if (inv) {
        const invBalance = Math.max(0, inv.totalCents - inv.discountCents - inv.paidCents);
        const amt = Math.min(request.amountCents, invBalance);
        const excess = request.amountCents - amt;
        payment = await db.payment.create({
          data: {
            receiptNo: await nextReceiptNo(), invoiceId: inv.id, studentId: request.studentId,
            schoolId: request.schoolId, amountCents: request.amountCents,
            method, reference: request.clientReference,
            receivedByName: byName, paidAt: new Date(),
          },
        });
        const paid = inv.paidCents + amt;
        await db.invoice.update({ where: { id: inv.id }, data: { paidCents: paid, status: paid >= inv.totalCents - inv.discountCents ? "PAID" : "PARTIAL" } });
        if (excess > 0) {
          const agg = await db.studentCredit.aggregate({ where: { schoolId: request.schoolId, studentId: request.studentId! }, _sum: { amountCents: true } });
          await db.studentCredit.create({
            data: {
              schoolId: request.schoolId, studentId: request.studentId!, kind: "CREDIT", amountCents: excess,
              balanceAfter: (agg._sum.amountCents ?? 0) + excess, sourcePaymentId: payment.id,
              note: `MoMo overpayment on ${inv.invoiceNo} — carried forward`, byName,
            },
          });
        }
        note = `Invoice ${inv.invoiceNo} — settled ${fmtGH(amt)}${excess ? `, credit ${fmtGH(excess)}` : ""}`;
      }
    } else if (request.purpose === "DAILY_ITEMS") {
      const items = (request.items as RequestItem[] | null) ?? [];
      const svc = await db.dailyService.findMany({ where: { schoolId: request.schoolId, id: { in: items.map((i) => i.serviceId) } } });
      const r = await collectDailyItems(db, {
        schoolId: request.schoolId, studentId: request.studentId!,
        items: items.filter((i) => i.amountCents > 0),
        services: svc.map((s) => ({ id: s.id, name: s.name })),
        method, reference: request.clientReference,
        receivedByName: byName, performedByRole: "MOMO",
        paidAt: new Date(),
      });
      payment = r.payment;
      note = r.breakdown.map((b) => `${b.name} ${fmtGH(b.paid)}${b.advance ? ` (+${fmtGH(b.advance)} advance)` : ""}`).join(", ");
    } else if (request.purpose === "ITEM_BALANCE") {
      const one = ((request.items as RequestItem[] | null) ?? [])[0];
      if (one) {
        const svc = await db.dailyService.findUnique({ where: { id: one.serviceId } });
        payment = await db.payment.create({
          data: {
            receiptNo: await nextReceiptNo(), studentId: request.studentId!, category: "DAILY",
            schoolId: request.schoolId, amountCents: request.amountCents,
            method, reference: request.clientReference,
            receivedByName: byName, paidAt: new Date(),
          },
        });
        await creditItemBalance(db, {
          schoolId: request.schoolId, studentId: request.studentId!, serviceId: one.serviceId,
          amountCents: request.amountCents, kind: "TOPUP",
          note: `MoMo advance (${request.clientReference})`, byName,
        });
        note = `${svc?.name ?? "Fee item"} — advance ${fmtGH(request.amountCents)}`;
      }
    } else if (request.purpose === "WALLET") {
      const kind = request.walletKind === "TRANSPORT" ? "TRANSPORT" : "PREPAID";
      payment = await db.payment.create({
        data: {
          receiptNo: await nextReceiptNo(), studentId: request.studentId!, category: "DAILY",
          schoolId: request.schoolId, amountCents: request.amountCents,
          method, reference: request.clientReference,
          receivedByName: byName, paidAt: new Date(),
        },
      });
      const w = await db.studentWallet.upsert({
        where: { studentId: request.studentId! },
        create: { schoolId: request.schoolId, studentId: request.studentId!, prepaidCents: 0, transportCents: 0 },
        update: {},
      });
      const balAfter = (kind === "PREPAID" ? w.prepaidCents : w.transportCents) + request.amountCents;
      await db.studentWallet.update({
        where: { studentId: request.studentId! },
        data: kind === "PREPAID" ? { prepaidCents: { increment: request.amountCents } } : { transportCents: { increment: request.amountCents } },
      });
      await db.walletTxn.create({
        data: {
          schoolId: request.schoolId, studentId: request.studentId!, wallet: kind, kind: "TOPUP",
          amountCents: request.amountCents, balanceAfter: balAfter,
          note: `MoMo top-up (${request.clientReference})`, byName,
        },
      });
      note = `${kind === "PREPAID" ? "Prepaid" : "Transport"} wallet +${fmtGH(request.amountCents)}`;
    }

    await db.paymentRequest.update({
      where: { id: request.id },
      data: { appliedPaymentId: payment?.id ?? null, appliedNote: note || null },
    });

    // receipt SMS to the guardian (respects the SMS automation setting)
    if (student) {
      const smsSetting = await db.smsSetting.findUnique({ where: { schoolId: request.schoolId } });
      if (smsSetting?.autoPaymentReceipts) {
        await sendAutomated({
          db, schoolId: request.schoolId, trigger: "PAYMENT_RECEIVED",
          defaultBody: `We have received ${fmtGH(request.amountCents)} via ${momoChannel} for ${student.firstName}${payment ? ` (${payment.receiptNo})` : ""}. ${note}. Thank you.`,
          vars: {
            student: student.firstName, amount: fmtGH(request.amountCents),
            receipt: payment?.receiptNo ?? "", date: new Date().toLocaleDateString("en-GB"),
          },
          studentId: request.studentId, recipientName: student.guardianName,
          recipientPhone: student.guardianPhone, sentByName: "Hubtel MoMo",
        });
      }
    }
    await logEvent(db, request.id, "COMPLETED", { source }, note);
  } catch (e: any) {
    // roll the claim back so a retry can try again — never lose money silently
    await db.paymentRequest.update({
      where: { id: request.id, status: "COMPLETED" },
      data: { status: "PENDING", completedAt: null },
    }).catch(() => {});
    await logEvent(db, request.id, "ERROR", { error: e?.message }, `applyCompletion failed: ${e?.message}`);
    throw e;
  }
  return (await db.paymentRequest.findUnique({ where: { id: requestId } }))!;
}

/** Parse a Hubtel callback payload (JSON or form-encoded) into a normalised
 *  shape: {reference, transactionId, status}. Tolerates all documented
 *  generations of the API. */
export function parseHubtelCallback(payload: any): { reference: string | null; transactionId: string | null; status: "COMPLETED" | "FAILED" | "CANCELLED" | "PENDING"; amountCents: number | null } {
  const p = payload ?? {};
  const data = p.Data ?? p.data ?? {};
  const reference = p.ClientReference || p.clientReference || data.ClientReference || data.clientReference || p.client_reference || null;
  const transactionId = p.TransactionId || p.transactionId || data.TransactionId || data.transactionId || p.HubtelTransactionId || null;
  const rc = String(p.ResponseCode ?? p.responseCode ?? data.ResponseCode ?? data.responseCode ?? "");
  const st = String(p.Status ?? p.status ?? data.Status ?? data.status ?? "").toLowerCase();
  const amount = Number(p.Amount ?? p.amount ?? data.Amount ?? data.amount ?? NaN);
  if (rc === "0000" || rc === "00" || st === "paid" || st === "success" || st === "completed")
    return { reference, transactionId, status: "COMPLETED", amountCents: Number.isFinite(amount) ? Math.round(amount * 100) : null };
  if (st.includes("cancel")) return { reference, transactionId, status: "CANCELLED", amountCents: null };
  if (["01", "02", "03", "04", "05", "06"].includes(rc) || ["failed", "declined", "insufficient", "timeout", "expired"].some((k) => st.includes(k)))
    return { reference, transactionId, status: "FAILED", amountCents: null };
  return { reference, transactionId, status: "PENDING", amountCents: null };
}

export { MOMO_LABEL, fmtGH };

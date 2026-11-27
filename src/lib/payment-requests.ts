// ── Online payment requests — engine (Hubtel | Paystack | Flutterwave + SIMULATED) ──
// Shared core behind the payments module API, the gateway webhooks and the
// public /pay status page:
//   • createRequest      — validate + persist + call the gateway (or simulate)
//   • checkRequest       — poll the gateway (checkout token / reference)
//   • applyCompletion    — idempotently post a completed request to the
//                          right ledger (invoice, daily items FIFO, per-item
//                          balance, wallet) + receipt SMS + audit event
// SIMULATED mode (no gateway env credentials) mirrors the SMS gateway
// approach: requests are created locally, the public /pay page carries a
// "simulate payment" button, and everything else behaves identically.
// Wave 78 — multi-gateway: every request names its provider (HUBTEL, the
// Ghana MoMo veteran, or PAYSTACK / FLUTTERWAVE hosted checkouts). With no
// credentials at all any labelled gateway runs in sandbox mode.

import { PrismaClient, Payment, PaymentRequest } from "@prisma/client";
import { collectDailyItems, creditItemBalance } from "@/lib/daily-billing";
import { sendAutomated, sendSms } from "@/lib/sms";
import { nextReceiptNo } from "@/lib/codes";
import {
  hubtelPaymentsConfigured, hubtelReceiveMoney, hubtelCreateCheckout, hubtelCheckoutStatus,
  hubtelTransactionStatus, hubtelWebhookUrl, hubtelCallbackBase, newClientReference, detectMomoChannel,
} from "@/lib/hubtel";
import { paystackConfigured, paystackInitTransaction, paystackVerify } from "@/lib/paystack";
import { flutterwaveConfigured, flwInitPayment, flwVerify } from "@/lib/flutterwave";
import { toGhanaIntl } from "@/lib/phone";

export const PAYMENT_PURPOSES = ["INVOICE", "DAILY_ITEMS", "ITEM_BALANCE", "WALLET", "SHOP_ORDER"] as const;
export type PaymentPurpose = (typeof PAYMENT_PURPOSES)[number];

export const PURPOSE_LABELS: Record<string, string> = {
  INVOICE: "Termly fees",
  DAILY_ITEMS: "Daily fee items",
  ITEM_BALANCE: "Fee-item advance",
  WALLET: "Wallet top-up",
  SHOP_ORDER: "School shop order",
};

const MOMO_LABEL: Record<string, string> = {
  "mtn-gh": "MTN MoMo",
  "vodafone-gh": "Telecel Cash",
  "tigo-gh": "AT Money",
};

const fmtGH = (cents: number) => `₵${(cents / 100).toFixed(2)}`;

type RequestItem = { serviceId: string; amountCents: number };

export function paymentsProvider(): "HUBTEL" | "SIMULATED" {
  return hubtelPaymentsConfigured() ? "HUBTEL" : "SIMULATED";
}

// ── wave 78 — multi-gateway provider model ─────────────────────
export const PAYMENT_PROVIDER_LABELS: Record<string, string> = {
  HUBTEL: "Hubtel", PAYSTACK: "Paystack", FLUTTERWAVE: "Flutterwave", SIMULATED: "Sandbox",
};
export const PAYMENT_PROVIDERS = ["HUBTEL", "PAYSTACK", "FLUTTERWAVE"] as const;

/** Gateways this deployment has live credentials for (empty → sandbox demo). */
export function availablePaymentProviders(): string[] {
  const out: string[] = [];
  if (hubtelPaymentsConfigured()) out.push("HUBTEL");
  if (paystackConfigured()) out.push("PAYSTACK");
  if (flutterwaveConfigured()) out.push("FLUTTERWAVE");
  return out;
}

export type ResolvedProvider =
  | { ok: true; provider: string; simulated: boolean }
  | { ok: false; error: string };

/** Which gateway a request should ride. An explicit choice must be configured
 *  — unless the deployment has NO credentials at all, in which case any
 *  labelled gateway runs in SIMULATED mode so schools can rehearse every
 *  flow (same sandbox approach as the SMS gateways). */
export function resolvePaymentProvider(requested?: string | null): ResolvedProvider {
  const req = String(requested ?? "").trim().toUpperCase();
  if ((PAYMENT_PROVIDERS as readonly string[]).includes(req)) {
    if (availablePaymentProviders().includes(req)) return { ok: true, provider: req, simulated: false };
    if (availablePaymentProviders().length === 0) return { ok: true, provider: req, simulated: true };
    return {
      ok: false,
      error: `${PAYMENT_PROVIDER_LABELS[req]} keys are not configured on this deployment — this school collects through ${PAYMENT_PROVIDER_LABELS[availablePaymentProviders()[0]]}`,
    };
  }
  const first = availablePaymentProviders()[0];
  if (first) return { ok: true, provider: first, simulated: false };
  return { ok: true, provider: req === "SIMULATED" ? "SIMULATED" : "HUBTEL", simulated: true };
}

/** A request minted in SIMULATED mode (SIM- token/txn) — it never polls a
 *  real gateway and is the only kind the public page may "pay" by button. */
export function isSimulatedRequest(r: { checkoutToken: string | null; providerTxnId: string | null }): boolean {
  return !!(r.checkoutToken?.startsWith("SIM-") || r.providerTxnId?.startsWith("SIM-"));
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
  shopOrderId?: string | null;
  items?: RequestItem[];
  walletKind?: "PREPAID" | "TRANSPORT" | "ITEM" | null;
  amountCents: number;
  method: "MOMO_PROMPT" | "PAYMENT_LINK";
  payerMsisdn?: string;
  channel?: string | null;
  provider?: string | null; // wave 78 — explicit gateway choice (HUBTEL | PAYSTACK | FLUTTERWAVE)
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
  if (amount < 50) return { ok: false, error: "Payments start at a minimum of ₵0.50 per transaction" };

  // wave 78 — resolve the gateway BEFORE anything else so a misconfigured
  // choice fails fast with a clear message and never leaves a dangling row
  const resolved = resolvePaymentProvider(input.provider);
  if (!resolved.ok) return { ok: false, error: resolved.error };
  const provider = resolved.provider;

  // purpose validation + description
  let itemsJson: RequestItem[] | null = null;
  let description = input.description ?? "";
  let invoiceId: string | null = null;
  let walletKind: string | null = null;
  let shopOrderId: string | null = null;

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
  } else if (input.purpose === "SHOP_ORDER") {
    // wave 77 — settle a parent-placed school-shop order (parent portal).
    if (!input.shopOrderId) return { ok: false, error: "Choose the order this payment settles" };
    const order = await db.shopOrder.findFirst({ where: { id: input.shopOrderId, schoolId: input.schoolId } });
    if (!order) return { ok: false, error: "Shop order not found" };
    if (order.status === "CANCELLED") return { ok: false, error: "This order was cancelled" };
    if (order.status !== "PENDING_PAYMENT") return { ok: false, error: "This order is already settled" };
    if (input.studentId && input.studentId !== order.studentId) return { ok: false, error: "The order belongs to a different learner" };
    if (amount !== order.totalCents) return { ok: false, error: `The order total is ${fmtGH(order.totalCents)} — pay that exact amount` };
    shopOrderId = order.id;
    input.studentId = order.studentId;
    description = description || `School shop order ${order.orderNo}`;
  } else {
    return { ok: false, error: "Unknown purpose" };
  }

  // payer
  const payerMsisdn = toGhanaIntl(input.payerMsisdn || student.guardianPhone);
  if (!payerMsisdn) return { ok: false, error: "A valid Ghana mobile-money number is required" };
  const payerName = student.guardianName || `${student.firstName} ${student.lastName}`;

  // Paystack & Flutterwave collect on their hosted checkouts — direct MoMo
  // prompts are a Hubtel capability, so non-Hubtel choices always ride links.
  const method = !resolved.simulated && provider !== "HUBTEL" ? "PAYMENT_LINK"
    : input.method === "PAYMENT_LINK" ? "PAYMENT_LINK" : "MOMO_PROMPT";
  if (method === "MOMO_PROMPT" && !resolved.simulated && provider === "HUBTEL" && !hubtelWebhookUrl())
    return { ok: false, error: "MoMo prompts need HUBTEL_CALLBACK_BASE (the public URL of this deployment) — use a payment link instead" };

  const clientReference = newClientReference(
    provider === "PAYSTACK" ? "PSK" : provider === "FLUTTERWAVE" ? "FLW" : provider === "SIMULATED" ? "SIM" : "HTL",
  );
  const channel = method === "MOMO_PROMPT" ? (input.channel || detectMomoChannel(payerMsisdn).channel) : null;

  // call the gateway FIRST (live mode) so a gateway failure never leaves a dangling row
  let providerTxnId: string | null = null;
  let checkoutToken: string | null = null;
  let checkoutUrl: string | null = null;
  if (resolved.simulated) {
    // SIMULATED (any labelled gateway) — local link behaves like the hosted checkout
    if (method === "PAYMENT_LINK") {
      const payUrl = publicPayUrl({ clientReference });
      if (payUrl) checkoutUrl = payUrl;
      else checkoutUrl = `/pay/${clientReference}`; // relative fallback (client resolves origin)
      checkoutToken = `SIM-${clientReference}`;
    } else {
      providerTxnId = `SIM-${clientReference}`;
    }
  } else if (provider === "HUBTEL") {
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
  } else if (provider === "PAYSTACK") {
    // hosted checkout — card, bank transfer & all Ghana MoMo wallets on one page
    const payUrl = publicPayUrl({ clientReference });
    if (!payUrl) return { ok: false, error: "Paystack checkouts need HUBTEL_CALLBACK_BASE (the public URL of this deployment)" };
    const r = await paystackInitTransaction({
      email: (student.guardianEmail || `${clientReference.toLowerCase()}@parents.nyansapo.invalid`).trim(),
      amountCents: amount,
      reference: clientReference,
      callbackUrl: `${payUrl}?done=1`,
      description: description.slice(0, 120),
    });
    if (!r.ok) return { ok: false, error: r.error ?? "Paystack rejected the checkout" };
    checkoutUrl = r.data?.authorizationUrl ?? null;
    checkoutToken = r.data?.accessCode ?? null;
    providerTxnId = clientReference; // Paystack tracks transactions by our reference
  } else if (provider === "FLUTTERWAVE") {
    const payUrl = publicPayUrl({ clientReference });
    if (!payUrl) return { ok: false, error: "Flutterwave checkouts need HUBTEL_CALLBACK_BASE (the public URL of this deployment)" };
    const r = await flwInitPayment({
      txRef: clientReference,
      amountCents: amount,
      redirectUrl: `${payUrl}?done=1`,
      payerName,
      payerPhone: payerMsisdn,
      description: description.slice(0, 120),
    });
    if (!r.ok) return { ok: false, error: r.error ?? "Flutterwave rejected the checkout" };
    checkoutUrl = r.data?.link ?? null;
    providerTxnId = clientReference;
  }

  const request = await db.paymentRequest.create({
    data: {
      schoolId: input.schoolId, studentId: input.studentId, purpose: input.purpose,
      invoiceId, items: itemsJson as any, walletKind, shopOrderId,
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
/** Poll the request's gateway (live) for the authoritative status; SIMULATED
 *  requests stay at PENDING until the public page's simulate button (or a
 *  sandbox webhook) completes them. */
export async function checkPaymentRequest(db: PrismaClient, request: PaymentRequest): Promise<PaymentRequest> {
  if (request.status !== "PENDING") return request;
  await db.paymentRequest.update({ where: { id: request.id }, data: { lastCheckedAt: new Date() } });

  if (!isSimulatedRequest(request)) {
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
    } else if (request.provider === "PAYSTACK") {
      const r = await paystackVerify(request.clientReference);
      if (r.ok) {
        const st = r.data!.status;
        if (st === "COMPLETED") return applyCompletion(db, request.id, "status check");
        if (st === "FAILED") return failRequest(db, request.id, "Declined / failed on Paystack");
        // "abandoned" stays pending — the payer can still come back and finish
      }
    } else if (request.provider === "FLUTTERWAVE") {
      const r = await flwVerify(request.clientReference);
      if (r.ok) {
        const st = r.data!.status;
        if (st === "COMPLETED") return applyCompletion(db, request.id, "status check");
        if (st === "FAILED") return failRequest(db, request.id, "Declined / failed on Flutterwave");
      }
    }
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
    const providerLabel = PAYMENT_PROVIDER_LABELS[request.provider] ?? "MoMo";
    const method = `MOMO_${(request.channel ?? "").split("-")[0]?.toUpperCase() || "GH"}`;
    const byName = `${providerLabel} — ${request.payerName}`;
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
    } else if (request.purpose === "SHOP_ORDER" && request.shopOrderId) {
      // wave 77 — school-shop order: receipt + stock decrement + CAS to PAID.
      // The order transition is an atomic compare-and-set like the request
      // claim, so a webhook and a status poll can never both fulfil it.
      const order = await db.shopOrder.findUnique({ where: { id: request.shopOrderId }, include: { items: true, student: true } });
      if (order) {
        const orderClaim = await db.shopOrder.updateMany({
          where: { id: order.id, status: "PENDING_PAYMENT" },
          data: { status: "PAID", paymentMethod: "ONLINE", paidAt: new Date(), paidByName: byName },
        });
        if (orderClaim.count) {
          payment = await db.payment.create({
            data: {
              receiptNo: await nextReceiptNo(), studentId: order.studentId, category: "SHOP",
              schoolId: request.schoolId, amountCents: request.amountCents,
              method, reference: request.clientReference,
              receivedByName: byName, paidAt: new Date(),
            },
          });
          // tracked stock lines — tolerate a concurrent sale (logged, never blocks the learner's order)
          let stockMisses = 0;
          for (const line of order.items) {
            if (!line.itemId) continue;
            const dec = await db.shopItem.updateMany({
              where: { id: line.itemId, stock: { not: null, gte: line.qty } },
              data: { stock: { decrement: line.qty } },
            });
            if (!dec.count) stockMisses++;
          }
          if (stockMisses) await logEvent(db, request.id, "ERROR", { stockMisses }, `${stockMisses} shop line(s) short of stock at fulfilment — restock from the shop console`);
          note = `Shop order ${order.orderNo} — ${fmtGH(request.amountCents)}, ${order.items.length} line(s)`;
          // guardian alert — the order confirmation (gated on evtShopOrders)
          const shopSmsSetting = await db.smsSetting.findUnique({ where: { schoolId: request.schoolId }, select: { evtShopOrders: true } });
          if (shopSmsSetting?.evtShopOrders && order.student) {
            await sendSms({
              db, schoolId: request.schoolId, studentId: order.studentId,
              recipientName: order.parentName || order.student.guardianName,
              recipientPhone: order.student.guardianPhone,
              body: `Shop order ${order.orderNo} confirmed — ${fmtGH(request.amountCents)}${payment ? `, receipt ${payment.receiptNo}` : ""}. ${order.items.map((i) => `${i.qty}× ${i.name}`).join(", ")} will be delivered to ${order.student.firstName} at school. Thank you.`,
              category: "SHOP", sentByName: "School shop",
            });
          }
        } else {
          note = `Shop order ${order.orderNo} was already settled — payment link expired unused`;
        }
      } else {
        note = "Shop order not found at completion";
      }
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
          recipientPhone: student.guardianPhone, sentByName: `${providerLabel} payments`,
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

export { MOMO_LABEL, fmtGH, isSimulatedRequest };

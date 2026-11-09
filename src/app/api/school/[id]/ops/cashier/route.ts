import { NextRequest, NextResponse } from "next/server";
import { sortClassrooms } from "@/lib/classes";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { paymentMethodLabel, resolveTransportFare, effectivePrice, voidPaymentCore, type CollectMethodPart } from "@/lib/daily-billing";
import { dailyServicesReceipt, invoicePaymentReceipt } from "@/lib/receipt-payload";

// ── GET /api/school/[id]/ops/cashier ──
//  ?action=student-info → the fee-collection portal payload for ONE learner:
//  every enabled fee module as a conditional card (iEduPro fee_collection
//  portal + user rules): optional items toggle, admin-approved lunch opt-outs
//  disable the card, 100% discounts hide it, transport riders get the transport
//  card, open invoices join as termly cards.
//  (default) → cashier console stats (iEduPro parity) ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const url = new URL(req.url);
  const action = url.searchParams.get("action") ?? "";
  // split (wave 10): the console report needs cashier.reports; the collection
  // portal's student-info accepts any collecting permission (daily.collect
  // covers classroom teachers — CI3 hybrid mode)
  const need = action === "student-info" ? ["cashier.reports", "fees.collect", "daily.collect"] : ["cashier.reports"];
  const auth = await opsAuth(req, schoolId, "GET", { perms: need });
  if (!auth.ok) return auth.res;

  if (action === "student-info") return studentInfo(req, schoolId, url);

  const from = url.searchParams.get("from") ? new Date(url.searchParams.get("from")!) : new Date(new Date().toDateString());
  const toSrc = url.searchParams.get("to") ? new Date(url.searchParams.get("to")!) : new Date();
  const to = new Date(toSrc.getTime() + 864e5 - 1); // end of day
  const collector = url.searchParams.get("collector") ?? undefined; // "all" or receivedByName
  const inRange = { paidAt: { gte: from, lte: to } };

  // ── payments in window (cashier takings: invoice + daily + transport counters) ──
  const payments = await db.payment.findMany({
    where: { schoolId, voided: false, paymentType: "PAYMENT", ...inRange, ...(collector && collector !== "all" ? { receivedByName: collector } : {}) },
    include: { student: true, invoice: true, dailyCharges: { include: { service: true } } },
    orderBy: { paidAt: "desc" },
  });

  const total = payments.reduce((s, p) => s + p.amountCents, 0);
  const studentsServed = new Set(payments.map((p) => p.studentId)).size;

  // ── method breakdown (SPLIT payments expand into their parts, e.g. part cash + part MoMo) ──
  const byMethod: Record<string, number> = {};
  payments.forEach((p) => {
    if (p.method === "SPLIT" && Array.isArray(p.splitJson)) {
      (p.splitJson as CollectMethodPart[]).forEach((m) => { byMethod[m.method] = (byMethod[m.method] ?? 0) + m.amountCents; });
    } else {
      byMethod[p.method] = (byMethod[p.method] ?? 0) + p.amountCents;
    }
  });

  // ── daily-fee type breakdown: collected (linked to a service) vs outstanding (all-time OWED) ──
  const collectedByService: Record<string, number> = {};
  payments.forEach((p) => {
    if (p.dailyCharges.length) {
      p.dailyCharges.forEach((c) => { collectedByService[c.service.name] = (collectedByService[c.service.name] ?? 0) + c.amountCents; });
    } else if (p.category === "DAILY") {
      collectedByService["Other daily"] = (collectedByService["Other daily"] ?? 0) + p.amountCents;
    }
  });
  const owedCharges = await db.dailyCharge.findMany({ where: { schoolId, status: "OWED" }, include: { service: true } });
  const outstandingByService: Record<string, number> = {};
  owedCharges.forEach((c) => { outstandingByService[c.service.name] = (outstandingByService[c.service.name] ?? 0) + c.amountCents; });

  // invoice-side collected in window (payments not linked to daily charges)
  const invoiceCollected = payments.filter((p) => p.category === "INVOICE").reduce((s, p) => s + p.amountCents, 0);
  const dailyCollected = payments.filter((p) => p.category === "DAILY" || p.dailyCharges.length).reduce((s, p) => s + p.amountCents, 0);
  const transportCollected = payments.filter((p) => p.category === "TRANSPORT").reduce((s, p) => s + p.amountCents, 0);

  // ── collection performance by collector (unfiltered by collector param) ──
  const allPayments = await db.payment.findMany({
    where: { schoolId, voided: false, paymentType: "PAYMENT", ...inRange },
    select: { receivedByName: true, amountCents: true },
  });
  const perf: Record<string, { name: string; total: number; count: number }> = {};
  allPayments.forEach((p) => {
    const k = p.receivedByName ?? "Unattributed";
    perf[k] ??= { name: k, total: 0, count: 0 };
    perf[k].total += p.amountCents;
    perf[k].count++;
  });
  const grand = allPayments.reduce((s, p) => s + p.amountCents, 0) || 1;
  const performance = Object.values(perf)
    .map((v) => ({ ...v, share: Math.round((v.total / grand) * 100) }))
    .sort((a, b) => b.total - a.total);

  // ── roster of collectors (for the filter dropdown) ──
  const collectors = Object.keys(perf).sort();

  // ── CI3 my_collections parity: detailed rows (per-fee-type split per
  //    transaction) + class summary, for the Detailed / Class Summary reports ──
  const classroomById = new Map<string, string>();
  (sortClassrooms(await db.classroom.findMany({ where: { schoolId }, select: { id: true, name: true, gradeLevel: true } }))).forEach((c) => classroomById.set(c.id, c.name));
  const classByStudent = new Map<string, string>();
  (await db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, select: { studentId: true, classroomId: true } })).forEach((e) => classByStudent.set(e.studentId, classroomById.get(e.classroomId) ?? ""));
  const rows = payments.map((p) => {
    const byService: Record<string, number> = {};
    if (p.dailyCharges.length) p.dailyCharges.forEach((c) => { byService[c.service.name] = (byService[c.service.name] ?? 0) + c.amountCents; });
    if (p.category === "INVOICE") byService["Termly fees"] = p.amountCents;
    else if (!p.dailyCharges.length && p.category === "DAILY") byService["Other daily"] = p.amountCents;
    else if (p.category === "POS") byService["Store / tuck"] = p.amountCents;
    else if (p.category === "TRANSPORT") byService["Transport"] = p.amountCents;
    else if (!p.dailyCharges.length) byService[p.category === "INVOICE" ? "Termly fees" : "Other"] = p.amountCents;
    return {
      id: p.id, date: p.paidAt, student: p.student ? `${p.student.firstName} ${p.student.lastName}` : "—",
      classroom: p.student ? (classByStudent.get(p.student.id) ?? "") : "",
      byService, total: p.amountCents, method: paymentMethodLabel(p), receiptNo: p.receiptNo,
      collector: p.receivedByName ?? "Unattributed",
    };
  });
  const serviceNames = [...new Set(rows.flatMap((r) => Object.keys(r.byService)))];
  const classAgg: Record<string, { classroom: string; students: Set<string>; totals: Record<string, number>; total: number; count: number }> = {};
  rows.forEach((r) => {
    const key = r.classroom || "—";
    classAgg[key] ??= { classroom: key, students: new Set(), totals: {}, total: 0, count: 0 };
    classAgg[key].students.add(r.student);
    classAgg[key].count++;
    classAgg[key].total += r.total;
    Object.entries(r.byService).forEach(([svc, amt]) => { classAgg[key].totals[svc] = (classAgg[key].totals[svc] ?? 0) + amt; });
  });
  const classSummary = Object.values(classAgg)
    .map((c) => ({ classroom: c.classroom, students: c.students.size, totals: c.totals, total: c.total, count: c.count }))
    .sort((a, b) => b.total - a.total);
  const serviceTotals: Record<string, number> = {};
  rows.forEach((r) => Object.entries(r.byService).forEach(([svc, amt]) => { serviceTotals[svc] = (serviceTotals[svc] ?? 0) + amt; }));

  return NextResponse.json({
    period: { from: from.toISOString().slice(0, 10), to: toSrc.toISOString().slice(0, 10) },
    collector: collector ?? "all",
    totals: { collected: total, transactions: payments.length, studentsServed },
    split: { invoiceCollected, dailyCollected, transportCollected },
    byMethod,
    feeBreakdown: Object.keys({ ...collectedByService, ...outstandingByService }).map((service) => ({
      service,
      collected: collectedByService[service] ?? 0,
      outstanding: outstandingByService[service] ?? 0,
    })).sort((a, b) => (b.collected + b.outstanding) - (a.collected + a.outstanding)),
    performance,
    collectors,
    recent: payments.slice(0, 12).map((p) => ({
      id: p.id, receiptNo: p.receiptNo, amount: p.amountCents, method: paymentMethodLabel(p), paidAt: p.paidAt,
      student: p.student ? `${p.student.firstName} ${p.student.lastName}` : "—",
      category: p.category, receivedByName: p.receivedByName,
    })),
    // CI3 my_collections report payload
    report: {
      serviceNames,
      serviceTotals,
      grandTotal: total,
      count: rows.length,
      rows: rows.map((r) => ({ ...r, date: r.date.toISOString() })),
      classSummary,
    },
    // learner roster for the student-ledger lookup (CI3 cashier sidebar parity)
    roster: (await db.enrollment.findMany({
      where: { schoolId, status: "ACTIVE" },
      select: { student: { select: { id: true, firstName: true, lastName: true, nyansapoIndex: true } }, classroom: { select: { name: true } } },
      orderBy: { rollNo: "asc" },
    })).map((e) => ({ id: e.student.id, name: `${e.student.firstName} ${e.student.lastName}`, index: e.student.nyansapoIndex, classroom: e.classroom.name })),
  });
}

// ── POST /api/school/[id]/ops/cashier ──
//    action=ledger          student ledger lookup (cashier.reports)
//    action=receipt-payload reprint one counter receipt (any collecting right)
//    action=void-payment    reverse a payment end-to-end (collecting right) ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action = String(body.action ?? "");
  const need = action === "void-payment"
    ? ["fees.collect", "daily.collect"]
    : action === "receipt-payload"
      ? ["cashier.reports", "fees.collect", "daily.collect"]
      : ["cashier.reports"];
  const auth = await opsAuth(req, schoolId, "POST", { perms: need });
  if (!auth.ok) return auth.res;
  const user = auth.user;

  /* ── reprint one receipt (portal Recent collections → Print) ── */
  if (action === "receipt-payload") {
    const p = await db.payment.findFirst({ where: { id: String(body.paymentId ?? ""), schoolId, voided: false } });
    if (!p) return NextResponse.json({ error: "Receipt not found" }, { status: 404 });
    try {
      const receipt = p.category === "INVOICE"
        ? await invoicePaymentReceipt(db, p.id)
        : await dailyServicesReceipt(db, p.id);
      return NextResponse.json({ receipt });
    } catch (e: any) {
      return NextResponse.json({ error: e.message ?? "This receipt cannot be reconstructed" }, { status: 400 });
    }
  }

  /* ── void (CI3 delete_transaction parity): charges re-arm as arrears,
     advances/wallet/invoice figures reverse, audit row written ── */
  if (action === "void-payment") {
    const p = await db.payment.findFirst({ where: { id: String(body.paymentId ?? ""), schoolId } });
    if (!p) return NextResponse.json({ error: "Payment not found" }, { status: 404 });
    if (p.voided) return NextResponse.json({ error: "This payment is already voided" }, { status: 400 });
    if (p.paymentType !== "PAYMENT") return NextResponse.json({ error: "Only payments can be voided (not refunds/credits)" }, { status: 400 });
    if (p.category === "POS") return NextResponse.json({ error: "Store purchases are reversed from the Store module (stock is re-stocked there)" }, { status: 400 });
    if (!p.studentId) return NextResponse.json({ error: "Payment has no linked learner" }, { status: 400 });
    try {
      const out = await voidPaymentCore(db, p, {
        byName: user.fullName, byRole: user.role,
        reason: body.reason ? String(body.reason).slice(0, 200) : null,
      });
      return NextResponse.json({ ok: true, receiptNo: p.receiptNo, ...out });
    } catch (e: any) {
      return NextResponse.json({ error: e.message ?? "Reversal failed" }, { status: 400 });
    }
  }

  if (body.action !== "ledger") return NextResponse.json({ error: "Unknown action" }, { status: 400 });
  const student = await db.student.findUnique({
    where: { id: String(body.studentId ?? "") },
    include: { enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: true } } },
  });
  if (!student || !await db.enrollment.count({ where: { studentId: student.id, schoolId } }))
    return NextResponse.json({ error: "Learner not found in this school" }, { status: 404 });
  const invoices = await db.invoice.findMany({
    where: { studentId: student.id, schoolId },
    include: { items: true, term: { include: { academicYear: true } } },
    orderBy: { createdAt: "asc" },
  });
  const payments = await db.payment.findMany({
    where: { studentId: student.id, schoolId, voided: false },
    orderBy: { paidAt: "asc" },
  });
  type Row = { date: string; kind: "DEBIT" | "CREDIT"; description: string; reference: string; amount: number; balance: number };
  const rows: Row[] = [];
  invoices.forEach((i) => rows.push({
    date: i.createdAt.toISOString(), kind: "DEBIT",
    description: `${i.term.name} ${i.term.academicYear.name} — ${i.items.map((x) => x.name).join(", ") || "Term bill"}`,
    reference: i.invoiceNo, amount: i.totalCents - i.discountCents, balance: 0,
  }));
  payments.forEach((p) => rows.push({
    date: p.paidAt.toISOString(), kind: "CREDIT",
    description: `${p.category === "DAILY" ? "Daily services" : p.category === "POS" ? "Store purchase" : p.category === "TRANSPORT" ? "Transport fare" : "Fees payment"} · ${paymentMethodLabel(p)}`,
    reference: p.receiptNo, amount: p.amountCents, balance: 0,
  }));
  rows.sort((a, b) => (a.date < b.date ? -1 : 1));
  let bal = 0;
  rows.forEach((r) => { bal += r.kind === "DEBIT" ? r.amount : -r.amount; r.balance = bal; });
  return NextResponse.json({
    student: { name: `${student.firstName} ${student.lastName}`, index: student.nyansapoIndex, classroom: student.enrollments[0]?.classroom?.name ?? null, guardian: student.guardianName, guardianPhone: student.guardianPhone },
    rows,
    closing: bal,
  });
}

/* ─────────────────────────────────────────────────────────────
   FEE COLLECTION PORTAL — student-info (wave 10)
   The CI3 fee_collection_portal parity, one learner at a time:

   daily cards    every ACTIVE daily-service enrolment; OPTIONAL items
                  (optInDaily) render as toggles, admin-approved OPT_OUT
                  exceptions disable the card, 100% discounts hide it,
                  partial discounts show the subsidised rate
   transport card active route assignment → one-way fare card with
                  per-stop overrides (in/out/both usage at collect time)
   invoice cards  open termly invoices (not written off) with their dues
   wallet         per-item prepaid balances + arrears; payments settle
                  arrears FIFO first and park the excess on the SAME
                  item as advance (iEduPro apply_payment)
   ───────────────────────────────────────────────────────────── */
async function studentInfo(req: NextRequest, schoolId: string, url: URL) {
  const studentId = url.searchParams.get("studentId");
  if (!studentId) return NextResponse.json({ error: "studentId is required" }, { status: 400 });
  const dateStr = url.searchParams.get("date") ?? new Date().toISOString().slice(0, 10);
  if (!/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return NextResponse.json({ error: "date must be YYYY-MM-DD" }, { status: 400 });
  const date = new Date(`${dateStr}T00:00:00`);
  const dayStart = new Date(date); dayStart.setHours(0, 0, 0, 0);
  const dayEnd = new Date(date); dayEnd.setHours(23, 59, 59, 999);

  const enrollment = await db.enrollment.findFirst({
    where: { studentId, status: "ACTIVE", classroom: { schoolId } },
    include: { classroom: true, student: true },
    orderBy: { admissionDate: "asc" },
  });
  if (!enrollment) return NextResponse.json({ error: "Learner not found in this school" }, { status: 404 });
  const student = enrollment.student;
  const classroomId = enrollment.classroomId;

  /* attendance today (for the header chip) */
  const attSession = await db.attendanceSession.findFirst({
    where: { schoolId, classroomId, date: { gte: dayStart, lte: dayEnd } },
    include: { records: { where: { studentId } } },
  });
  const attRecord = attSession?.records[0] ?? null;

  /* this week (Mon–Sun) for WEEKLY items */
  const weekStart = new Date(dayStart);
  weekStart.setDate(weekStart.getDate() - ((weekStart.getDay() + 6) % 7));
  const weekEnd = new Date(weekStart); weekEnd.setDate(weekEnd.getDate() + 7);

  /* enrolments → item rows */
  const enrollRows = await db.dailyServiceEnrollment.findMany({
    where: { studentId, active: true, service: { schoolId, active: true } },
    include: { service: { include: { classRates: true } } },
  });

  /* per-item balances + arrears */
  const balances = await db.serviceBalance.findMany({ where: { studentId } });
  const balByService = new Map(balances.map((b) => [b.serviceId, b.prepaidCents]));
  const owedRows = await db.dailyCharge.findMany({ where: { studentId, status: "OWED" }, include: { service: { select: { category: true } } } });
  const owedByService = new Map<string, number>();
  owedRows.forEach((c) => owedByService.set(c.serviceId, (owedByService.get(c.serviceId) ?? 0) + c.amountCents));
  const weekCharges = await db.dailyCharge.findMany({
    where: { studentId, date: { gte: weekStart, lt: weekEnd } },
    include: { service: true },
  });
  const weeklyBilled = new Set<string>();
  weekCharges.forEach((c) => { if (c.service.chargeMode === "WEEKLY" && c.status !== "WAIVED") weeklyBilled.add(c.serviceId); });
  const todayCharges = weekCharges.filter((c) => c.date >= dayStart && c.date <= dayEnd);
  const todayByService = new Map<string, { amountCents: number; status: string; usage: string | null; paymentId: string | null }>();
  todayCharges.forEach((c) => todayByService.set(c.serviceId, { amountCents: c.amountCents, status: c.status, usage: c.usage, paymentId: c.paymentId }));

  /* meal opt-in/opt-out exceptions for the date (PENDING parent requests + APPROVED) */
  const exceptions = await db.dailyMealException.findMany({
    where: { studentId, date: { gte: dayStart, lte: dayEnd }, status: { in: ["PENDING", "APPROVED"] } },
    include: { service: true },
  });
  const optOutByService = new Map<string, { status: string; reason: string | null }>(); // OPT_OUT
  const optInByService = new Map<string, { status: string; reason: string | null }>(); // OPT_IN
  exceptions.forEach((e) => {
    if (e.kind === "OPT_OUT") optOutByService.set(e.serviceId, { status: e.status, reason: e.reason });
    else optInByService.set(e.serviceId, { status: e.status, reason: e.reason });
  });

  /* approved discounts (DAILY_SERVICE scope) — CI3 get_student_discount_for_date */
  const discountApps = await db.discountApplication.findMany({
    where: {
      studentId, schoolId, status: "APPROVED",
      OR: [
        { serviceId: null, discount: { isNot: null } },
        { serviceId: { not: null } },
      ],
    },
    include: { discount: true },
  });
  const dailyDiscounts = discountApps.filter((a) => {
    if (a.serviceId) return true; // service-scoped
    const d = a.discount;
    return !!d && d.scope === "DAILY_SERVICE"; // generic
  });

  const discountFor = (serviceId: string, priceCents: number) => {
    // specific service application first, then a generic DAILY_SERVICE discount
    const specific = dailyDiscounts.find((a) => a.serviceId === serviceId);
    const generic = dailyDiscounts.find((a) => !a.serviceId);
    const pick = specific ?? generic;
    if (!pick) return { cents: 0, label: null as string | null, hidden: false };
    const method = pick.discount?.type ?? "PERCENT";
    const raw = pick.valueOverride ?? pick.discount?.value ?? 0;
    const cents = method === "PERCENT" ? Math.round((priceCents * Math.min(100, Math.max(0, raw))) / 100) : Math.min(priceCents, raw);
    return {
      cents,
      label: pick.discount?.name ?? "Discount",
      hidden: cents >= priceCents && priceCents > 0, // 100% → the card will not load
    };
  };

  /* transport assignment → the transport card */
  const fare = await resolveTransportFare(db, studentId);
  const transportOwed = owedRows.filter((c) => c.service?.category === "TRANSPORT").reduce((a, c) => a + c.amountCents, 0);
  const wallet = await db.studentWallet.findUnique({ where: { studentId } });

  /* open termly invoices */
  const invoices = await db.invoice.findMany({
    where: { studentId, schoolId, writtenOff: false, status: { in: ["UNPAID", "PARTIAL", "OVERDUE"] } },
    include: { items: true, term: true },
    orderBy: { createdAt: "desc" },
  });

  /* recent payments (context strip) */
  const recentPayments = await db.payment.findMany({
    where: { studentId, voided: false, paymentType: "PAYMENT" },
    orderBy: { paidAt: "desc" },
    take: 6,
  });

  /* build the card list — ordering: daily items, transport, invoices */
  const cards = enrollRows
    .map((se) => {
      const svc = se.service;
      const classRate = svc.classRates.find((r) => r.classroomId === classroomId)?.priceCents ?? null;
      const price = effectivePrice(svc, se, classRate);
      const disc = discountFor(svc.id, price);
      const optOut = optOutByService.get(svc.id) ?? null;
      const optIn = optInByService.get(svc.id) ?? null;
      const optional = svc.optInDaily;
      return {
        kind: "DAILY" as const,
        serviceId: svc.id,
        name: svc.name,
        category: svc.category,
        priceCents: price,
        discountedCents: Math.max(0, price - disc.cents),
        discountCents: disc.cents,
        discountLabel: disc.label,
        hidden: disc.hidden, // 100% discount → card not loaded
        optional, // optInDaily → rendered as a toggle
        optOut, // APPROVED → card disabled (admin approved the lunch skip)
        optedIn: optional ? optIn : null, // APPROVED → opted in for the day
        arrearsCents: owedByService.get(svc.id) ?? 0,
        prepaidCents: balByService.get(svc.id) ?? 0,
        weekly: svc.chargeMode === "WEEKLY",
        weeklyBilledThisWeek: svc.chargeMode === "WEEKLY" && weeklyBilled.has(svc.id),
        weekdays: svc.weekdays,
        today: todayByService.get(svc.id) ?? null,
        chargeMode: svc.chargeMode,
      };
    })
    .filter((c) => !c.hidden); // hidden cards are simply not loaded

  const transportCard = fare ? {
    kind: "TRANSPORT" as const,
    routeName: fare.routeName,
    stopName: fare.stopName,
    onewayCents: fare.onewayCents,
    bothCents: fare.onewayCents * 2,
    arrearsCents: transportOwed,
    transportWalletCents: wallet?.transportCents ?? 0,
    todayUsage: (todayByService.get("__transport__")?.usage) ?? null,
  } : null;

  const invoiceCards = invoices.map((inv) => ({
    kind: "INVOICE" as const,
    invoiceId: inv.id,
    invoiceNo: inv.invoiceNo,
    term: inv.term?.name ?? null,
    totalCents: inv.totalCents,
    paidCents: inv.paidCents,
    dueCents: Math.max(0, inv.totalCents - inv.paidCents),
    dueDate: inv.dueDate ? inv.dueDate.toISOString().slice(0, 10) : null,
    status: inv.status,
    items: inv.items.map((it) => ({ name: it.name, amountCents: it.amountCents })),
  }));

  return NextResponse.json({
    date: dateStr,
    student: {
      id: student.id,
      name: `${student.firstName} ${student.lastName}`,
      index: student.nyansapoIndex,
      classroom: enrollment.classroom.name,
      classroomId,
      guardianName: student.guardianName,
      guardianPhone: student.guardianPhone,
    },
    attendance: attRecord ? { status: attRecord.status, excusedReason: attRecord.excusedReason ?? null, takenBy: attSession?.takenByName ?? null } : null,
    wallet: {
      prepaidCents: wallet?.prepaidCents ?? 0,
      transportCents: wallet?.transportCents ?? 0,
      itemPrepaidTotal: Array.from(balByService.values()).reduce((a, b) => a + b, 0),
      arrearsTotal: owedRows.reduce((a, c) => a + c.amountCents, 0),
    },
    discounts: dailyDiscounts.map((a) => ({
      name: a.discount?.name ?? "Discount",
      method: a.discount?.type ?? "PERCENT",
      value: a.valueOverride ?? a.discount?.value ?? 0,
      scoped: !!a.serviceId,
    })),
    cards,
    transport: transportCard,
    invoices: invoiceCards,
    recentPayments: recentPayments.map((p) => ({
      id: p.id, receiptNo: p.receiptNo, amountCents: p.amountCents,
      method: paymentMethodLabel(p), category: p.category, paidAt: p.paidAt.toISOString(),
    })),
  });
}

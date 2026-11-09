import { NextRequest, NextResponse } from "next/server";
import { sortClassrooms } from "@/lib/classes";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { generateDayCharges, settleDailyCharges, effectivePrice, applyTransportUsage, parseTeacherPerms, DEFAULT_TEACHER_PERMS, collectionAllowed, TRANSPORT_USAGES, logDailyFeeAudit, arrearsTotalCents, creditItemBalance, itemBalanceCents, collectDailyItems, markPresentForDate, sanitizeMethodParts, paymentMethodLabel, type CollectionMode, type TeacherPerms, type TransportUsage } from "@/lib/daily-billing";
import { sendAutomated, smsEventEnabled } from "@/lib/sms";
import { dailyServicesReceipt } from "@/lib/receipt-payload";
import { nextReceiptNo } from "@/lib/codes";
import { PAYMENT_LABELS } from "@/lib/format";

const fmtMoney = (cents: number) => `₵${(cents / 100).toFixed(2)}`;

// ── GET /api/school/[id]/ops/daily — services, ledger, POS, wallets, settings ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["daily.view"] });
  if (!auth.ok) return auth.res;

  const url = new URL(req.url);
  const q = (k: string) => url.searchParams.get(k) ?? undefined;
  const dateStr = q("date") ?? new Date().toISOString().slice(0, 10);
  const date = new Date(dateStr);
  // seeded/generated charges may carry a time component — match the whole calendar day
  const dayStart = new Date(date); dayStart.setHours(0, 0, 0, 0);
  const dayEnd = new Date(date); dayEnd.setHours(23, 59, 59, 999);
  const monthStart = new Date(date.getFullYear(), date.getMonth(), 1);
  const monthEnd = new Date(date.getFullYear(), date.getMonth() + 1, 0, 23, 59, 59);

  /* ── DINING COUPONS (CI3 Dining_coupon parity): the printable sheet of
     learners charged a meal/drink service on the date — 20 slips per page,
     payment type resolved (direct payment / prepaid wallet / owing). ── */
  if (q("tab") === "coupons") {
    const serviceId = q("serviceId");
    const classroomId = q("classroomId");
    if (!serviceId) return NextResponse.json({ error: "serviceId is required" }, { status: 400 });
    const svc = await db.dailyService.findFirst({ where: { id: serviceId, schoolId } });
    if (!svc) return NextResponse.json({ error: "Service not found" }, { status: 404 });
    const dayCharges = await db.dailyCharge.findMany({
      where: { schoolId, serviceId, date: { gte: dayStart, lte: dayEnd }, status: { not: "WAIVED" } },
      include: { student: { include: { enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: true } } } }, payment: true },
      orderBy: [{ student: { firstName: "asc" } }],
    });
    const rows = dayCharges
      .filter((c) => !classroomId || c.student.enrollments[0]?.classroomId === classroomId)
      .map((c) => ({
        student: `${c.student.firstName} ${c.student.lastName}`,
        studentCode: c.student.nyansapoIndex ?? "",
        classroom: c.student.enrollments[0]?.classroom?.name ?? null,
        amountCents: c.amountCents,
        paymentType: c.status === "PAID"
          ? (c.payment ? "Direct payment" : "Prepaid wallet")
          : c.status === "OWED" ? "Owing" : "Prepaid wallet",
      }));
    return NextResponse.json({ date: dateStr, service: { id: svc.id, name: svc.name, category: svc.category }, rows });
  }

  const services = await db.dailyService.findMany({
    where: { schoolId },
    include: { _count: { select: { enrollments: { where: { active: true } } } }, classRates: { include: { classroom: { select: { id: true, name: true } } } } },
    orderBy: { name: "asc" },
  });

  // per-service stats (this month)
  const charges = await db.dailyCharge.findMany({ where: { schoolId, date: { gte: monthStart, lte: monthEnd } } });
  const byService: Record<string, { billed: number; collected: number; owed: number; waived: number; count: number }> = {};
  charges.forEach((c) => {
    const e = (byService[c.serviceId] ??= { billed: 0, collected: 0, owed: 0, waived: 0, count: 0 });
    e.billed += c.amountCents;
    e.count++;
    if (c.status === "PAID") e.collected += c.amountCents;
    else if (c.status === "OWED") e.owed += c.amountCents;
    else e.waived += c.amountCents;
  });

  // 14-day trend
  const trendStart = new Date(date.getTime() - 20 * 864e5);
  const recent = await db.dailyCharge.findMany({ where: { schoolId, date: { gte: trendStart, lte: date } } });
  const dayMap: Record<string, { billed: number; collected: number }> = {};
  recent.forEach((c) => {
    const k = c.date.toISOString().slice(0, 10);
    const e = (dayMap[k] ??= { billed: 0, collected: 0 });
    e.billed += c.amountCents;
    if (c.status === "PAID") e.collected += c.amountCents;
  });
  const trend = Object.entries(dayMap).map(([d, v]) => ({ date: d, ...v })).sort((a, b) => a.date.localeCompare(b.date)).slice(-14);

  // ledger: per-student outstanding (all time)
  const ledgerCharges = await db.dailyCharge.findMany({ where: { schoolId, status: "OWED" }, include: { student: true, service: true } });
  const ledger: Record<string, { studentId: string; student: string; classroom: string | null; guardianPhone: string; owed: number; items: Record<string, number>; oldest: string }> = {};
  for (const c of ledgerCharges) {
    const e = (ledger[c.studentId] ??= { studentId: c.studentId, student: `${c.student.firstName} ${c.student.lastName}`, classroom: null, guardianPhone: c.student.guardianPhone, owed: 0, items: {}, oldest: c.date.toISOString() });
    e.owed += c.amountCents;
    e.items[c.service.name] = (e.items[c.service.name] ?? 0) + c.amountCents;
    if (c.date.toISOString() < e.oldest) e.oldest = c.date.toISOString();
  }
  const enrollments = await db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" } });
  const classByStudent: Record<string, string> = {};
  enrollments.forEach((e) => (classByStudent[e.studentId] = e.classroomId));
  const classrooms =sortClassrooms(await db.classroom.findMany({ where: { schoolId } }));
  const clsName = Object.fromEntries(classrooms.map((c) => [c.id, c.name]));
  Object.values(ledger).forEach((l) => (l.classroom = clsName[classByStudent[l.studentId]] ?? null));

  // POS workspace: today's charges + recent POS payments
  const todayCharges = await db.dailyCharge.findMany({ where: { schoolId, date: { gte: dayStart, lte: dayEnd } }, include: { student: true, service: true } });
  const posPayments = await db.payment.findMany({ where: { schoolId, category: "DAILY" }, orderBy: { paidAt: "desc" }, take: 10, include: { student: true } });

  // meal opt-in / opt-out register for the day (dining parity): the counter and
  // the meals tab both read this — approved opt-outs must not be charged
  const mealExceptions = await db.dailyMealException.findMany({
    where: { schoolId, date: { gte: dayStart, lte: dayEnd } },
    include: { student: { select: { firstName: true, lastName: true } }, service: { select: { name: true } } },
    orderBy: { createdAt: "desc" },
  });
  const mealOutByStudentSvc = new Map(mealExceptions.filter((e) => e.status === "APPROVED" && e.kind === "OPT_OUT").map((e) => [`${e.studentId}:${e.serviceId}`, e]));

  // per-service today stats (iedupro-style category cards)
  const todayByService: Record<string, { billed: number; collected: number; count: number; owedCount: number }> = {};
  todayCharges.forEach((c) => {
    const e = (todayByService[c.serviceId] ??= { billed: 0, collected: 0, count: 0, owedCount: 0 });
    e.billed += c.amountCents;
    e.count++;
    if (c.status === "PAID") e.collected += c.amountCents;
    else if (c.status === "OWED") e.owedCount++;
  });
  // WEEKLY services (e.g. sachet water on Wednesdays) aggregate across the whole ISO week
  const weeklyServiceIds = services.filter((s) => s.chargeMode === "WEEKLY").map((s) => s.id);
  const weekStart = (() => { const wk = new Date(dayStart); const dow = (wk.getDay() + 6) % 7; wk.setDate(wk.getDate() - dow); return wk; })();
  const weekEnd = new Date(weekStart); weekEnd.setDate(weekStart.getDate() + 7);
  const weekCharges = weeklyServiceIds.length
    ? await db.dailyCharge.findMany({ where: { schoolId, serviceId: { in: weeklyServiceIds }, date: { gte: weekStart, lt: weekEnd } }, select: { studentId: true, serviceId: true, amountCents: true, status: true } })
    : [];
  // per-learner × weekly-service: already billed this week? (iEduPro: once the child
  // pays within the week, no further billing until the following week)
  const weekBilled = new Set(weekCharges.map((c) => `${c.studentId}:${c.serviceId}`));
  {
    const weekByService: Record<string, { billed: number; collected: number; count: number; owedCount: number }> = {};
    weekCharges.forEach((c) => {
      const e = (weekByService[c.serviceId] ??= { billed: 0, collected: 0, count: 0, owedCount: 0 });
      e.billed += c.amountCents; e.count++;
      if (c.status === "PAID") e.collected += c.amountCents;
      else if (c.status === "OWED") e.owedCount++;
    });
    Object.assign(todayByService, weekByService);
  }

  // students list for POS picker (with enrolled services + today status)
  const studentRows = await db.enrollment.findMany({
    where: { schoolId, status: "ACTIVE" },
    include: { student: { include: { serviceEnrollments: { where: { active: true }, include: { service: true } } } }, classroom: true },
  });
  const todayByStudent: Record<string, { charged: number; owed: number }> = {};
  todayCharges.forEach((c) => {
    const e = (todayByStudent[c.studentId] ??= { charged: 0, owed: 0 });
    e.charged += c.amountCents;
    if (c.status === "OWED") e.owed += c.amountCents;
  });

  // ── wallets (iedupro §1): prepaid + transport balances, arrears derived from ledger ──
  const walletRows = await db.studentWallet.findMany({ where: { schoolId } });
  const walletByStudent: Record<string, { prepaidCents: number; transportCents: number }> = {};
  walletRows.forEach((w) => (walletByStudent[w.studentId] = { prepaidCents: w.prepaidCents, transportCents: w.transportCents }));
  const walletTxns = await db.walletTxn.findMany({ where: { schoolId }, orderBy: { at: "desc" }, take: 60, include: { student: true } });

  // today's transport-fare charge per student (usage picker in the collect dialog)
  const todayTransport = todayCharges.filter((c) => c.service.category === "TRANSPORT");
  const transportByStudent: Record<string, { id: string; amount: number; status: string; usage: string | null }> = {};
  todayTransport.forEach((c) => (transportByStudent[c.studentId] = { id: c.id, amount: c.amountCents, status: c.status, usage: c.usage }));

  // per-classroom rate lookup (iedupro daily_fee_rates parity: student override → class rate → standard)
  const classRateFor = (serviceId: string, classroomId: string): number | null => {
    const svc = services.find((s) => s.id === serviceId);
    return svc?.classRates.find((r) => r.classroomId === classroomId)?.priceCents ?? null;
  };

  // one-way route fare preview per learner (iEduPro: the route fare captures a single
  // directional charge — morning OR afternoon 1×, both ways 2×; the collect dialog
  // previews the live total as the direction is picked)
  const riderRows = await db.transportAssignment.findMany({ where: { active: true, route: { schoolId } }, include: { route: { include: { stopRecords: true } } } });
  const transportFareByStudent: Record<string, { oneway: number; routeName: string; stopName: string | null; routeId: string }> = {};
  riderRows.forEach((a) => {
    const stopName = a.pickupStop ?? a.dropStop;
    const stop = stopName ? a.route.stopRecords.find((s) => s.name.trim().toLowerCase() === stopName.trim().toLowerCase() && s.fareCents != null) : undefined;
    transportFareByStudent[a.studentId] = {
      oneway: stop?.fareCents ?? a.route.farePerTripCents ?? 0,
      routeName: a.route.name,
      stopName: stop?.fareCents != null ? stopName! : null,
      routeId: a.routeId,
    };
  });

  // ── per-item state for every learner (iEduPro daily_fee_wallet parity) ──
  //  each fee item carries its own prepaid balance + arrears; the POS collect
  //  dialog renders one row per item with the amount being paid per item
  const itemBalRows = await db.serviceBalance.findMany({ where: { schoolId } });
  const itemBal = new Map<string, number>(); // `${studentId}:${serviceId}` → prepaid cents
  itemBalRows.forEach((b) => itemBal.set(`${b.studentId}:${b.serviceId}`, b.prepaidCents));
  const itemOwed = new Map<string, number>(); // per-item arrears (sum of OWED charges)
  ledgerCharges.forEach((c) => itemOwed.set(`${c.studentId}:${c.serviceId}`, (itemOwed.get(`${c.studentId}:${c.serviceId}`) ?? 0) + c.amountCents));
  const itemToday = new Map<string, { amount: number; status: string; usage: string | null }>();
  todayCharges.forEach((c) => itemToday.set(`${c.studentId}:${c.serviceId}`, { amount: c.amountCents, status: c.status, usage: c.usage }));

  const posStudents = studentRows.map((r) => ({
    id: r.student.id,
    name: `${r.student.firstName} ${r.student.lastName}`,
    classroom: r.classroom.name,
    classroomId: r.classroomId,
    guardianPhone: r.student.guardianPhone,
    services: r.student.serviceEnrollments.map((se) => ({ id: se.serviceId, name: se.service.name, category: se.service.category, price: effectivePrice(se.service, se, classRateFor(se.serviceId, r.classroomId)) })),
    items: r.student.serviceEnrollments.map((se) => ({
      serviceId: se.serviceId,
      name: se.service.name,
      category: se.service.category,
      price: effectivePrice(se.service, se, classRateFor(se.serviceId, r.classroomId)),
      arrears: itemOwed.get(`${r.student.id}:${se.serviceId}`) ?? 0,
      balance: itemBal.get(`${r.student.id}:${se.serviceId}`) ?? 0,
      today: itemToday.get(`${r.student.id}:${se.serviceId}`) ?? null,
      weekly: se.service.chargeMode === "WEEKLY",
      weeklyBilledThisWeek: se.service.chargeMode === "WEEKLY" && weekBilled.has(`${r.student.id}:${se.serviceId}`),
      weekdays: se.service.weekdays,
      optedOut: mealOutByStudentSvc.has(`${r.student.id}:${se.serviceId}`) ? "APPROVED" : ((mealExceptions.find((e) => e.studentId === r.student.id && e.serviceId === se.serviceId && e.kind === "OPT_OUT")?.status ?? null)),
    })),
    balance: (ledger[r.student.id]?.owed ?? 0),
    wallet: walletByStudent[r.student.id] ?? { prepaidCents: 0, transportCents: 0 },
    todayTransport: transportByStudent[r.student.id] ?? null,
    today: todayByStudent[r.student.id] ?? null,
    transportFare: transportFareByStudent[r.student.id] ?? null,
  }));

  // ── collection policy settings (iedupro §1) ──
  const settingRows = await db.opsSetting.findMany({ where: { schoolId, key: { in: ["DAILY_COLLECTION_MODE", "DAILY_TEACHER_PERMS"] } } });
  const collectionMode = ((settingRows.find((r) => r.key === "DAILY_COLLECTION_MODE")?.value ?? "HYBRID") as CollectionMode);
  const teacherPerms = parseTeacherPerms(settingRows.find((r) => r.key === "DAILY_TEACHER_PERMS")?.value);
  const allClassrooms =sortClassrooms(await db.classroom.findMany({ where: { schoolId }, select: {  id: true, name: true, gradeLevel: true } }));

  // ── daily reconciliation (iedupro daily_fee_reconciliation) ──
  // charges raised vs settled at the counter vs wallet deductions, with a
  // per-collector breakdown for the handover report.
  async function buildReconciliation() {
    const dayCharges = todayCharges;
    const billed = dayCharges.reduce((a, c) => a + c.amountCents, 0);
    const collected = dayCharges.filter((c) => c.status === "PAID").reduce((a, c) => a + c.amountCents, 0);
    const waived = dayCharges.filter((c) => c.status === "WAIVED").reduce((a, c) => a + c.amountCents, 0);
    const outstanding = dayCharges.filter((c) => c.status === "OWED").reduce((a, c) => a + c.amountCents, 0);
    // receipts issued on the day; wallet-method receipts settle via a wallet txn,
    // so they count under wallet — only true counter takings (cash/momo/cheque) count here
    const dayPayments = await db.payment.findMany({ where: { schoolId, category: "DAILY", paidAt: { gte: dayStart, lte: dayEnd } } });
    const walletMethods = ["WALLET", "TRANSPORT_WALLET"];
    const counterPayments = dayPayments.filter((p) => !walletMethods.includes(p.method));
    const counter = counterPayments.reduce((a, p) => a + p.amountCents, 0);
    // wallet deductions applied on the day (prepaid + transport PAY txns)
    const dayWalletPays = await db.walletTxn.findMany({ where: { schoolId, kind: "PAY", at: { gte: dayStart, lte: dayEnd } } });
    const walletPays = dayWalletPays.reduce((a, t) => a + Math.abs(t.amountCents), 0);
    // per-collector breakdown (counter takings + wallet deductions), for the handover report
    const byCollector: Record<string, { counter: number; receipts: number; wallet: number; total: number }> = {};
    counterPayments.forEach((p) => {
      const e = (byCollector[p.receivedByName ?? "—"] ??= { counter: 0, receipts: 0, wallet: 0, total: 0 });
      e.counter += p.amountCents; e.receipts++; e.total += p.amountCents;
    });
    dayWalletPays.forEach((t) => {
      const e = (byCollector[t.byName ?? "—"] ??= { counter: 0, receipts: 0, wallet: 0, total: 0 });
      e.wallet += Math.abs(t.amountCents); e.total += Math.abs(t.amountCents);
    });
    return {
      billed, collected, waived, outstanding, counter, walletPays,
      variance: collected - counter - walletPays, // should be 0 when every settled charge traces to a receipt or wallet txn
      byCollector: Object.entries(byCollector)
        .map(([name, v]) => ({ name, ...v }))
        .sort((a, b) => b.total - a.total),
      txns: dayCharges.length,
    };
  }

  // ── daily-fee audit log (iedupro daily_fee_audit_log): latest 100 wallet/charge
  //    events — charges, payments, top-ups, adjustments — with a learner filter ──
  const auditStudentId = q("auditStudentId");
  const auditRows = await db.dailyFeeAudit.findMany({
    where: { schoolId, ...(auditStudentId ? { studentId: auditStudentId } : {}) },
    orderBy: { createdAt: "desc" },
    take: 100,
    include: { student: { include: { enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: { select: { name: true } } } } } } },
  });

  return NextResponse.json({
    date: dateStr,
    services: services.map((s) => ({
      id: s.id, name: s.name, category: s.category, price: s.priceCents, chargeMode: s.chargeMode,
      weekdays: s.weekdays, active: s.active, optInDaily: s.optInDaily, enrolled: s._count.enrollments,
      classRates: s.classRates.map((r) => ({ classroomId: r.classroomId, classroom: r.classroom.name, price: r.priceCents })),
      month: byService[s.id] ?? { billed: 0, collected: 0, owed: 0, waived: 0, count: 0 },
      today: todayByService[s.id] ?? { billed: 0, collected: 0, count: 0, owedCount: 0 },
    })),
    month: {
      billed: charges.reduce((a, c) => a + c.amountCents, 0),
      collected: charges.filter((c) => c.status === "PAID").reduce((a, c) => a + c.amountCents, 0),
      owed: charges.filter((c) => c.status === "OWED").reduce((a, c) => a + c.amountCents, 0),
      waived: charges.filter((c) => c.status === "WAIVED").reduce((a, c) => a + c.amountCents, 0),
    },
    today: {
      charged: todayCharges.reduce((a, c) => a + c.amountCents, 0),
      collected: todayCharges.filter((c) => c.status === "PAID").reduce((a, c) => a + c.amountCents, 0),
      owed: todayCharges.filter((c) => c.status === "OWED").reduce((a, c) => a + c.amountCents, 0),
      count: todayCharges.length,
      generated: todayCharges.length > 0,
    },
    trend,
    ledger: Object.values(ledger).sort((a, b) => b.owed - a.owed),
    posStudents,
    posPayments: posPayments.map((p) => ({ id: p.id, receiptNo: p.receiptNo, amount: p.amountCents, method: paymentMethodLabel(p), paidAt: p.paidAt, student: p.student ? `${p.student.firstName} ${p.student.lastName}` : "—" })),
    wallets: posStudents
      .filter((s) => s.wallet.prepaidCents > 0 || s.wallet.transportCents > 0 || s.balance > 0 || s.items.some((i: any) => i.balance > 0))
      .map((s) => ({
        studentId: s.id, student: s.name, classroom: s.classroom,
        prepaid: s.wallet.prepaidCents, transport: s.wallet.transportCents, arrears: s.balance,
        // per-item prepaid + arrears (iEduPro daily_fee_wallet parity)
        items: (s.items as any[]).filter((i) => i.balance > 0 || i.arrears > 0)
          .map((i) => ({ serviceId: i.serviceId, name: i.name, category: i.category, balance: i.balance, arrears: i.arrears })),
      })),
    walletTxns: walletTxns.map((t) => ({ id: t.id, student: `${t.student.firstName} ${t.student.lastName}`, wallet: t.wallet, item: t.serviceId ? services.find((s) => s.id === t.serviceId)?.name ?? null : null, kind: t.kind, amount: Math.abs(t.amountCents), balanceAfter: t.balanceAfter, note: t.note, byName: t.byName, at: t.at })),
    // ── daily reconciliation (iedupro daily_fee_reconciliation): charges raised vs
    //    settled at the counter vs wallet deductions, with the collector breakdown
    //    for the handover report ──
    reconciliation: await buildReconciliation(),
    // ── audit log (iedupro daily_fee_audit_log): every charge / payment / top-up /
    //    adjustment event, newest first (latest 100, filterable by learner) ──
    audit: auditRows.map((a) => ({
      id: a.id, studentId: a.studentId,
      student: `${a.student.firstName} ${a.student.lastName}`,
      classroom: a.student.enrollments[0]?.classroom?.name ?? null,
      actionType: a.actionType, service: a.serviceCode, amount: a.amountCents,
      balanceBefore: a.balanceBeforeCents, balanceAfter: a.balanceAfterCents,
      arrearsBefore: a.arrearsBeforeCents, arrearsAfter: a.arrearsAfterCents,
      by: a.performedByName, byRole: a.performedByRole,
      reference: a.reference, notes: a.notes, at: a.createdAt,
    })),
    settings: {
      collectionMode,
      teacherPerms,
      classrooms: allClassrooms,
    },
    // meal opt-in / opt-out register for the date (Meals tab + counter badges)
    mealExceptions: mealExceptions.map((e) => ({
      id: e.id, studentId: e.studentId, student: `${e.student.firstName} ${e.student.lastName}`,
      serviceId: e.serviceId, service: e.service.name, kind: e.kind, status: e.status,
      reason: e.reason, requestedByName: e.requestedByName, decidedByName: e.decidedByName, createdAt: e.createdAt,
    })),
  });
}

// ── POST /api/school/[id]/ops/daily — services, enrolment, POS, generation ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  // RBAC (iEduPro collection-policy parity): collecting is for teachers & cashiers
  // (daily.collect); configuration, enrolment, wallets & waivers need daily.manage.
  const MANAGE_ACTIONS = ["save-service", "toggle-service", "enroll", "unenroll", "settings-save", "wallet-adjust", "waive-charge", "waive-student-day", "rate-save", "rate-clear", "meal-exception-save", "meal-exception-decide", "meal-exception-delete"];
  const auth = await opsAuth(req, schoolId, "POST", {
    perms: MANAGE_ACTIONS.includes(action) ? ["daily.manage"] : ["daily.collect", "daily.manage"],
  });
  if (!auth.ok) return auth.res;
  const user = auth.user;

  if (action === "save-service") {
    const { id, name, category, priceCents, chargeMode, weekdays, optInDaily } = body;
    if (!name || !Number(priceCents)) return NextResponse.json({ error: "Name and price are required" }, { status: 400 });
    const data = { name, category: category ?? "OTHER", priceCents: Number(priceCents), chargeMode: chargeMode ?? "DAILY", weekdays: weekdays ?? "1,2,3,4,5", optInDaily: !!optInDaily };
    const svc = id ? await db.dailyService.update({ where: { id }, data }) : await db.dailyService.create({ data: { ...data, schoolId } });
    return NextResponse.json({ service: svc });
  }

  if (action === "toggle-service") {
    const svc = await db.dailyService.findUnique({ where: { id: body.id } });
    if (!svc || svc.schoolId !== schoolId) return NextResponse.json({ error: "Service not found" }, { status: 404 });
    const updated = await db.dailyService.update({ where: { id: body.id }, data: { active: !svc.active } });
    return NextResponse.json({ service: updated });
  }

  /* ── presence-only entry from the collection portal (wave 10): record a
     boarding direction and/or mark present WITHOUT any payment — the transport
     fare materialises against the transport wallet/arrears (iEduPro
     record_transport_intention + mark_student_boarded), no cash moves. ── */
  if (action === "record-presence") {
    const { studentId, transportUsage, markPresent, date: on } = body;
    const student = await db.student.findUnique({ where: { id: studentId } });
    if (!student) return NextResponse.json({ error: "Learner not found" }, { status: 404 });
    const day = new Date(on ?? new Date().toISOString().slice(0, 10));
    let transport: { applied: string; fareCents: number | null } | null = null;
    if (transportUsage) {
      if (!TRANSPORT_USAGES.includes(transportUsage)) return NextResponse.json({ error: "Invalid transport usage" }, { status: 400 });
      const applied = await applyTransportUsage(db, schoolId, studentId, day, transportUsage as TransportUsage, { performedByName: user.fullName, performedByRole: user.role });
      if (transportUsage !== "NOT_USING" && !applied) return NextResponse.json({ error: "This learner has no active route assignment" }, { status: 400 });
      transport = { applied: transportUsage, fareCents: applied ? applied.amountCents : 0 };
    }
    const present = markPresent
      ? await markPresentForDate(db, { schoolId, studentId, date: day })
      : null;
    return NextResponse.json({
      transport,
      present: present ? { mode: present } : null,
    });
  }

  // ── per-classroom rate matrix (iedupro daily_fee_rates: rates are set per
  //    class-section per fee type). upsert one rate; clear removes the override.
  //    TRANSPORT is the exception — fares follow the bus ROUTE (per pickup stop,
  //    iEduPro enroll.transport_id → transport.route_fare), never the class. ──
  if (action === "rate-save") {
    const { serviceId, classroomId, priceCents } = body;
    if (!serviceId || !classroomId) return NextResponse.json({ error: "serviceId and classroomId required" }, { status: 400 });
    const svc = await db.dailyService.findUnique({ where: { id: serviceId } });
    if (!svc || svc.schoolId !== schoolId) return NextResponse.json({ error: "Service not found" }, { status: 404 });
    if (svc.category === "TRANSPORT")
      return NextResponse.json({ error: "Transport fare is not class-based — it follows the bus route and pickup stop. Manage fares in Transportation → Routes." }, { status: 400 });
    const cls = await db.classroom.findUnique({ where: { id: classroomId } });
    if (!cls || cls.schoolId !== schoolId) return NextResponse.json({ error: "Classroom not found" }, { status: 404 });
    const price = Math.max(0, Math.round(Number(priceCents) || 0));
    if (price <= 0) return NextResponse.json({ error: "Enter a rate above zero" }, { status: 400 });
    const rate = await db.dailyServiceRate.upsert({
      where: { serviceId_classroomId: { serviceId, classroomId } },
      update: { priceCents: price },
      create: { serviceId, classroomId, priceCents: price },
    });
    return NextResponse.json({ rate });
  }

  if (action === "rate-clear") {
    const { serviceId, classroomId } = body;
    if (!serviceId || !classroomId) return NextResponse.json({ error: "serviceId and classroomId required" }, { status: 400 });
    await db.dailyServiceRate.deleteMany({ where: { serviceId, classroomId } });
    return NextResponse.json({ ok: true });
  }

  if (action === "enroll") {
    const { serviceId, studentIds, priceCentsOverride } = body;
    if (!serviceId || !Array.isArray(studentIds) || !studentIds.length) return NextResponse.json({ error: "serviceId and studentIds[] required" }, { status: 400 });
    let n = 0;
    for (const studentId of studentIds) {
      await db.dailyServiceEnrollment.upsert({
        where: { serviceId_studentId: { serviceId, studentId } },
        update: { active: true, priceCentsOverride: priceCentsOverride ? Number(priceCentsOverride) : null },
        create: { serviceId, studentId, priceCentsOverride: priceCentsOverride ? Number(priceCentsOverride) : null },
      });
      n++;
    }
    return NextResponse.json({ enrolled: n });
  }

  if (action === "unenroll") {
    await db.dailyServiceEnrollment.update({ where: { id: body.enrollmentId }, data: { active: false } }).catch(() => null);
    return NextResponse.json({ ok: true });
  }

  if (action === "generate-day") {
    const date = new Date(body.date ?? new Date().toISOString().slice(0, 10));
    const res = await generateDayCharges(db, schoolId, date, { performedByName: user.fullName, performedByRole: user.role });
    return NextResponse.json(res);
  }

  // ── meal opt-in / opt-out register (dining parity): staff-created exceptions are
  //    APPROVED immediately; parent portal requests arrive PENDING and are decided here.
  //    An APPROVED OPT_OUT waives any unpaid charge for that learner/service/day. ──
  if (action === "meal-exception-save") {
    const { studentId, serviceId, date, kind, reason } = body;
    if (!studentId || !serviceId || !date || !"OPT_OUT OPT_IN".includes(kind ?? ""))
      return NextResponse.json({ error: "studentId, serviceId, date and kind (OPT_OUT | OPT_IN) are required" }, { status: 400 });
    const svc = await db.dailyService.findUnique({ where: { id: serviceId } });
    if (!svc || svc.schoolId !== schoolId) return NextResponse.json({ error: "Service not found" }, { status: 404 });
    if (kind === "OPT_IN" && !svc.optInDaily)
      return NextResponse.json({ error: `${svc.name} is billed to every enrolled learner by default — opt-in is only for services marked optional (e.g. breakfast).` }, { status: 400 });
    if (kind === "OPT_OUT" && svc.optInDaily)
      return NextResponse.json({ error: `${svc.name} is optional — learners without an opt-in are simply not billed. Use OPT_IN to join for a day.` }, { status: 400 });
    const st = await db.student.findFirst({ where: { id: studentId, enrollments: { some: { schoolId } } } });
    if (!st) return NextResponse.json({ error: "Learner not found" }, { status: 404 });
    const d = new Date(date); d.setHours(0, 0, 0, 0);
    const row = await db.dailyMealException.upsert({
      where: { studentId_serviceId_date_kind: { studentId, serviceId, date: d, kind } },
      update: { status: "APPROVED", reason: reason ?? null, decidedByName: user.fullName, decidedAt: new Date() },
      create: { schoolId, studentId, serviceId, date: d, kind, status: "APPROVED", reason: reason ?? null, requestedByName: user.fullName, decidedByName: user.fullName, decidedAt: new Date() },
    });
    // approved opt-out: immediately waive any unpaid charge already generated for that day
    if (kind === "OPT_OUT") {
      const dEnd = new Date(d); dEnd.setHours(23, 59, 59, 999);
      const unpaid = await db.dailyCharge.findFirst({ where: { schoolId, serviceId, studentId, date: { gte: d, lte: dEnd }, status: "OWED" } });
      if (unpaid) {
        await db.dailyCharge.update({ where: { id: unpaid.id }, data: { status: "WAIVED" } });
        await logDailyFeeAudit(db, {
          schoolId, studentId, actionType: "CHARGE_REVERSAL", serviceCode: svc.name, amountCents: unpaid.amountCents,
          performedByName: user.fullName, performedByRole: user.role,
          reference: unpaid.id, notes: `waived — meal opt-out approved · ${svc.name} · ${d.toISOString().slice(0, 10)}`,
        });
      }
    }
    return NextResponse.json({ exception: row });
  }

  if (action === "meal-exception-decide") {
    const { id, decision } = body; // APPROVED | DECLINED
    if (!id || !"APPROVED DECLINED".includes(decision ?? "")) return NextResponse.json({ error: "id and decision (APPROVED | DECLINED) required" }, { status: 400 });
    const row = await db.dailyMealException.findFirst({ where: { id, schoolId }, include: { service: true } });
    if (!row) return NextResponse.json({ error: "Request not found" }, { status: 404 });
    const updated = await db.dailyMealException.update({ where: { id }, data: { status: decision, decidedByName: user.fullName, decidedAt: new Date() } });
    if (decision === "APPROVED" && row.kind === "OPT_OUT") {
      const d = new Date(row.date); const dEnd = new Date(d); dEnd.setHours(23, 59, 59, 999);
      const unpaid = await db.dailyCharge.findFirst({ where: { schoolId, serviceId: row.serviceId, studentId: row.studentId, date: { gte: d, lte: dEnd }, status: "OWED" } });
      if (unpaid) {
        await db.dailyCharge.update({ where: { id: unpaid.id }, data: { status: "WAIVED" } });
        await logDailyFeeAudit(db, {
          schoolId, studentId: row.studentId, actionType: "CHARGE_REVERSAL", serviceCode: row.service.name, amountCents: unpaid.amountCents,
          performedByName: user.fullName, performedByRole: user.role,
          reference: unpaid.id, notes: `waived — meal opt-out approved · ${row.service.name} · ${d.toISOString().slice(0, 10)}`,
        });
      }
    }
    return NextResponse.json({ exception: updated });
  }

  if (action === "meal-exception-delete") {
    const row = await db.dailyMealException.findFirst({ where: { id: body.id, schoolId } });
    if (!row) return NextResponse.json({ error: "Request not found" }, { status: 404 });
    await db.dailyMealException.delete({ where: { id: body.id } });
    return NextResponse.json({ ok: true });
  }

  if (action === "settings-save") {
    // admins only — the collection policy is a management decision
    if (user.role !== "SCHOOL_ADMIN" && user.role !== "GROUP_OWNER")
      return NextResponse.json({ error: "Only administrators can change the collection policy." }, { status: 403 });
    const mode = ["CLASSROOM", "CASHIER", "HYBRID"].includes(body.collectionMode) ? body.collectionMode as string : "HYBRID";
    const permsIn = body.teacherPerms ?? {};
    const perms: TeacherPerms = {
      mode: ["ALL", "NONE", "SELECTIVE"].includes(permsIn.mode) ? permsIn.mode : "ALL",
      classroomIds: Array.isArray(permsIn.classroomIds) ? permsIn.classroomIds.filter((x: unknown) => typeof x === "string") : [],
      serviceIds: Array.isArray(permsIn.serviceIds) ? permsIn.serviceIds.filter((x: unknown) => typeof x === "string") : [],
    };
    for (const [key, value] of [["DAILY_COLLECTION_MODE", mode], ["DAILY_TEACHER_PERMS", JSON.stringify(perms)] as [string, string]] as [string, string][]) {
      await db.opsSetting.upsert({ where: { schoolId_key: { schoolId, key } }, update: { value }, create: { schoolId, key, value } });
    }
    return NextResponse.json({ collectionMode: mode, teacherPerms: perms });
  }

  if (action === "wallet-topup") {
    const { studentId, wallet, amountCents, note, serviceId } = body;
    if (!studentId) return NextResponse.json({ error: "studentId required" }, { status: 400 });
    const amt = Math.round(Number(amountCents));
    if (!amt || amt <= 0) return NextResponse.json({ error: "Enter a top-up amount above zero" }, { status: 400 });
    const student = await db.student.findUnique({ where: { id: studentId } });
    if (!student) return NextResponse.json({ error: "Learner not found" }, { status: 404 });

    // ── per-item top-up (iEduPro daily_fee_wallet: each fee item holds its own
    //    prepaid credit — top up Lunch, Water, Transport fare, … separately) ──
    if (serviceId) {
      const svc = await db.dailyService.findUnique({ where: { id: serviceId } });
      if (!svc || svc.schoolId !== schoolId) return NextResponse.json({ error: "Service not found" }, { status: 404 });
      const balance = await creditItemBalance(db, { schoolId, studentId, serviceId, amountCents: amt, kind: "TOPUP", note: note ?? null, byName: user.fullName });
      await logDailyFeeAudit(db, {
        schoolId, studentId, actionType: "TOPUP", serviceCode: svc.name, amountCents: amt,
        performedByName: user.fullName, performedByRole: user.role,
        notes: `Item balance top-up — ${svc.name}${note ? ` — ${note}` : ""}`,
      });
      return NextResponse.json({ service: { id: svc.id, name: svc.name }, balance, wallet: "ITEM" });
    }

    if (wallet !== "PREPAID" && wallet !== "TRANSPORT") return NextResponse.json({ error: "wallet must be PREPAID or TRANSPORT" }, { status: 400 });
    const existing = await db.studentWallet.findUnique({ where: { studentId } });
    const w = existing
      ? await db.studentWallet.update({
          where: { studentId },
          data: wallet === "PREPAID" ? { prepaidCents: { increment: amt } } : { transportCents: { increment: amt } },
        })
      : await db.studentWallet.create({
          data: { schoolId, studentId, ...(wallet === "PREPAID" ? { prepaidCents: amt } : { transportCents: amt }) },
        });
    const balanceAfter = wallet === "PREPAID" ? w.prepaidCents : w.transportCents;
    const txn = await db.walletTxn.create({
      data: { schoolId, studentId, wallet, kind: "TOPUP", amountCents: amt, balanceAfter, note: note ?? null, byName: user.fullName },
    });
    // iEduPro daily_fee_audit_log: every top-up leaves a trail
    await logDailyFeeAudit(db, {
      schoolId, studentId, actionType: "TOPUP", serviceCode: wallet, amountCents: amt,
      balanceBeforeCents: (existing ? existing.prepaidCents + existing.transportCents : 0),
      balanceAfterCents: w.prepaidCents + w.transportCents,
      performedByName: user.fullName, performedByRole: user.role,
      reference: txn.id,
      notes: `${wallet === "PREPAID" ? "Prepaid" : "Transport"} wallet top-up${note ? ` — ${note}` : ""}`,
    });
    return NextResponse.json({ wallet: w, txn });
  }

  if (action === "wallet-adjust") {
    // correction: set a wallet to an exact balance (audit-trailed)
    const { studentId, wallet, amountCents, note, serviceId } = body;
    if (!studentId) return NextResponse.json({ error: "studentId required" }, { status: 400 });

    // ── per-item balance correction ──
    if (serviceId) {
      const svc = await db.dailyService.findUnique({ where: { id: serviceId } });
      if (!svc || svc.schoolId !== schoolId) return NextResponse.json({ error: "Service not found" }, { status: 404 });
      const target = Math.max(0, Math.round(Number(amountCents) || 0));
      const before = await itemBalanceCents(db, studentId, serviceId);
      await db.serviceBalance.upsert({
        where: { studentId_serviceId: { studentId, serviceId } },
        update: { prepaidCents: target },
        create: { schoolId, studentId, serviceId, prepaidCents: target },
      });
      await db.walletTxn.create({
        data: { schoolId, studentId, wallet: "ITEM", kind: "ADJUST", amountCents: target - before, balanceAfter: target, note: note ?? "balance correction", byName: user.fullName, serviceId },
      });
      await logDailyFeeAudit(db, {
        schoolId, studentId, actionType: "ADJUSTMENT", serviceCode: svc.name, amountCents: target - before,
        performedByName: user.fullName, performedByRole: user.role,
        notes: `Item balance set to ₵${(target / 100).toFixed(2)} — ${svc.name} — ${note ?? "balance correction"}`,
      });
      return NextResponse.json({ service: { id: svc.id, name: svc.name }, balance: target, wallet: "ITEM" });
    }

    if (wallet !== "PREPAID" && wallet !== "TRANSPORT") return NextResponse.json({ error: "wallet must be PREPAID or TRANSPORT" }, { status: 400 });
    const target = Math.max(0, Math.round(Number(amountCents) || 0));
    const w0 = await db.studentWallet.findUnique({ where: { studentId } });
    if (!w0) return NextResponse.json({ error: "No wallet to adjust — top it up first" }, { status: 404 });
    const before = wallet === "PREPAID" ? w0.prepaidCents : w0.transportCents;
    const w = await db.studentWallet.update({
      where: { studentId },
      data: wallet === "PREPAID" ? { prepaidCents: target } : { transportCents: target },
    });
    const txn = await db.walletTxn.create({
      data: { schoolId, studentId, wallet, kind: "ADJUST", amountCents: target - before, balanceAfter: target, note: note ?? "balance correction", byName: user.fullName },
    });
    // iEduPro daily_fee_audit_log: balance corrections are audit-trailed with the reason
    await logDailyFeeAudit(db, {
      schoolId, studentId, actionType: "ADJUSTMENT", serviceCode: wallet, amountCents: target - before,
      balanceBeforeCents: w0.prepaidCents + w0.transportCents,
      balanceAfterCents: w.prepaidCents + w.transportCents,
      performedByName: user.fullName, performedByRole: user.role,
      reference: txn.id,
      notes: `${wallet === "PREPAID" ? "Prepaid" : "Transport"} wallet set to ₵${(target / 100).toFixed(2)} — ${note ?? "balance correction"}`,
    });
    return NextResponse.json({ wallet: w, txn });
  }

  if (action === "pos-collect") {
    const { studentId, amountCents, items, method, reference, payFrom, paidAt, transportUsage, markPresent } = body;
    const student = await db.student.findUnique({ where: { id: studentId } });
    if (!student) return NextResponse.json({ error: "Learner not found" }, { status: 404 });

    // transport usage first (iEduPro §1): fixes the day's fare before settling —
    // the route fare is ONE-WAY: morning or afternoon 1×, both ways 2×, not riding 0
    if (transportUsage) {
      if (!TRANSPORT_USAGES.includes(transportUsage)) return NextResponse.json({ error: "Invalid transport usage" }, { status: 400 });
      const applied = await applyTransportUsage(db, schoolId, studentId, new Date(body.date ?? new Date().toISOString().slice(0, 10)), transportUsage as TransportUsage, { performedByName: user.fullName, performedByRole: user.role });
      if (transportUsage !== "NOT_USING" && !applied)
        return NextResponse.json({ error: "This learner has no active route assignment — transport fare follows the bus route." }, { status: 400 });
    }

    // ── per-item payment entry (iEduPro §apply_payment) ───────────────────
    //  each fee item carries the amount being paid for it; the item's arrears
    //  settle FIFO and any EXCESS lands on that item's prepaid balance as
    //  advance (payment_type: arrears | current | advance | mixed).
    if (Array.isArray(items) && items.length) {
      const parsed = (items as { serviceId?: string; amountCents?: number }[])
        .map((i) => ({ serviceId: String(i.serviceId ?? ""), amountCents: Math.max(0, Math.round(Number(i.amountCents) || 0)) }))
        .filter((i) => i.serviceId && i.amountCents > 0);
      if (!parsed.length) return NextResponse.json({ error: "Enter an amount for at least one fee item" }, { status: 400 });
      const svcRows = await db.dailyService.findMany({ where: { schoolId, id: { in: parsed.map((i) => i.serviceId) } } });
      if (svcRows.length !== parsed.length) return NextResponse.json({ error: "One of the fee items does not belong to this school" }, { status: 400 });
      const total = parsed.reduce((s, i) => s + i.amountCents, 0);

      // collection policy (iedupro §1): mode + teacher permission matrix
      const settingRows = await db.opsSetting.findMany({ where: { schoolId, key: { in: ["DAILY_COLLECTION_MODE", "DAILY_TEACHER_PERMS"] } } });
      const mode = ((settingRows.find((r) => r.key === "DAILY_COLLECTION_MODE")?.value ?? "HYBRID") as CollectionMode);
      const perms = parseTeacherPerms(settingRows.find((r) => r.key === "DAILY_TEACHER_PERMS")?.value);
      const en = await db.enrollment.findFirst({ where: { studentId, status: "ACTIVE", schoolId } });
      const gate = collectionAllowed(user.role, mode, perms, {
        classroomId: en?.classroomId ?? null,
        serviceIds: parsed.map((i) => i.serviceId),
      });
      if (!gate.ok) return NextResponse.json({ error: gate.reason ?? "Not permitted" }, { status: 403 });

      // multi-method split (e.g. part cash + part MoMo): every part must be a
      // known counter method and the parts must add up to the items total
      let methodParts;
      if (Array.isArray(body.methods) && body.methods.length) {
        const sp = sanitizeMethodParts(body.methods, total);
        if (sp.error) return NextResponse.json({ error: sp.error }, { status: 400 });
        methodParts = sp.parts;
      }

      // shared per-item collection core (also used by the offline queue flush)
      const { payment, breakdown, settledTotal, advanceTotal, paymentType } = await collectDailyItems(db, {
        schoolId, studentId, items: parsed,
        services: svcRows.map((s) => ({ id: s.id, name: s.name })),
        method, reference: reference ?? null,
        ...(methodParts ? { methods: methodParts } : {}),
        receivedByName: user.fullName, performedByRole: user.role,
        paidAt: paidAt ? new Date(paidAt) : new Date(),
      });

      // mark present (shared with the offline flush)
      const present = markPresent
        ? await markPresentForDate(db, { schoolId, studentId, date: new Date(body.date ?? new Date().toISOString().slice(0, 10)) })
        : null;

      // auto receipt SMS — daily-fee payment event gate (evtDailyFeesPaid)
      const setting = await db.smsSetting.findUnique({ where: { schoolId } });
      if (setting?.autoPaymentReceipts && (await smsEventEnabled(db, schoolId, "DAILY_FEES_PAID"))) {
        await sendAutomated({
          db, schoolId, trigger: "PAYMENT_RECEIVED",
          defaultBody: `We have received ₵${(total / 100).toFixed(2)} for ${student.firstName} (${payment.receiptNo}) towards daily services. Thank you.`,
          vars: { student: student.firstName, amount: `₵${(total / 100).toFixed(2)}`, receipt: payment.receiptNo, date: new Date().toLocaleDateString("en-GB") },
          studentId, recipientName: student.guardianName, recipientPhone: student.guardianPhone, sentByName: user.fullName,
        });
      }
      const receipt = await dailyServicesReceipt(db, payment.id);
      return NextResponse.json({ payment: { ...payment, applied: settledTotal, advance: advanceTotal, paymentType, breakdown }, student: `${student.firstName} ${student.lastName}`, receipt, present });
    }

    // ── legacy single-amount path (offline queue + quick pay) ──────────────
    const owed = await db.dailyCharge.aggregate({ where: { studentId, status: "OWED" }, _sum: { amountCents: true } });
    const owedCents = owed._sum.amountCents ?? 0;
    if (owedCents <= 0) return NextResponse.json({ error: "No outstanding daily balance" }, { status: 400 });
    const amt = Math.max(0, Math.min(Number(amountCents), owedCents));
    if (amt <= 0) return NextResponse.json({ error: "Amount exceeds balance or is invalid" }, { status: 400 });

    // ── collection policy (iedupro §1): mode + teacher permission matrix ──
    const settingRows = await db.opsSetting.findMany({ where: { schoolId, key: { in: ["DAILY_COLLECTION_MODE", "DAILY_TEACHER_PERMS"] } } });
    const mode = ((settingRows.find((r) => r.key === "DAILY_COLLECTION_MODE")?.value ?? "HYBRID") as CollectionMode);
    const perms = parseTeacherPerms(settingRows.find((r) => r.key === "DAILY_TEACHER_PERMS")?.value);
    const en = await db.enrollment.findFirst({ where: { studentId, status: "ACTIVE", schoolId } });
    const scopeCharges = await db.dailyCharge.findMany({ where: { studentId, status: "OWED" }, include: { service: true } });
    const gate = collectionAllowed(user.role, mode, perms, {
      classroomId: en?.classroomId ?? null,
      serviceIds: [...new Set(scopeCharges.map((c) => c.serviceId))],
    });
    if (!gate.ok) return NextResponse.json({ error: gate.reason ?? "Not permitted" }, { status: 403 });

    // ── wallet payment paths (iedupro §1) ──
    const walletKind = payFrom === "PREPAID" ? "PREPAID" : payFrom === "TRANSPORT" ? "TRANSPORT" : null;
    let walletTxnId: string | null = null;
    let payMethod = method ?? "CASH";
    let onlyCategory: string | undefined;
    let walletBalBefore: number | null = null; // for the audit trail (total wallet credit pre-debit)
    if (walletKind === "PREPAID") {
      const w = await db.studentWallet.findUnique({ where: { studentId } });
      if (!w || w.prepaidCents < amt) return NextResponse.json({ error: `Prepaid wallet short — balance is ₵${(w?.prepaidCents ?? 0) / 100}` }, { status: 400 });
      walletBalBefore = w.prepaidCents + w.transportCents;
      await db.studentWallet.update({ where: { studentId }, data: { prepaidCents: { decrement: amt } } });
      const t = await db.walletTxn.create({ data: { schoolId, studentId, wallet: "PREPAID", kind: "PAY", amountCents: -amt, balanceAfter: w.prepaidCents - amt, note: reference ?? null, byName: user.fullName } });
      walletTxnId = t.id;
      payMethod = "WALLET";
    } else if (walletKind === "TRANSPORT") {
      const w = await db.studentWallet.findUnique({ where: { studentId } });
      if (!w || w.transportCents < amt) return NextResponse.json({ error: `Transport wallet short — balance is ₵${(w?.transportCents ?? 0) / 100}` }, { status: 400 });
      walletBalBefore = w.prepaidCents + w.transportCents;
      await db.studentWallet.update({ where: { studentId }, data: { transportCents: { decrement: amt } } });
      const t = await db.walletTxn.create({ data: { schoolId, studentId, wallet: "TRANSPORT", kind: "PAY", amountCents: -amt, balanceAfter: w.transportCents - amt, note: reference ?? null, byName: user.fullName } });
      walletTxnId = t.id;
      payMethod = "TRANSPORT_WALLET";
      onlyCategory = "TRANSPORT"; // transport wallet may only clear transport-fare charges
    }

    const payment = await db.payment.create({
      data: {
        receiptNo: await nextReceiptNo(), studentId, category: "DAILY", schoolId,
        amountCents: amt, method: payMethod, reference: reference ?? null,
        receivedByName: user.fullName,
        paidAt: paidAt ? new Date(paidAt) : new Date(),
      },
    });
    const applied = await settleDailyCharges(db, payment.id, studentId, amt, onlyCategory);

    // ── daily-fee audit log (iEduPro daily_fee_audit_log): every payment leaves a trail ──
    const settledCharges = await db.dailyCharge.findMany({ where: { paymentId: payment.id }, include: { service: true } });
    const settledServices = [...new Set(settledCharges.map((c) => c.service.name))];
    const arrearsAfterAgg = await db.dailyCharge.aggregate({ where: { studentId, status: "OWED" }, _sum: { amountCents: true } });
    await logDailyFeeAudit(db, {
      schoolId, studentId, actionType: "PAYMENT",
      serviceCode: settledServices.length === 1 ? settledServices[0] : settledServices.length ? "MULTIPLE" : "DAILY",
      amountCents: amt,
      ...(walletBalBefore != null ? { balanceBeforeCents: walletBalBefore, balanceAfterCents: walletBalBefore - amt } : {}),
      arrearsBeforeCents: owedCents, arrearsAfterCents: arrearsAfterAgg._sum.amountCents ?? 0,
      performedByName: user.fullName, performedByRole: user.role,
      reference: payment.receiptNo,
      notes: `${PAYMENT_LABELS[payMethod] ?? payMethod} payment${reference ? ` · ref ${reference}` : ""}${applied < amt ? ` · ${((amt - applied) / 100).toFixed(2)} not applicable to ${onlyCategory ? `${onlyCategory.toLowerCase()} charges` : "open charges"}` : ""}`,
    });

    // ── mark present (iedupro §1): flip today's class attendance to PRESENT ──
    let present: string | null = null;
    if (markPresent) {
      const d = new Date(body.date ?? new Date().toISOString().slice(0, 10));
      const dStart = new Date(d); dStart.setHours(0, 0, 0, 0);
      const dEnd = new Date(d); dEnd.setHours(23, 59, 59, 999);
      if (!en) present = "no-class";
      else {
        const session = await db.attendanceSession.findFirst({ where: { classroomId: en.classroomId, date: { gte: dStart, lte: dEnd } }, include: { records: { where: { studentId } } } });
        if (!session) present = "no-session";
        else if (session.records.length) {
          if (session.records[0].status !== "PRESENT") {
            await db.attendanceRecord.update({ where: { id: session.records[0].id }, data: { status: "PRESENT" } });
            present = "updated";
          } else present = "already";
        } else {
          await db.attendanceRecord.create({ data: { sessionId: session.id, studentId, status: "PRESENT" } });
          present = "marked";
        }
      }
    }

    // auto receipt SMS — daily-fee payment event gate (evtDailyFeesPaid)
    const setting = await db.smsSetting.findUnique({ where: { schoolId } });
    if (setting?.autoPaymentReceipts && (await smsEventEnabled(db, schoolId, "DAILY_FEES_PAID"))) {
      await sendAutomated({
        db, schoolId, trigger: "PAYMENT_RECEIVED",
        defaultBody: `We have received ₵${(amt / 100).toFixed(2)} for ${student.firstName} (${payment.receiptNo}) towards daily services. Thank you.`,
        vars: { student: student.firstName, amount: `₵${(amt / 100).toFixed(2)}`, receipt: payment.receiptNo, date: new Date().toLocaleDateString("en-GB") },
        studentId, recipientName: student.guardianName, recipientPhone: student.guardianPhone, sentByName: user.fullName,
      });
    }
    const receipt = await dailyServicesReceipt(db, payment.id);
    return NextResponse.json({ payment: { ...payment, applied }, student: `${student.firstName} ${student.lastName}`, receipt, walletTxnId, present });
  }

  if (action === "waive-charge") {
    const c = await db.dailyCharge.findUnique({ where: { id: body.chargeId }, include: { service: true } });
    if (!c || c.schoolId !== schoolId) return NextResponse.json({ error: "Charge not found" }, { status: 404 });
    const wasOwed = c.status === "OWED";
    const arrearsBefore = wasOwed ? await arrearsTotalCents(db, c.studentId) : 0;
    const updated = await db.dailyCharge.update({ where: { id: body.chargeId }, data: { status: "WAIVED" } });
    // iEduPro daily_fee_audit_log: a waiver is an adjustment that clears arrears
    if (wasOwed) {
      await logDailyFeeAudit(db, {
        schoolId, studentId: c.studentId, actionType: "ADJUSTMENT", serviceCode: c.service.name, amountCents: c.amountCents,
        arrearsBeforeCents: arrearsBefore, arrearsAfterCents: Math.max(0, arrearsBefore - c.amountCents),
        performedByName: user.fullName, performedByRole: user.role,
        reference: c.id, notes: `charge waived — ${c.service.name} · ${c.date.toISOString().slice(0, 10)}${body.reason ? ` · ${body.reason}` : ""}`,
      });
    }
    return NextResponse.json({ charge: updated });
  }

  if (action === "waive-student-day") {
    const { studentId, date } = body;
    const d = new Date(date);
    const dStart = new Date(d); dStart.setHours(0, 0, 0, 0);
    const dEnd = new Date(d); dEnd.setHours(23, 59, 59, 999);
    const dayOwed = await db.dailyCharge.findMany({
      where: { schoolId, studentId, date: { gte: dStart, lte: dEnd }, status: "OWED" }, include: { service: true },
    });
    const res = await db.dailyCharge.updateMany({ where: { schoolId, studentId, date: { gte: dStart, lte: dEnd }, status: "OWED" }, data: { status: "WAIVED" } });
    // iEduPro daily_fee_audit_log: one adjustment row for the day's waived charges
    if (res.count && dayOwed.length) {
      const names = [...new Set(dayOwed.map((c) => c.service.name))];
      const waivedTotal = dayOwed.reduce((s, c) => s + c.amountCents, 0);
      const arrearsAfter = await arrearsTotalCents(db, studentId);
      await logDailyFeeAudit(db, {
        schoolId, studentId, actionType: "ADJUSTMENT",
        serviceCode: names.length === 1 ? names[0] : "MULTIPLE", amountCents: waivedTotal,
        arrearsBeforeCents: arrearsAfter + waivedTotal, arrearsAfterCents: arrearsAfter,
        performedByName: user.fullName, performedByRole: user.role,
        notes: `day waived · ${dStart.toISOString().slice(0, 10)} · ${res.count} charges${body.reason ? ` · ${body.reason}` : ""}`,
      });
    }
    return NextResponse.json({ waived: res.count });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

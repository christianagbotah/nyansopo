import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { generateDayCharges, settleDailyCharges, effectivePrice, applyTransportUsage, parseTeacherPerms, DEFAULT_TEACHER_PERMS, collectionAllowed, TRANSPORT_USAGES, type CollectionMode, type TeacherPerms, type TransportUsage } from "@/lib/daily-billing";
import { sendAutomated } from "@/lib/sms";
import { dailyServicesReceipt } from "@/lib/receipt-payload";

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

  const services = await db.dailyService.findMany({
    where: { schoolId },
    include: { _count: { select: { enrollments: { where: { active: true } } } } },
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
  const classrooms = await db.classroom.findMany({ where: { schoolId } });
  const clsName = Object.fromEntries(classrooms.map((c) => [c.id, c.name]));
  Object.values(ledger).forEach((l) => (l.classroom = clsName[classByStudent[l.studentId]] ?? null));

  // POS workspace: today's charges + recent POS payments
  const todayCharges = await db.dailyCharge.findMany({ where: { schoolId, date: { gte: dayStart, lte: dayEnd } }, include: { student: true, service: true } });
  const posPayments = await db.payment.findMany({ where: { schoolId, category: "DAILY" }, orderBy: { paidAt: "desc" }, take: 10, include: { student: true } });

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
  if (weeklyServiceIds.length) {
    const wk = new Date(dayStart);
    const dow = (wk.getDay() + 6) % 7; // Monday = 0
    wk.setDate(wk.getDate() - dow); wk.setHours(0, 0, 0, 0); // week start (Mon)
    const wkEnd = new Date(wk); wkEnd.setDate(wkEnd.getDate() + 7);
    const weekCharges = await db.dailyCharge.findMany({ where: { schoolId, serviceId: { in: weeklyServiceIds }, date: { gte: wk, lt: wkEnd } } });
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

  const posStudents = studentRows.map((r) => ({
    id: r.student.id,
    name: `${r.student.firstName} ${r.student.lastName}`,
    classroom: r.classroom.name,
    classroomId: r.classroomId,
    guardianPhone: r.student.guardianPhone,
    services: r.student.serviceEnrollments.map((se) => ({ id: se.serviceId, name: se.service.name, category: se.service.category, price: effectivePrice(se.service, se) })),
    balance: (ledger[r.student.id]?.owed ?? 0),
    wallet: walletByStudent[r.student.id] ?? { prepaidCents: 0, transportCents: 0 },
    todayTransport: transportByStudent[r.student.id] ?? null,
    today: todayByStudent[r.student.id] ?? null,
  }));

  // ── collection policy settings (iedupro §1) ──
  const settingRows = await db.opsSetting.findMany({ where: { schoolId, key: { in: ["DAILY_COLLECTION_MODE", "DAILY_TEACHER_PERMS"] } } });
  const collectionMode = ((settingRows.find((r) => r.key === "DAILY_COLLECTION_MODE")?.value ?? "HYBRID") as CollectionMode);
  const teacherPerms = parseTeacherPerms(settingRows.find((r) => r.key === "DAILY_TEACHER_PERMS")?.value);
  const allClassrooms = await db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" }, select: { id: true, name: true } });

  return NextResponse.json({
    date: dateStr,
    services: services.map((s) => ({
      id: s.id, name: s.name, category: s.category, price: s.priceCents, chargeMode: s.chargeMode,
      weekdays: s.weekdays, active: s.active, enrolled: s._count.enrollments,
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
    posPayments: posPayments.map((p) => ({ id: p.id, receiptNo: p.receiptNo, amount: p.amountCents, method: p.method, paidAt: p.paidAt, student: p.student ? `${p.student.firstName} ${p.student.lastName}` : "—" })),
    wallets: posStudents
      .filter((s) => s.wallet.prepaidCents > 0 || s.wallet.transportCents > 0 || s.balance > 0)
      .map((s) => ({ studentId: s.id, student: s.name, classroom: s.classroom, prepaid: s.wallet.prepaidCents, transport: s.wallet.transportCents, arrears: s.balance })),
    walletTxns: walletTxns.map((t) => ({ id: t.id, student: `${t.student.firstName} ${t.student.lastName}`, wallet: t.wallet, kind: t.kind, amount: Math.abs(t.amountCents), balanceAfter: t.balanceAfter, note: t.note, byName: t.byName, at: t.at })),
    settings: {
      collectionMode,
      teacherPerms,
      classrooms: allClassrooms,
    },
  });
}

// ── POST /api/school/[id]/ops/daily — services, enrolment, POS, generation ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  // RBAC (iEduPro collection-policy parity): collecting is for teachers & cashiers
  // (daily.collect); configuration, enrolment, wallets & waivers need daily.manage.
  const MANAGE_ACTIONS = ["save-service", "toggle-service", "enroll", "unenroll", "settings-save", "wallet-adjust", "waive-charge", "waive-student-day"];
  const auth = await opsAuth(req, schoolId, "POST", {
    perms: MANAGE_ACTIONS.includes(action) ? ["daily.manage"] : ["daily.collect", "daily.manage"],
  });
  if (!auth.ok) return auth.res;
  const user = auth.user;

  if (action === "save-service") {
    const { id, name, category, priceCents, chargeMode, weekdays } = body;
    if (!name || !Number(priceCents)) return NextResponse.json({ error: "Name and price are required" }, { status: 400 });
    const data = { name, category: category ?? "OTHER", priceCents: Number(priceCents), chargeMode: chargeMode ?? "DAILY", weekdays: weekdays ?? "1,2,3,4,5" };
    const svc = id ? await db.dailyService.update({ where: { id }, data }) : await db.dailyService.create({ data: { ...data, schoolId } });
    return NextResponse.json({ service: svc });
  }

  if (action === "toggle-service") {
    const svc = await db.dailyService.findUnique({ where: { id: body.id } });
    if (!svc || svc.schoolId !== schoolId) return NextResponse.json({ error: "Service not found" }, { status: 404 });
    const updated = await db.dailyService.update({ where: { id: body.id }, data: { active: !svc.active } });
    return NextResponse.json({ service: updated });
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
    const res = await generateDayCharges(db, schoolId, date);
    return NextResponse.json(res);
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
    const { studentId, wallet, amountCents, note } = body;
    if (!studentId) return NextResponse.json({ error: "studentId required" }, { status: 400 });
    if (wallet !== "PREPAID" && wallet !== "TRANSPORT") return NextResponse.json({ error: "wallet must be PREPAID or TRANSPORT" }, { status: 400 });
    const amt = Math.round(Number(amountCents));
    if (!amt || amt <= 0) return NextResponse.json({ error: "Enter a top-up amount above zero" }, { status: 400 });
    const student = await db.student.findUnique({ where: { id: studentId } });
    if (!student) return NextResponse.json({ error: "Learner not found" }, { status: 404 });
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
    return NextResponse.json({ wallet: w, txn });
  }

  if (action === "wallet-adjust") {
    // correction: set a wallet to an exact balance (audit-trailed)
    const { studentId, wallet, amountCents, note } = body;
    if (!studentId) return NextResponse.json({ error: "studentId required" }, { status: 400 });
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
    return NextResponse.json({ wallet: w, txn });
  }

  if (action === "pos-collect") {
    const { studentId, amountCents, method, reference, payFrom, paidAt, transportUsage, markPresent } = body;
    const student = await db.student.findUnique({ where: { id: studentId } });
    if (!student) return NextResponse.json({ error: "Learner not found" }, { status: 404 });

    // transport usage first (iedupro §1): may waive or halve today's fare before settling
    if (transportUsage) {
      if (!TRANSPORT_USAGES.includes(transportUsage)) return NextResponse.json({ error: "Invalid transport usage" }, { status: 400 });
      await applyTransportUsage(db, schoolId, studentId, new Date(body.date ?? new Date().toISOString().slice(0, 10)), transportUsage as TransportUsage);
    }

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
    if (walletKind === "PREPAID") {
      const w = await db.studentWallet.findUnique({ where: { studentId } });
      if (!w || w.prepaidCents < amt) return NextResponse.json({ error: `Prepaid wallet short — balance is GH₵${(w?.prepaidCents ?? 0) / 100}` }, { status: 400 });
      await db.studentWallet.update({ where: { studentId }, data: { prepaidCents: { decrement: amt } } });
      const t = await db.walletTxn.create({ data: { schoolId, studentId, wallet: "PREPAID", kind: "PAY", amountCents: -amt, balanceAfter: w.prepaidCents - amt, note: reference ?? null, byName: user.fullName } });
      walletTxnId = t.id;
      payMethod = "WALLET";
    } else if (walletKind === "TRANSPORT") {
      const w = await db.studentWallet.findUnique({ where: { studentId } });
      if (!w || w.transportCents < amt) return NextResponse.json({ error: `Transport wallet short — balance is GH₵${(w?.transportCents ?? 0) / 100}` }, { status: 400 });
      await db.studentWallet.update({ where: { studentId }, data: { transportCents: { decrement: amt } } });
      const t = await db.walletTxn.create({ data: { schoolId, studentId, wallet: "TRANSPORT", kind: "PAY", amountCents: -amt, balanceAfter: w.transportCents - amt, note: reference ?? null, byName: user.fullName } });
      walletTxnId = t.id;
      payMethod = "TRANSPORT_WALLET";
      onlyCategory = "TRANSPORT"; // transport wallet may only clear transport-fare charges
    }

    const seq = (await db.payment.count({ where: { schoolId } })) + 1001;
    const payment = await db.payment.create({
      data: {
        receiptNo: `RCP-2026-${String(seq).padStart(5, "0")}`, studentId, category: "DAILY", schoolId,
        amountCents: amt, method: payMethod, reference: reference ?? null,
        receivedByName: user.fullName,
        paidAt: paidAt ? new Date(paidAt) : new Date(),
      },
    });
    const applied = await settleDailyCharges(db, payment.id, studentId, amt, onlyCategory);

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

    // auto receipt SMS
    const setting = await db.smsSetting.findUnique({ where: { schoolId } });
    if (setting?.autoPaymentReceipts) {
      await sendAutomated({
        db, schoolId, trigger: "PAYMENT_RECEIVED",
        defaultBody: `We have received GH₵${(amt / 100).toFixed(2)} for ${student.firstName} (${payment.receiptNo}) towards daily services. Thank you.`,
        vars: { student: student.firstName, amount: `GH₵${(amt / 100).toFixed(2)}`, receipt: payment.receiptNo, date: new Date().toLocaleDateString("en-GB") },
        studentId, recipientName: student.guardianName, recipientPhone: student.guardianPhone, sentByName: user.fullName,
      });
    }
    const receipt = await dailyServicesReceipt(db, payment.id);
    return NextResponse.json({ payment: { ...payment, applied }, student: `${student.firstName} ${student.lastName}`, receipt, walletTxnId, present });
  }

  if (action === "waive-charge") {
    const c = await db.dailyCharge.findUnique({ where: { id: body.chargeId } });
    if (!c || c.schoolId !== schoolId) return NextResponse.json({ error: "Charge not found" }, { status: 404 });
    const updated = await db.dailyCharge.update({ where: { id: body.chargeId }, data: { status: "WAIVED" } });
    return NextResponse.json({ charge: updated });
  }

  if (action === "waive-student-day") {
    const { studentId, date } = body;
    const d = new Date(date);
    const dStart = new Date(d); dStart.setHours(0, 0, 0, 0);
    const dEnd = new Date(d); dEnd.setHours(23, 59, 59, 999);
    const res = await db.dailyCharge.updateMany({ where: { schoolId, studentId, date: { gte: dStart, lte: dEnd }, status: "OWED" }, data: { status: "WAIVED" } });
    return NextResponse.json({ waived: res.count });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

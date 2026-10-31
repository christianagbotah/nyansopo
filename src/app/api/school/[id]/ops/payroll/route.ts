import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { calcPayslip, currentPeriod, ssnitBreakdown, Allowance, Deduction, DEFAULT_RATES, type StatutoryRates } from "@/lib/payroll";

/* statutory rates live in OpsSetting as percent strings keyed SSNIT_TIER1_EMPLOYEE etc. */
async function loadRates(schoolId: string): Promise<StatutoryRates> {
  const rows = await db.opsSetting.findMany({ where: { schoolId, key: { in: ["SSNIT_TIER1_EMPLOYEE", "SSNIT_TIER1_EMPLOYER", "SSNIT_TIER2", "GETFUND", "NHIL"] } } });
  const map = Object.fromEntries(rows.map((r) => [r.key, parseFloat(r.value)]));
  return {
    ssnitTier1Employee: (map.SSNIT_TIER1_EMPLOYEE ?? 5) / 100,
    ssnitTier1Employer: (map.SSNIT_TIER1_EMPLOYER ?? 13) / 100,
    ssnitTier2: (map.SSNIT_TIER2 ?? 5) / 100,
    getfund: (map.GETFUND ?? 2.5) / 100,
    nhil: (map.NHIL ?? 2.5) / 100,
  };
}

// ── GET /api/school/[id]/ops/payroll — staff setup, runs, payslips, statutory rates ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["payroll.view"] });
  if (!auth.ok) return auth.res;

  const url = new URL(req.url);
  const runId = url.searchParams.get("runId") ?? undefined;

  const [school, rates] = await Promise.all([db.school.findUnique({ where: { id: schoolId } }), loadRates(schoolId)]);
  const staff = await db.staff.findMany({ where: { schoolId }, orderBy: [{ active: "desc" }, { fullName: "asc" }] });
  const runs = await db.payrollRun.findMany({
    where: { schoolId },
    orderBy: { period: "desc" },
    include: { _count: { select: { payslips: true } } },
  });
  const selectedRun = runId ? runs.find((r) => r.id === runId) ?? null : runs[0] ?? null;
  const payslips = selectedRun
    ? await db.payslip.findMany({ where: { payrollRunId: selectedRun.id }, include: { staff: true }, orderBy: { staff: { fullName: "asc" } } })
    : [];
  // licensed Tier-2 / Tier-3 pension managers (iedupro parity directory)
  const providers = await db.pensionProvider.findMany({ where: { schoolId }, orderBy: { name: "asc" } });

  return NextResponse.json({
    school: school ? { name: school.name, motto: school.motto, town: school.town, region: school.region, phone: school.phone, email: school.email } : null,
    rates: {
      ssnitTier1Employee: Math.round(rates.ssnitTier1Employee * 1000) / 10,
      ssnitTier1Employer: Math.round(rates.ssnitTier1Employer * 1000) / 10,
      ssnitTier2: Math.round(rates.ssnitTier2 * 1000) / 10,
      getfund: Math.round(rates.getfund * 1000) / 10,
      nhil: Math.round(rates.nhil * 1000) / 10,
    },
    staff: staff.map((s) => ({
      id: s.id, fullName: s.fullName, role: s.role, title: s.title, phone: s.phone, email: s.email,
      category: s.category, position: s.position, qualification: s.qualification,
      department: s.department, staffCode: s.staffCode, tier2Provider: s.tier2Provider,
      accountNumber: s.accountNumber, bankName: s.bankName, tin: s.tin,
      salary: s.salaryCents, employmentType: s.employmentType, ssnitNumber: s.ssnitNumber,
      momoNumber: s.momoNumber, hiredAt: s.hiredAt, active: s.active,
    })),
    runs: runs.map((r) => ({
      id: r.id, period: r.period, status: r.status, payslipCount: r._count.payslips,
      gross: r.totalGrossCents, deductions: r.totalDeductionsCents, net: r.totalNetCents,
      employerSsnit: r.totalEmployerSsnitCents, approvedByName: r.approvedByName, paidAt: r.paidAt,
    })),
    selectedRun: selectedRun ? { id: selectedRun.id, period: selectedRun.period, status: selectedRun.status } : null,
    payslips: payslips.map((p) => ({
      id: p.id, staffId: p.staffId, staff: p.staff.fullName, role: p.staff.role, title: p.staff.title, ssnitNumber: p.staff.ssnitNumber, momoNumber: p.staff.momoNumber,
      position: p.staff.position, staffCode: p.staff.staffCode, accountNumber: p.staff.accountNumber, bankName: p.staff.bankName, tier2Provider: p.staff.tier2Provider,
      base: p.baseSalaryCents, allowances: JSON.parse(p.allowancesJson) as Allowance[],
      gross: p.grossCents, ssnitEmployee: p.ssnitEmployeeCents, ssnitEmployer: p.ssnitEmployerCents,
      paye: p.payeCents, otherDeductions: JSON.parse(p.otherDeductionsJson) as Deduction[],
      totalDeductions: p.totalDeductionsCents, net: p.netCents, status: p.status,
      ssnit: ssnitBreakdown(p.baseSalaryCents, rates),
    })),
    currentPeriod: currentPeriod(),
    providers: providers.map((p) => ({ id: p.id, name: p.name, code: p.code, contactPhone: p.contactPhone, active: p.active })),
  });
}

// ── POST /api/school/[id]/ops/payroll ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  const auth = await opsAuth(req, schoolId, "POST", { perms: (action === "save-staff" || action === "create-staff") ? ["staff.manage", "payroll.process"] : ["payroll.process"] });
  if (!auth.ok) return auth.res;
  const user = auth.user;

  if (action === "save-staff") {
    const { staffId, salaryCents, employmentType, ssnitNumber, momoNumber, active,
            fullName, role, title, phone, email, category, position, qualification, department,
            tier2Provider, accountNumber, bankName, tin } = body;
    const st = await db.staff.findUnique({ where: { id: staffId } });
    if (!st || st.schoolId !== schoolId) return NextResponse.json({ error: "Staff not found" }, { status: 404 });
    const updated = await db.staff.update({
      where: { id: staffId },
      data: {
        salaryCents: Number(salaryCents) || 0,
        employmentType: employmentType ?? st.employmentType,
        ssnitNumber: ssnitNumber ?? st.ssnitNumber,
        momoNumber: momoNumber ?? st.momoNumber,
        ...(typeof active === "boolean" ? { active } : {}),
        ...(fullName?.trim() ? { fullName: fullName.trim() } : {}),
        ...(role ? { role } : {}),
        ...(title !== undefined ? { title: title || null } : {}),
        ...(phone !== undefined ? { phone: phone || null } : {}),
        ...(email !== undefined ? { email: email || null } : {}),
        ...(category ? { category } : {}),
        ...(position !== undefined ? { position: position || null } : {}),
        ...(qualification !== undefined ? { qualification: qualification || null } : {}),
        ...(department !== undefined ? { department: department || null } : {}),
        ...(tier2Provider !== undefined ? { tier2Provider: tier2Provider || null } : {}),
        ...(accountNumber !== undefined ? { accountNumber: accountNumber || null } : {}),
        ...(bankName !== undefined ? { bankName: bankName || null } : {}),
        ...(tin !== undefined ? { tin: tin || null } : {}),
      },
    });
    return NextResponse.json({ staff: updated });
  }

  if (action === "create-staff") {
    const { fullName, role, category, position, phone, email, salaryCents, employmentType, ssnitNumber, momoNumber, qualification, department, tier2Provider, accountNumber, bankName } = body;
    if (!fullName?.trim()) return NextResponse.json({ error: "Full name is required" }, { status: 400 });
    const cat = category ?? (role === "HEADTEACHER" || role === "BURSAR" ? "ADMINISTRATION" : "TEACHING");
    const prefix = cat === "NON_TEACHING" ? "NTS" : cat === "ADMINISTRATION" ? "ADM" : "TCR";
    const seq = (await db.staff.count({ where: { schoolId } })) + 1;
    const created = await db.staff.create({
      data: {
        schoolId,
        fullName: String(fullName).trim(),
        role: role ?? (cat === "TEACHING" ? "TEACHER" : "ASSISTANT"),
        title: position ?? null,
        phone: phone || null,
        email: email || null,
        category: cat,
        position: position || null,
        qualification: qualification || null,
        department: department || null,
        staffCode: `${prefix}-${String(seq).padStart(5, "0")}`,
        salaryCents: Number(salaryCents) || 0,
        employmentType: employmentType ?? "FULL_TIME",
        ssnitNumber: ssnitNumber || null,
        momoNumber: momoNumber || null,
        tier2Provider: tier2Provider || null,
        accountNumber: accountNumber || null,
        bankName: bankName || null,
        hiredAt: new Date(),
      },
    });
    return NextResponse.json({ staff: created });
  }

  if (action === "create-run") {
    const period: string = body.period ?? currentPeriod();
    const existing = await db.payrollRun.findUnique({ where: { schoolId_period: { schoolId, period } } });
    if (existing) return NextResponse.json({ error: `A payroll run for ${period} already exists` }, { status: 400 });
    const staff = await db.staff.findMany({ where: { schoolId, active: true, salaryCents: { gt: 0 } } });
    if (!staff.length) return NextResponse.json({ error: "No salaried active staff — set up salaries first" }, { status: 400 });
    const rates = await loadRates(schoolId);
    const run = await db.payrollRun.create({ data: { schoolId, period, status: "DRAFT" } });
    let gross = 0, deduct = 0, net = 0, erSsnit = 0;
    for (const st of staff) {
      const calc = calcPayslip(st.salaryCents, [], [], rates);
      gross += calc.grossCents; deduct += calc.totalDeductionsCents; net += calc.netCents; erSsnit += calc.ssnitEmployerCents;
      await db.payslip.create({
        data: {
          payrollRunId: run.id, staffId: st.id, baseSalaryCents: st.salaryCents,
          allowancesJson: "[]", grossCents: calc.grossCents,
          ssnitEmployeeCents: calc.ssnitEmployeeCents, ssnitEmployerCents: calc.ssnitEmployerCents,
          payeCents: calc.payeCents, otherDeductionsJson: "[]",
          totalDeductionsCents: calc.totalDeductionsCents, netCents: calc.netCents,
        },
      });
    }
    const updated = await db.payrollRun.update({ where: { id: run.id }, data: { totalGrossCents: gross, totalDeductionsCents: deduct, totalNetCents: net, totalEmployerSsnitCents: erSsnit } });
    return NextResponse.json({ run: updated });
  }

  // statutory settings — iedupro “Statutory Settings” parity
  if (action === "save-rates") {
    const { ssnitTier1Employee, ssnitTier1Employer, ssnitTier2, getfund, nhil } = body;
    const entries: [string, number][] = [
      ["SSNIT_TIER1_EMPLOYEE", Number(ssnitTier1Employee)],
      ["SSNIT_TIER1_EMPLOYER", Number(ssnitTier1Employer)],
      ["SSNIT_TIER2", Number(ssnitTier2)],
      ["GETFUND", Number(getfund)],
      ["NHIL", Number(nhil)],
    ];
    for (const [key, val] of entries) {
      if (Number.isFinite(val) && val >= 0 && val <= 100) {
        await db.opsSetting.upsert({
          where: { schoolId_key: { schoolId, key } },
          update: { value: String(val), updatedAt: new Date() },
          create: { schoolId, key, value: String(val) },
        });
      }
    }
    return NextResponse.json({ ok: true });
  }

  // ── pension providers (licensed Tier-2 / Tier-3 managers directory) ──
  if (action === "save-provider") {
    const { id, name, code, contactPhone } = body;
    if (!name?.trim() || !code?.trim()) return NextResponse.json({ error: "Provider name and code are required" }, { status: 400 });
    const clash = await db.pensionProvider.findFirst({ where: { code: String(code).trim().toUpperCase(), ...(id ? { id: { not: id } } : {}) } });
    if (clash) return NextResponse.json({ error: "That provider code is already in use" }, { status: 400 });
    const data = { name: String(name).trim(), code: String(code).trim().toUpperCase(), contactPhone: contactPhone?.trim() || null };
    const p = id ? await db.pensionProvider.update({ where: { id }, data }) : await db.pensionProvider.create({ data: { ...data, schoolId } });
    return NextResponse.json({ provider: p });
  }

  if (action === "toggle-provider") {
    const p = await db.pensionProvider.findUnique({ where: { id: body.id } });
    if (!p || p.schoolId !== schoolId) return NextResponse.json({ error: "Provider not found" }, { status: 404 });
    const updated = await db.pensionProvider.update({ where: { id: p.id }, data: { active: !p.active } });
    return NextResponse.json({ provider: updated });
  }

  if (action === "delete-provider") {
    const p = await db.pensionProvider.findUnique({ where: { id: body.id } });
    if (!p || p.schoolId !== schoolId) return NextResponse.json({ error: "Provider not found" }, { status: 404 });
    await db.pensionProvider.delete({ where: { id: p.id } });
    return NextResponse.json({ ok: true });
  }

  if (action === "update-payslip") {
    const { payslipId, allowances, otherDeductions } = body;
    const p = await db.payslip.findUnique({ where: { id: payslipId }, include: { payrollRun: true } });
    if (!p || p.payrollRun.schoolId !== schoolId) return NextResponse.json({ error: "Payslip not found" }, { status: 404 });
    if (p.payrollRun.status !== "DRAFT") return NextResponse.json({ error: "Only DRAFT runs can be edited" }, { status: 400 });
    const allow: Allowance[] = Array.isArray(allowances) ? allowances : [];
    const ded: Deduction[] = Array.isArray(otherDeductions) ? otherDeductions : [];
    const rates = await loadRates(schoolId);
    const calc = calcPayslip(p.baseSalaryCents, allow, ded, rates);
    const updated = await db.payslip.update({
      where: { id: payslipId },
      data: {
        allowancesJson: JSON.stringify(allow), otherDeductionsJson: JSON.stringify(ded),
        grossCents: calc.grossCents, ssnitEmployeeCents: calc.ssnitEmployeeCents,
        ssnitEmployerCents: calc.ssnitEmployerCents, payeCents: calc.payeCents,
        totalDeductionsCents: calc.totalDeductionsCents, netCents: calc.netCents,
      },
    });
    // recompute run totals
    const slips = await db.payslip.findMany({ where: { payrollRunId: p.payrollRunId } });
    await db.payrollRun.update({
      where: { id: p.payrollRunId },
      data: {
        totalGrossCents: slips.reduce((s, x) => s + x.grossCents, 0),
        totalDeductionsCents: slips.reduce((s, x) => s + x.totalDeductionsCents, 0),
        totalNetCents: slips.reduce((s, x) => s + x.netCents, 0),
        totalEmployerSsnitCents: slips.reduce((s, x) => s + x.ssnitEmployerCents, 0),
      },
    });
    return NextResponse.json({ payslip: updated });
  }

  if (action === "delete-run") {
    const run = await db.payrollRun.findUnique({ where: { id: body.runId } });
    if (!run || run.schoolId !== schoolId) return NextResponse.json({ error: "Run not found" }, { status: 404 });
    if (run.status !== "DRAFT") return NextResponse.json({ error: "Only DRAFT runs can be deleted" }, { status: 400 });
    await db.payslip.deleteMany({ where: { payrollRunId: run.id } });
    await db.payrollRun.delete({ where: { id: run.id } });
    return NextResponse.json({ ok: true });
  }

  if (action === "approve-run") {
    const run = await db.payrollRun.findUnique({ where: { id: body.runId } });
    if (!run || run.schoolId !== schoolId) return NextResponse.json({ error: "Run not found" }, { status: 404 });
    if (run.status !== "DRAFT") return NextResponse.json({ error: "Run is not a draft" }, { status: 400 });
    const updated = await db.payrollRun.update({ where: { id: run.id }, data: { status: "APPROVED", approvedByName: user.fullName } });
    return NextResponse.json({ run: updated });
  }

  if (action === "pay-run") {
    const run = await db.payrollRun.findUnique({ where: { id: body.runId } });
    if (!run || run.schoolId !== schoolId) return NextResponse.json({ error: "Run not found" }, { status: 404 });
    if (run.status !== "APPROVED") return NextResponse.json({ error: "Approve the run before paying" }, { status: 400 });
    await db.payslip.updateMany({ where: { payrollRunId: run.id }, data: { status: "PAID", paidAt: new Date() } });
    const updated = await db.payrollRun.update({ where: { id: run.id }, data: { status: "PAID", paidAt: new Date() } });
    return NextResponse.json({ run: updated });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

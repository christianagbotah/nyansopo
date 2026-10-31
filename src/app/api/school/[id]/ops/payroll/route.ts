import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { calcPayslip, currentPeriod, Allowance, Deduction } from "@/lib/payroll";

// ── GET /api/school/[id]/ops/payroll — staff setup, runs, payslips ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET");
  if (!auth.ok) return auth.res;

  const url = new URL(req.url);
  const runId = url.searchParams.get("runId") ?? undefined;

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

  return NextResponse.json({
    staff: staff.map((s) => ({
      id: s.id, fullName: s.fullName, role: s.role, title: s.title, phone: s.phone,
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
      id: p.id, staffId: p.staffId, staff: p.staff.fullName, role: p.staff.role, momoNumber: p.staff.momoNumber,
      base: p.baseSalaryCents, allowances: JSON.parse(p.allowancesJson) as Allowance[],
      gross: p.grossCents, ssnitEmployee: p.ssnitEmployeeCents, ssnitEmployer: p.ssnitEmployerCents,
      paye: p.payeCents, otherDeductions: JSON.parse(p.otherDeductionsJson) as Deduction[],
      totalDeductions: p.totalDeductionsCents, net: p.netCents, status: p.status,
    })),
    currentPeriod: currentPeriod(),
  });
}

// ── POST /api/school/[id]/ops/payroll ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST");
  if (!auth.ok) return auth.res;
  const user = auth.user;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;

  if (action === "save-staff") {
    const { staffId, salaryCents, employmentType, ssnitNumber, momoNumber, active } = body;
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
      },
    });
    return NextResponse.json({ staff: updated });
  }

  if (action === "create-run") {
    const period: string = body.period ?? currentPeriod();
    const existing = await db.payrollRun.findUnique({ where: { schoolId_period: { schoolId, period } } });
    if (existing) return NextResponse.json({ error: `A payroll run for ${period} already exists` }, { status: 400 });
    const staff = await db.staff.findMany({ where: { schoolId, active: true, salaryCents: { gt: 0 } } });
    if (!staff.length) return NextResponse.json({ error: "No salaried active staff — set up salaries first" }, { status: 400 });
    const run = await db.payrollRun.create({ data: { schoolId, period, status: "DRAFT" } });
    let gross = 0, deduct = 0, net = 0, erSsnit = 0;
    for (const st of staff) {
      const calc = calcPayslip(st.salaryCents, [], []);
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

  if (action === "update-payslip") {
    const { payslipId, allowances, otherDeductions } = body;
    const p = await db.payslip.findUnique({ where: { id: payslipId }, include: { payrollRun: true } });
    if (!p || p.payrollRun.schoolId !== schoolId) return NextResponse.json({ error: "Payslip not found" }, { status: 404 });
    if (p.payrollRun.status !== "DRAFT") return NextResponse.json({ error: "Only DRAFT runs can be edited" }, { status: 400 });
    const allow: Allowance[] = Array.isArray(allowances) ? allowances : [];
    const ded: Deduction[] = Array.isArray(otherDeductions) ? otherDeductions : [];
    const calc = calcPayslip(p.baseSalaryCents, allow, ded);
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

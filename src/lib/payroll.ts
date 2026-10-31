// ── Ghana payroll maths: SSNIT Tier 1/2 + PAYE progressive tax ──
// SSNIT (Social Security & National Insurance Trust):
//   Tier 1: 5% employee + 13% employer of basic salary (up to the SSNIT cap)
//   Tier 2: 5% employer of basic salary (occupational pension, always uncapped)
// PAYE (Progressive annual bands, converted to monthly — 2024+ schedule):
//   First GH₵6,144 p.a. (₵512/mo) .............. 0%
//   Next  GH₵2,208 (₵184/mo) ................... 5%
//   Next  GH₵2,616 (₵218/mo) ................... 10%
//   Next  GH₵36,480 (₵3,040/mo) ................ 17.5%
//   Next  GH₵19,452 (₵1,621/mo) ................ 25%
//   Next  GH₵17,592 (₵1,466/mo) ................ 30%
//   Excess over GH₵84,492 (₵7,041/mo) .......... 35%
// Taxable income = gross − SSNIT employee contribution.

export type Allowance = { name: string; amountCents: number };
export type Deduction = { name: string; amountCents: number };

export const SSNIT_CAP_CENTS = 35_000_00; // GH₵35,000/month maximum pensionable earnings

/** Employee Tier-1 contribution: 5% of basic, capped. */
export function ssnitEmployeeCents(basicCents: number): number {
  return Math.round(Math.min(basicCents, SSNIT_CAP_CENTS) * 0.05);
}

/** Employer contribution: 13% Tier 1 (capped) + 5% Tier 2 (uncapped). */
export function ssnitEmployerCents(basicCents: number): number {
  const tier1 = Math.round(Math.min(basicCents, SSNIT_CAP_CENTS) * 0.13);
  const tier2 = Math.round(basicCents * 0.05);
  return tier1 + tier2;
}

const PAYE_BANDS_CENTS: { limit: number; rate: number }[] = [
  { limit: 512_00, rate: 0 },      // first
  { limit: 184_00, rate: 0.05 },
  { limit: 218_00, rate: 0.10 },
  { limit: 3_040_00, rate: 0.175 },
  { limit: 1_621_00, rate: 0.25 },
  { limit: 1_466_00, rate: 0.30 },
  { limit: Infinity, rate: 0.35 },  // excess
];

/** PAYE on monthly taxable income (gross − employee SSNIT). */
export function payeCents(taxableCents: number): number {
  let remaining = Math.max(0, taxableCents);
  let tax = 0;
  for (const band of PAYE_BANDS_CENTS) {
    if (remaining <= 0) break;
    const slice = Math.min(remaining, band.limit);
    tax += Math.round(slice * band.rate);
    remaining -= slice;
  }
  return tax;
}

export type PayslipCalc = {
  baseSalaryCents: number;
  allowances: Allowance[];
  grossCents: number;
  ssnitEmployeeCents: number;
  ssnitEmployerCents: number;
  payeCents: number;
  otherDeductions: Deduction[];
  totalDeductionsCents: number;
  netCents: number;
};

/** Full monthly payslip for one staff member. All amounts in pesewas. */
export function calcPayslip(
  baseSalaryCents: number,
  allowances: Allowance[] = [],
  otherDeductions: Deduction[] = []
): PayslipCalc {
  const allowanceTotal = allowances.reduce((s, a) => s + a.amountCents, 0);
  const gross = baseSalaryCents + allowanceTotal;
  const ssnitEmp = ssnitEmployeeCents(baseSalaryCents);
  const ssnitEr = ssnitEmployerCents(baseSalaryCents);
  // PAYE is charged on gross minus employee SSNIT (allowances are taxed too)
  const paye = payeCents(gross - ssnitEmp);
  const otherTotal = otherDeductions.reduce((s, d) => s + d.amountCents, 0);
  const totalDeductions = ssnitEmp + paye + otherTotal;
  return {
    baseSalaryCents,
    allowances,
    grossCents: gross,
    ssnitEmployeeCents: ssnitEmp,
    ssnitEmployerCents: ssnitEr,
    payeCents: paye,
    otherDeductions,
    totalDeductionsCents: totalDeductions,
    netCents: gross - totalDeductions,
  };
}

export const currentPeriod = (d = new Date()) =>
  `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;

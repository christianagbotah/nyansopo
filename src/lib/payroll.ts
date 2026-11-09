// ── Ghana payroll maths: SSNIT Tier 1/2 + PAYE progressive tax ──
// SSNIT (Social Security & National Insurance Trust):
//   Tier 1: 5% employee + 13% employer of basic salary (up to the SSNIT cap)
//   Tier 2: 5% employer of basic salary (occupational pension, always uncapped)
// PAYE (Progressive annual bands, converted to monthly — 2024+ schedule):
//   First ₵6,144 p.a. (₵512/mo) .............. 0%
//   Next  ₵2,208 (₵184/mo) ................... 5%
//   Next  ₵2,616 (₵218/mo) ................... 10%
//   Next  ₵36,480 (₵3,040/mo) ................ 17.5%
//   Next  ₵19,452 (₵1,621/mo) ................ 25%
//   Next  ₵17,592 (₵1,466/mo) ................ 30%
//   Excess over ₵84,492 (₵7,041/mo) .......... 35%
// Taxable income = gross − SSNIT employee contribution.

export type Allowance = { name: string; amountCents: number };
export type Deduction = { name: string; amountCents: number };

/* ── Statutory rates — school-configurable (iedupro “Statutory Settings” parity) ── */
export type StatutoryRates = {
  ssnitTier1Employee: number; // fraction, e.g. 0.05
  ssnitTier1Employer: number; // e.g. 0.135
  ssnitTier2: number; // employee occupational pension deduction, e.g. 0.05
  getfund: number; // employer levy on basic
  nhil: number;
};
export const DEFAULT_RATES: StatutoryRates = {
  ssnitTier1Employee: 0.05,
  ssnitTier1Employer: 0.13,
  ssnitTier2: 0.05,
  getfund: 0.025,
  nhil: 0.025,
};

export const SSNIT_CAP_CENTS = 35_000_00; // ₵35,000/month maximum pensionable earnings

/** Employee SSNIT deduction: Tier-1 employee share + Tier-2, capped at the SSNIT ceiling. */
export function ssnitEmployeeCents(basicCents: number, r: StatutoryRates = DEFAULT_RATES): number {
  return Math.round(Math.min(basicCents, SSNIT_CAP_CENTS) * (r.ssnitTier1Employee + r.ssnitTier2));
}

/** Employer contribution: Tier-1 employer (capped) + Tier-2 + GETFund/NHIL levies. */
export function ssnitEmployerCents(basicCents: number, r: StatutoryRates = DEFAULT_RATES): number {
  const capped = Math.min(basicCents, SSNIT_CAP_CENTS);
  const tier1 = Math.round(capped * r.ssnitTier1Employer);
  const tier2 = Math.round(capped * r.ssnitTier2);
  const levies = Math.round(basicCents * ((r.getfund ?? 0) + (r.nhil ?? 0)));
  return tier1 + tier2 + levies;
}

/** SSNIT-only employer cost (for the Tier-1 / Tier-2 statutory reports). */
export function ssnitBreakdown(basicCents: number, r: StatutoryRates = DEFAULT_RATES) {
  const capped = Math.min(basicCents, SSNIT_CAP_CENTS);
  return {
    tier1Employee: Math.round(capped * r.ssnitTier1Employee),
    tier1Employer: Math.round(capped * r.ssnitTier1Employer),
    tier2: Math.round(capped * r.ssnitTier2),
    getfund: Math.round(basicCents * (r.getfund ?? 0)),
    nhil: Math.round(basicCents * (r.nhil ?? 0)),
  };
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
  otherDeductions: Deduction[] = [],
  rates: StatutoryRates = DEFAULT_RATES
): PayslipCalc {
  const allowanceTotal = allowances.reduce((s, a) => s + a.amountCents, 0);
  const gross = baseSalaryCents + allowanceTotal;
  const ssnitEmp = ssnitEmployeeCents(baseSalaryCents, rates);
  const ssnitEr = ssnitEmployerCents(baseSalaryCents, rates);
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

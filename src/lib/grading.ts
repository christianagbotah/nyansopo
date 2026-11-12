// Grading scales per curriculum — GES (SBC/CCP + WAEC-style), Cambridge CAIE, IB.
export type GradeBand = { min: number; grade: string; label: string };

export const SCALES: Record<string, { name: string; bands: GradeBand[]; examLabel: string }> = {
  GES: {
    name: "GES Standards-Based / Common Core (BECE-style)",
    examLabel: "End-of-Term Exam (70%)",
    bands: [
      { min: 80, grade: "1", label: "Advanced" },
      { min: 68, grade: "2", label: "Proficient" },
      { min: 55, grade: "3", label: "Approaching Proficiency" },
      { min: 40, grade: "4", label: "Developing" },
      { min: 0, grade: "5", label: "Beginning" },
    ],
  },
  WAEC: {
    name: "WAEC WASSCE (A1–F9, best-6 aggregate)",
    examLabel: "WASSCE Paper",
    bands: [
      { min: 75, grade: "A1", label: "Excellent" },
      { min: 70, grade: "B2", label: "Very Good" },
      { min: 65, grade: "B3", label: "Good" },
      { min: 60, grade: "C4", label: "Credit" },
      { min: 55, grade: "C5", label: "Credit" },
      { min: 50, grade: "C6", label: "Credit" },
      { min: 45, grade: "D7", label: "Pass" },
      { min: 40, grade: "E8", label: "Pass" },
      { min: 0, grade: "F9", label: "Fail" },
    ],
  },
  CAIE: {
    name: "Cambridge (CAIE) 9–1 / A*–G",
    examLabel: "Cambridge Progression / Checkpoint",
    bands: [
      { min: 90, grade: "A*", label: "Outstanding" },
      { min: 80, grade: "A", label: "Excellent" },
      { min: 70, grade: "B", label: "Good" },
      { min: 60, grade: "C", label: "Satisfactory" },
      { min: 50, grade: "D", label: "Borderline" },
      { min: 40, grade: "E", label: "Weak" },
      { min: 0, grade: "U", label: "Ungraded" },
    ],
  },
  IB: {
    name: "IB 1–7 scale with descriptors",
    examLabel: "IB Summative Task",
    bands: [
      { min: 85, grade: "7", label: "Excellent" },
      { min: 72, grade: "6", label: "Very Good" },
      { min: 58, grade: "5", label: "Good" },
      { min: 44, grade: "4", label: "Satisfactory" },
      { min: 30, grade: "3", label: "Mediocre" },
      { min: 16, grade: "2", label: "Poor" },
      { min: 0, grade: "1", label: "Very Poor" },
    ],
  },
};

export function gradeFor(curriculum: string, pct: number): { grade: string; label: string } {
  const scale = curriculum === "GES" ? SCALES.GES : curriculum === "CAIE" ? SCALES.CAIE : curriculum === "IB" ? SCALES.IB : SCALES.GES;
  const band = scale.bands.find((b) => pct >= b.min) ?? scale.bands[scale.bands.length - 1];
  return { grade: band.grade, label: band.label };
}

export function scaleFor(curriculum: string) {
  return curriculum === "GES" ? SCALES.GES : curriculum === "CAIE" ? SCALES.CAIE : curriculum === "IB" ? SCALES.IB : SCALES.GES;
}

export const CURRICULUM_META: Record<string, { label: string; blurb: string; stages: string[] }> = {
  GES: { label: "GES (NaCCA)", blurb: "Standards-Based Curriculum for KG–B6 and the Common Core Programme for B7–B9, aligned to BECE.", stages: ["Kindergarten (SBC)", "Primary (SBC)", "Common Core (CCP)"] },
  CAIE: { label: "Cambridge (CAIE)", blurb: "Cambridge Primary, Lower Secondary, IGCSE and A/AS Level pathways.", stages: ["Cambridge Primary", "Cambridge Lower Secondary", "Cambridge Upper Secondary / IGCSE", "Cambridge Advanced / A-Level"] },
  IB: { label: "IB World School", blurb: "PYP, MYP and Diploma Programme with inquiry-based assessment.", stages: ["IB Primary Years Programme", "IB Middle Years Programme", "IB Diploma Programme"] },
};

/* ═══════════════════════════════════════════════════════════════
   WAVE 20 — Ghana BECE-style term-result computations (pure).
   Ghana terminal-report doctrine: class assessment carries 30%
   and the end-of-term exam 70%, so a learner's subject total sits
   on the familiar /100 scale graded A1..F9 (WAEC BECE bands).
   All functions here are side-effect free so the API route, the
   seed script and (potentially) offline devices share one source
   of grading truth.
   ═══════════════════════════════════════════════════════════════ */

/** Ghana standard: class assessment out of 30, end-of-term exam out of 70. */
export const CLASS_SCORE_MAX = 30;
export const EXAM_SCORE_MAX = 70;

const round1 = (n: number) => Math.round(n * 10) / 10;
const clamp = (v: number, max: number) => Math.max(0, Math.min(max, v));

/** weightedTotal(classScore, examScore) — the BECE 30/70 composition.
 *  Inputs are already on their canonical scales (class ≤30, exam ≤70);
 *  missing components count as 0, but a row with NEITHER component is
 *  null (nothing was assessed — the subject should not appear on the
 *  learner's report). Result is rounded to 1 decimal. */
export function weightedTotal(
  classScore: number | null | undefined,
  examScore: number | null | undefined,
): number | null {
  const cs = typeof classScore === "number" && Number.isFinite(classScore) ? clamp(classScore, CLASS_SCORE_MAX) : null;
  const es = typeof examScore === "number" && Number.isFinite(examScore) ? clamp(examScore, EXAM_SCORE_MAX) : null;
  if (cs === null && es === null) return null;
  return round1((cs ?? 0) + (es ?? 0));
}

/** gradeBand(total) — WAEC BECE band for a /100 total: A1 (Excellent)
 *  down to F9 (Fail). Returns null when nothing was assessed. */
export function gradeBand(total: number | null | undefined): { grade: string; label: string } | null {
  if (typeof total !== "number" || !Number.isFinite(total)) return null;
  const t = clamp(total, 100);
  const band = SCALES.WAEC.bands.find((b) => t >= b.min) ?? SCALES.WAEC.bands[SCALES.WAEC.bands.length - 1];
  return { grade: band.grade, label: band.label };
}

/** Numeric value of a WAEC band (A1=1 … F9=9). Accepts the numeric GES
 *  forms ("1".."5") too. Null for unrecognised grades. */
export function gradeValue(grade: string | null | undefined): number | null {
  if (!grade) return null;
  const g = grade.trim().toUpperCase();
  const waec = SCALES.WAEC.bands.findIndex((b) => b.grade === g);
  if (waec >= 0) return waec + 1;
  const num = Number(g);
  if (Number.isInteger(num) && num >= 1 && num <= 9) return num;
  return null;
}

/** rankClass(results) — competition ranking ("1224" style): learners
 *  with the same total share a position and the next distinct total
 *  takes its index+1. Learners without a total (nothing assessed) get
 *  no position. Returns a Map keyed by the row id. */
export function rankClass(results: { id: string; total: number | null }[]): Map<string, number> {
  const scored = results
    .filter((r) => typeof r.total === "number" && Number.isFinite(r.total))
    .sort((a, b) => (b.total as number) - (a.total as number));
  const out = new Map<string, number>();
  let pos = 0;
  let prev: number | null = null;
  for (const [i, r] of scored.entries()) {
    if (prev === null || (r.total as number) !== prev) {
      pos = i + 1;
      prev = r.total as number;
    }
    out.set(r.id, pos);
  }
  return out;
}

export type AggregateEntry = { grade: string | null; isCore?: boolean };

/** bestSixAggregate — BECE aggregate doctrine: the four core subjects
 *  (English, Maths, Science, Social Studies — flagged isCore) count
 *  first, then the two best remaining subjects; each subject's band is
 *  worth its value (A1=1 … F9=9) and LOWER aggregates are better
 *  (aggregate 6 = six A1s). Falls back to whatever exists when the
 *  class offers fewer than six graded subjects; null when nothing was
 *  graded. */
export function bestSixAggregate(entries: AggregateEntry[]): number | null {
  const scored = entries
    .map((e) => ({ ...e, v: gradeValue(e.grade) }))
    .filter((e): e is AggregateEntry & { v: number } => e.v !== null)
    .sort((a, b) => a.v - b.v);
  if (!scored.length) return null;
  const cores = scored.filter((e) => e.isCore).slice(0, 4);
  const picked = [...cores];
  for (const e of scored) {
    if (picked.length >= 6) break;
    if (cores.includes(e)) continue;
    picked.push(e);
  }
  return picked.reduce((s, e) => s + e.v, 0);
}

/** Short per-subject remark from the band label — the classic one-word
 *  column on a Ghana terminal report. */
export function resultRemark(total: number | null | undefined): string | null {
  const band = gradeBand(total);
  if (!band) return null;
  if (band.grade === "A1") return "Excellent";
  if (band.grade === "B2") return "Very good";
  if (band.grade === "B3") return "Good";
  if (band.grade === "C4" || band.grade === "C5" || band.grade === "C6") return "Credit";
  if (band.grade === "D7" || band.grade === "E8") return "Pass";
  return "Fail";
}

/** Class-teacher / head-teacher remarks from the term average — the
 *  deterministic fallback used when the school has no RemarkTemplate
 *  library for the band. */
export function autoRemarks(average: number | null | undefined): { teacherRemark: string; headRemark: string } {
  const a = typeof average === "number" && Number.isFinite(average) ? average : 0;
  if (a >= 80) return { teacherRemark: "An excellent term's work — keep soaring.", headRemark: "Outstanding result. A model learner for the school." };
  if (a >= 65) return { teacherRemark: "Very good performance — aim even higher next term.", headRemark: "A strong result. The school is proud of this effort." };
  if (a >= 55) return { teacherRemark: "Good effort — more attention in the weak areas will help.", headRemark: "Satisfactory progress with clear room to improve." };
  if (a >= 40) return { teacherRemark: "A fair result — serious revision is needed next term.", headRemark: "Must improve. Closer supervision has been arranged." };
  return { teacherRemark: "Below expectation — needs close support at home and at school.", headRemark: "Unsatisfactory. Parent engagement is advised." };
}

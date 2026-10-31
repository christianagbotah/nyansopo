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

// Client-safe grading scale table (mirrors src/lib/grading.ts without server imports)
export const SCALES_TABLE: Record<string, { name: string; blurb: string; examLabel: string; bands: { min: number; grade: string; label: string }[] }> = {
  GES: {
    name: "GES — NaCCA Standards-Based / Common Core",
    blurb: "Standards-Based Curriculum for KG–B6 and the Common Core Programme for B7–B9, with BECE-style proficiency grading.",
    examLabel: "End-of-Term Exam (70%)",
    bands: [
      { min: 80, grade: "1", label: "Advanced" },
      { min: 68, grade: "2", label: "Proficient" },
      { min: 55, grade: "3", label: "Approaching Proficiency" },
      { min: 40, grade: "4", label: "Developing" },
      { min: 0, grade: "5", label: "Beginning" },
    ],
  },
  CAIE: {
    name: "Cambridge — CAIE",
    blurb: "Cambridge Primary, Lower Secondary, IGCSE and A/AS Level pathways with A*–U grading.",
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
    name: "IB World School",
    blurb: "PYP, MYP and Diploma Programme with the 1–7 scale and inquiry-based assessment.",
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

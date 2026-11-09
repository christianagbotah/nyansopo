// ── Multi-curriculum metadata (GES · CAIE · IB) ────────────────────────────
// International schools in Ghana (Datus International, Tema International,
// Galaxy International…) run SEVERAL curricula side by side — the national
// GES track plus Cambridge/IB. Every curriculum-aware surface (settings,
// lesson notes, framework manager, class bands) reads from here.

export type CurriculumKey = "GES" | "CAIE" | "IB";

export const CURRICULA: Record<CurriculumKey, {
  label: string;
  full: string;
  blurb: string;
  /** canonical grade-level band labels per curriculum (for class naming hints) */
  bands: { key: string; label: string; grades: string[] }[];
  /** NaCCA core competencies (GES) / Cambridge learner attributes / IB learner profile */
  competenciesLabel: string;
  competencies: string[];
  /** what the framework hierarchy is called in this curriculum */
  hierarchy: { strand: string; subStrand: string; standard: string; indicator: string };
}> = {
  GES: {
    label: "GES",
    full: "GES — NaCCA Standards-Based Curriculum (KG–B6) & Common Core Programme (B7–B9)",
    blurb: "NaCCA SBC/CCP: strands → sub-strands → content standards → indicators, with core competencies. The national track for KG through JHS.",
    bands: [
      { key: "KG", label: "Kindergarten", grades: ["KG1", "KG2"] },
      { key: "PRIMARY", label: "Primary (B1–B6)", grades: ["B1", "B2", "B3", "B4", "B5", "B6"] },
      { key: "JHS", label: "Junior High (B7–B9)", grades: ["B7", "B8", "B9"] },
    ],
    competenciesLabel: "Core competencies (NaCCA)",
    competencies: [
      "Critical Thinking and Problem Solving",
      "Creativity and Innovation",
      "Communication and Collaboration",
      "Cultural Identity and Global Citizenship",
      "Personal Development and Leadership",
      "Digital Literacy",
    ],
    hierarchy: { strand: "Strand", subStrand: "Sub-strand", standard: "Content standard", indicator: "Learning indicator" },
  },
  CAIE: {
    label: "Cambridge",
    full: "Cambridge Pathway — CAIE Primary / Lower Secondary (Checkpoint) / IGCSE",
    blurb: "Cambridge Primary & Lower Secondary: five content areas per subject mapped to Stages, with learning objectives and Cambridge learner attributes.",
    bands: [
      { key: "PRIMARY", label: "Cambridge Primary (Stages 1–6)", grades: ["Stage 1", "Stage 2", "Stage 3", "Stage 4", "Stage 5", "Stage 6"] },
      { key: "LOWERSEC", label: "Lower Secondary / Checkpoint", grades: ["Stage 7", "Stage 8", "Stage 9"] },
      { key: "IGCSE", label: "IGCSE / O Level", grades: ["IGCSE Y1", "IGCSE Y2"] },
    ],
    competenciesLabel: "Cambridge learner attributes",
    competencies: [
      "Confident",
      "Responsible",
      "Reflective",
      "Innovative",
      "Engaged",
    ],
    hierarchy: { strand: "Content area", subStrand: "Sub-area", standard: "Learning objective", indicator: "Skill focus" },
  },
  IB: {
    label: "IB",
    full: "IB World School — Primary Years Programme (PYP) · Middle Years (MYP) · Diploma (DP)",
    blurb: "PYP units of inquiry under the six transdisciplinary themes, driven by key concepts and the learner profile; central ideas & lines of inquiry are school-authored.",
    bands: [
      { key: "PYP", label: "PYP (ages 3–12)", grades: ["PYP 1", "PYP 2", "PYP 3", "PYP 4", "PYP 5", "PYP 6"] },
      { key: "MYP", label: "MYP (ages 11–16)", grades: ["MYP 1", "MYP 2", "MYP 3", "MYP 4", "MYP 5"] },
      { key: "DP", label: "Diploma Programme", grades: ["DP Y1", "DP Y2"] },
    ],
    competenciesLabel: "IB learner profile",
    competencies: [
      "Inquirers",
      "Knowledgeable",
      "Thinkers",
      "Communicators",
      "Principled",
      "Open-minded",
      "Caring",
      "Risk-takers",
      "Balanced",
      "Reflective",
    ],
    hierarchy: { strand: "Transdisciplinary theme", subStrand: "Key concept", standard: "Central idea", indicator: "Line of inquiry" },
  },
};

export const CURRICULUM_KEYS: CurriculumKey[] = ["GES", "CAIE", "IB"];

export function isCurriculumKey(v: string): v is CurriculumKey {
  return (CURRICULUM_KEYS as string[]).includes(v);
}

export function curriculumLabel(v: string): string {
  return isCurriculumKey(v) ? CURRICULA[v].label : v;
}

/** A school's enabled curricula: JSON `curricula` if set, else the single primary. */
export function schoolCurricula(school: { curriculum: string; curricula?: string | null }): CurriculumKey[] {
  if (school.curricula) {
    try {
      const list = JSON.parse(school.curricula) as string[];
      const valid = list.filter(isCurriculumKey) as CurriculumKey[];
      if (valid.length) return valid;
    } catch { /* fall through */ }
  }
  return [isCurriculumKey(school.curriculum) ? (school.curriculum as CurriculumKey) : "GES"];
}

/** Which framework grade-level string matches a classroom's grade for a given curriculum. */
export function frameworkGrade(curriculum: string, gradeLevel: string): string {
  const g = gradeLevel.trim().toUpperCase();
  if (curriculum === "CAIE") {
    const m = g.match(/^B(\d)$/) ?? g.match(/^YEAR (\d)$/) ?? g.match(/^PYP (\d)$/);
    if (m) return `Stage ${m[1]}`;
    return gradeLevel.includes("Stage") ? gradeLevel : gradeLevel;
  }
  if (curriculum === "IB") {
    const m = g.match(/^B(\d)$/) ?? g.match(/^YEAR (\d)$/) ?? g.match(/^STAGE (\d)$/);
    if (m) return `PYP ${m[1]}`;
    return gradeLevel;
  }
  // GES: B1..B9 canonical; Stage n (Cambridge track class) maps to Bn
  const m = g.match(/^STAGE (\d)$/) ?? g.match(/^YEAR (\d)$/) ?? g.match(/^PYP (\d)$/);
  if (m && Number(m[1]) >= 1 && Number(m[1]) <= 9) return `B${m[1]}`;
  return gradeLevel;
}

/** Case-insensitive subject matching against the framework library. */
export function frameworkSubjectKey(name: string): string {
  return name.trim().toLowerCase();
}

/**
 * Curriculum Framework Library seed — REAL published taxonomy, idempotent.
 *
 * Sources (verified against NaCCA / Cambridge / IB publications):
 *  • GES Mathematics B1–B6  — 4 strands: Number · Algebra · Geometry and
 *    Measurement · Data (NaCCA MATHS-LOWER-PRIMARY-B1-B3 / UPPER-PRIMARY-B4-B6);
 *    B7–B9 Common Core Programme uses "Data & Probability" as the 4th strand.
 *    Number sub-strands: Counting, Representation, Cardinality & Ordinality ·
 *    Number Operations (Addition, Subtraction, Multiplication and Division) ·
 *    Fractions · Money. Algebra → Patterns and Relationships. Data → Data
 *    Collection, Organisation, Presentation & Interpretation.
 *  • GES English B4–B6 — Oral Language (Listening and Speaking) · Reading ·
 *    Grammar Usage · Writing · Literature.
 *  • GES Science B4–B6 — Diversity of Matter · Cycles · Systems · Forces &
 *    Energy · Humans & the Environment (verified sub-strands: Living and
 *    non-living things; Life cycles of organisms; The human body systems).
 *  • GES RME B1–B6 / CCP — God, His Creation and Attributes · Religious
 *    Practices · The Family and the Community · Authority and Obedience.
 *  • GES Computing — Introduction to Computing · Productivity Software ·
 *    Communication Networks · Computational Thinking.
 *  • GES Creative Arts & Design — Visual Arts · Performing Arts.
 *  • Content standard exemplar B4.1.1.1 (+indicator B4.1.1.1.1) — NaCCA B4
 *    maths, Number strand, sub-strand 1: place value for whole numerals up
 *    to 100,000.
 *  • Cambridge Primary (CAIE) Maths — 5 content areas: Number · Geometry ·
 *    Measure · Handling data · Problem solving; Number sub-strands: Numbers
 *    and the number system · Calculation — mental strategies · Calculation —
 *    addition and subtraction · Calculation — multiplication and division ·
 *    Fractions, decimals, percentages, ratio and proportion.
 *  • Cambridge Primary English — Phonics, spelling and vocabulary · Grammar
 *    and punctuation · Reading · Writing · Speaking and listening.
 *  • Cambridge Primary Science — Biology · Chemistry · Physics · Earth and
 *    Space · Thinking and Working Scientifically.
 *  • IB PYP — 6 transdisciplinary themes (Who we are; Where we are in place
 *    and time; How we express ourselves; How the world works; How we organize
 *    ourselves; Sharing the planet), key concepts as sub-strands; central
 *    ideas & lines of inquiry are school-authored (examples provided).
 *
 * Schools can extend everything through the Framework manager (import/CRUD).
 */
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

type Indicator = { code?: string; statement: string }[];
type Standard = { code?: string; statement: string; indicators?: Indicator }[];
type Sub = { code?: string; name: string; standards?: Standard }[];
type Strand = { code?: string; name: string; subs?: Sub }[];

type FrameworkRow = { curriculum: "GES" | "CAIE" | "IB"; subjectName: string; gradeLevel: string; strands: Strand[] };

/* ── shared GES strand definitions ─────────────────────────────── */
const gesMathsSubsNumber = (): Sub => [
  { code: ".1", name: "Counting, Representation, Cardinality & Ordinality", standards: [{
    code: ".1", statement: "Demonstrate understanding of quantities and place value for multi-digit whole numerals up to 100,000",
    indicators: [{ code: ".1", statement: "Model number quantities and place value for multi-digit whole numbers up to 100,000 using graph sheets or bundles of sticks" }],
  }] },
  { code: ".2", name: "Number Operations (Addition, Subtraction, Multiplication and Division)" },
  { code: ".3", name: "Fractions" },
  { code: ".4", name: "Money" },
];
const gesMathsStrands = (ccp: boolean): Strand[] => [
  { code: "1", name: "Number", subs: gesMathsSubsNumber() },
  { code: "2", name: "Algebra", subs: [{ code: ".1", name: "Patterns and Relationships" }] },
  { code: "3", name: "Geometry and Measurement", subs: [{ code: ".1", name: "Lines, Angles and Shapes" }, { code: ".2", name: "Measurement" }] },
  ccp ? { code: "4", name: "Data & Probability", subs: [{ code: ".1", name: "Data Collection, Organisation, Presentation & Interpretation" }] }
      : { code: "4", name: "Data", subs: [{ code: ".1", name: "Data Collection, Organisation, Presentation & Interpretation" }] },
];
const gesEnglishStrands: Strand[] = [
  { code: "1", name: "Oral Language (Listening and Speaking)" },
  { code: "2", name: "Reading" },
  { code: "3", name: "Grammar Usage" },
  { code: "4", name: "Writing" },
  { code: "5", name: "Literature" },
];
const gesScienceStrands: Strand[] = [
  { code: "1", name: "Diversity of Matter", subs: [{ code: ".1", name: "Living and non-living things" }, { code: ".2", name: "Materials" }] },
  { code: "2", name: "Cycles", subs: [{ code: ".2", name: "Life cycles of organisms" }] },
  { code: "3", name: "Systems", subs: [{ code: ".1", name: "The human body systems" }] },
  { code: "4", name: "Forces & Energy" },
  { code: "5", name: "Humans & the Environment" },
];
const gesComputingStrands: Strand[] = [
  { code: "1", name: "Introduction to Computing" },
  { code: "2", name: "Productivity Software" },
  { code: "3", name: "Communication Networks" },
  { code: "4", name: "Computational Thinking" },
];
const gesRmeStrands: Strand[] = [
  { code: "1", name: "God, His Creation and Attributes" },
  { code: "2", name: "Religious Practices" },
  { code: "3", name: "The Family and the Community" },
  { code: "4", name: "Authority and Obedience" },
];
const gesGhanaianLangStrands: Strand[] = [
  { code: "1", name: "Oral Language (Listening and Speaking)" },
  { code: "2", name: "Reading" },
  { code: "3", name: "Grammar" },
  { code: "4", name: "Writing" },
  { code: "5", name: "Literature" },
];

/* ── Cambridge (CAIE) Primary ──────────────────────────────────── */
const caieMathsStrands: Strand[] = [
  { name: "Number", subs: [
    { name: "Numbers and the number system" },
    { name: "Calculation — mental strategies" },
    { name: "Calculation — addition and subtraction" },
    { name: "Calculation — multiplication and division" },
    { name: "Fractions, decimals, percentages, ratio and proportion" },
  ] },
  { name: "Geometry" },
  { name: "Measure" },
  { name: "Handling data" },
  { name: "Problem solving" },
];
const caieEnglishStrands: Strand[] = [
  { name: "Phonics, spelling and vocabulary" },
  { name: "Grammar and punctuation" },
  { name: "Reading" },
  { name: "Writing" },
  { name: "Speaking and listening" },
];
const caieScienceStrands: Strand[] = [
  { name: "Biology" },
  { name: "Chemistry" },
  { name: "Physics" },
  { name: "Earth and Space" },
  { name: "Thinking and Working Scientifically" },
];

/* ── IB PYP Unit of Inquiry ────────────────────────────────────── */
const pypConcepts: Sub = [
  { name: "Form" }, { name: "Function" }, { name: "Causation" }, { name: "Change" },
  { name: "Connection" }, { name: "Perspective" }, { name: "Responsibility" },
];
const pypThemes: Strand[] = [
  { name: "Who we are", subs: structuredClone(pypConcepts).map((s) => ({ ...s, standards: [
    { statement: "An inquiry into the nature of the self; beliefs and values; personal, physical, mental, social and spiritual health; human relationships including families, friends, communities and cultures" },
  ] })) },
  { name: "Where we are in place and time" },
  { name: "How we express ourselves" },
  { name: "How the world works" },
  { name: "How we organize ourselves" },
  { name: "Sharing the planet" },
];

const FRAMEWORKS: FrameworkRow[] = [];
for (const g of ["B1", "B2", "B3", "B4", "B5", "B6"]) FRAMEWORKS.push({ curriculum: "GES", subjectName: "Mathematics", gradeLevel: g, strands: gesMathsStrands(false) });
for (const g of ["B7", "B8", "B9"]) FRAMEWORKS.push({ curriculum: "GES", subjectName: "Mathematics", gradeLevel: g, strands: gesMathsStrands(true) });
for (const g of ["B4", "B5", "B6", "B7", "B8", "B9"]) {
  FRAMEWORKS.push({ curriculum: "GES", subjectName: "English Language", gradeLevel: g, strands: gesEnglishStrands });
  FRAMEWORKS.push({ curriculum: "GES", subjectName: "Science", gradeLevel: g, strands: gesScienceStrands });
  FRAMEWORKS.push({ curriculum: "GES", subjectName: "Computing", gradeLevel: g, strands: gesComputingStrands });
  FRAMEWORKS.push({ curriculum: "GES", subjectName: "Religious and Moral Education", gradeLevel: g, strands: gesRmeStrands });
}
for (const g of ["B4", "B5", "B6"]) {
  FRAMEWORKS.push({ curriculum: "GES", subjectName: "Ghanaian Language", gradeLevel: g, strands: gesGhanaianLangStrands });
  FRAMEWORKS.push({ curriculum: "GES", subjectName: "Creative Arts and Design", gradeLevel: g, strands: [
    { code: "1", name: "Visual Arts" }, { code: "2", name: "Performing Arts" },
  ] });
}
for (const st of ["Stage 1", "Stage 2", "Stage 3", "Stage 4", "Stage 5", "Stage 6"]) {
  FRAMEWORKS.push({ curriculum: "CAIE", subjectName: "Mathematics", gradeLevel: st, strands: caieMathsStrands });
  FRAMEWORKS.push({ curriculum: "CAIE", subjectName: "English", gradeLevel: st, strands: caieEnglishStrands });
  FRAMEWORKS.push({ curriculum: "CAIE", subjectName: "Science", gradeLevel: st, strands: caieScienceStrands });
}
for (const st of ["PYP 1", "PYP 2", "PYP 3", "PYP 4", "PYP 5", "PYP 6"]) {
  FRAMEWORKS.push({ curriculum: "IB", subjectName: "Unit of Inquiry", gradeLevel: st, strands: structuredClone(pypThemes) });
}

async function main() {
  let strands = 0, subs = 0, standards = 0, indicators = 0;
  for (const fw of FRAMEWORKS) {
    for (let si = 0; si < fw.strands.length; si++) {
      const s = fw.strands[si];
      const strand = await db.curriculumStrand.upsert({
        where: { curriculum_subjectName_gradeLevel_name: { curriculum: fw.curriculum, subjectName: fw.subjectName, gradeLevel: fw.gradeLevel, name: s.name } },
        create: { curriculum: fw.curriculum, subjectName: fw.subjectName, gradeLevel: fw.gradeLevel, name: s.name, code: s.code ?? null, sortOrder: si },
        update: { code: s.code ?? null, sortOrder: si },
      });
      strands++;
      for (let bi = 0; bi < (s.subs ?? []).length; bi++) {
        const sub = s.subs![bi];
        const subRow = await db.curriculumSubStrand.upsert({
          where: { strandId_name: { strandId: strand.id, name: sub.name } },
          create: { strandId: strand.id, name: sub.name, code: sub.code ?? null, sortOrder: bi },
          update: { code: sub.code ?? null, sortOrder: bi },
        });
        subs++;
        for (const std of sub.standards ?? []) {
          const stdRow = await db.curriculumContentStandard.upsert({
            where: { subStrandId_statement: { subStrandId: subRow.id, statement: std.statement } },
            create: { subStrandId: subRow.id, statement: std.statement, code: std.code ? `${fw.gradeLevel}.${s.code}${std.code}`.replace(/\.\./g, ".") : null },
            update: { code: std.code ? `${fw.gradeLevel}.${s.code}${std.code}`.replace(/\.\./g, ".") : null },
          });
          standards++;
          for (let ii = 0; ii < (std.indicators ?? []).length; ii++) {
            const ind = std.indicators![ii];
            await db.curriculumIndicator.upsert({
              where: { standardId_statement: { standardId: stdRow.id, statement: ind.statement } },
              create: { standardId: stdRow.id, statement: ind.statement, code: ind.code ? `${stdRow.code ?? ""}${ind.code}` : null, sortOrder: ii },
              update: { code: ind.code ? `${stdRow.code ?? ""}${ind.code}` : null, sortOrder: ii },
            });
            indicators++;
          }
        }
      }
    }
  }
  // International schools run several curricula in parallel (e.g. Datus
  // International, Tema International) — Sika Gold (CAIE) also offers the
  // national GES track; Star of the East (IB) teaches Ghanaian Language.
  await db.school.update({ where: { code: "SGI" }, data: { curricula: JSON.stringify(["CAIE", "GES"]) } });
  await db.school.update({ where: { code: "SES" }, data: { curricula: JSON.stringify(["IB", "GES"]) } });
  await db.school.update({ where: { code: "BFI" }, data: { curricula: JSON.stringify(["CAIE"]) } });
  await db.school.update({ where: { code: "GEA" }, data: { curricula: JSON.stringify(["GES"]) } });
  console.log(`✅ framework library: ${strands} strands · ${subs} sub-strands · ${standards} content standards · ${indicators} indicators across ${FRAMEWORKS.length} subject×grade frameworks`);
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

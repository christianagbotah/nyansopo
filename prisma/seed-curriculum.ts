/* Curriculum seed — GES scheme-of-learning topics, lesson-note templates,
   lesson notes in every workflow state, and study material (iEduPro
   lesson_notes / academic_syllabus / study_material parity demo data).
   Idempotent — safe to re-run. Run: bun prisma/seed-curriculum.ts */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

const DAY = 864e5;
/** Monday of week N of a term starting on startsAt (week 1 = the first week). */
function weekDate(termStart: Date, week: number, weekday = 1): Date {
  const d = new Date(termStart);
  d.setDate(d.getDate() + (week - 1) * 7 + ((weekday - d.getDay() + 7) % 7));
  return d;
}

async function main() {
  const schools = await db.school.findMany({ select: { id: true, code: true } });
  const gea = schools.find((s) => s.code === "GEA");
  if (!gea) { console.log("no GEA school — skip"); return; }
  const schoolId = gea.id;

  const existing = await db.lessonNote.count({ where: { schoolId } });
  if (existing > 0) { console.log(`⏭️  ${existing} lesson notes already seeded`); return; }

  const [b4, jhs3] = await Promise.all([
    db.classroom.findFirst({ where: { schoolId, name: { contains: "Basic 4" } } }),
    db.classroom.findFirst({ where: { schoolId, name: { contains: "JHS 3" } } }),
  ]);
  const subs = await db.subject.findMany({ where: { schoolId } });
  const sub = (name: string) => subs.find((s) => s.name === name)?.id ?? subs[0].id;
  const MATHS = sub("Mathematics"), ENGL = sub("English Language"), SCI = sub("Science"), ISCI = sub("Integrated Science");
  const term = await db.term.findFirst({ where: { academicYear: { schoolId }, isCurrent: true } });
  if (!term || !b4 || !jhs3) { console.log("missing classroom/term — run the core seed first"); return; }
  const termId = term.id;
  const T0 = term.startsAt;
  const headName = "Madam Grace Mensah"; // GEA head (SCHOOL_ADMIN reviewer)

  const [isaac, abena, kwame] = await Promise.all([
    db.staff.findFirst({ where: { schoolId, fullName: "Mr. Isaac Boateng" } }),
    db.staff.findFirst({ where: { schoolId, fullName: "Madam Abena Osei" } }),
    db.staff.findFirst({ where: { schoolId, fullName: "Mr. Kwame Duah" } }),
  ]);

  // ── 1. lesson-note templates (GES skeletons) ──────────────────
  console.log("📘 Templates…");
  const tpl = await Promise.all([
    db.lessonNoteTemplate.create({ data: { schoolId, subjectId: MATHS, classroomId: b4.id, name: "B4 Mathematics — Number & numerals", strand: "Number", subStrand: "Counting and Numeration", contentStandard: "B4.1.1.1 Demonstrate understanding of numbers up to 100,000", description: "Weekly number-work skeleton: reading/writing numerals, place value, rounding.", objectives: "By the end of the lesson learners can:\n1. Read and write numerals up to 100,000\n2. Identify place value of digits\n3. Round numbers to the nearest 10 and 100", activities: "Starter (5 min): skip-counting chant.\nMain (25 min): place-value chart work in pairs; teacher models, learners build numbers with number cards.\nPlenary (10 min): quick-fire rounding quiz — thumbs up/down.", content: "Place value: thousands, ten-thousands, hundred-thousands.\nRounding rule: look at the digit to the right of the place you are rounding to.\nKey vocab: numeral, place value, estimate, round." } }),
    db.lessonNoteTemplate.create({ data: { schoolId, subjectId: ENGL, classroomId: b4.id, name: "B4 English — Word work & comprehension", strand: "Reading", subStrand: "Word Attack Skills", contentStandard: "B4.1.2.1 Apply word-attack skills to read and understand new words", description: "Two-syllable word work, phonics recap and a short comprehension passage.", objectives: "By the end of the lesson learners can:\n1. Break two-syllable words into syllables\n2. Read a short passage fluently\n3. Answer literal comprehension questions", activities: "Starter: syllable clapping game.\nMain: chunking new words on the board; group reading of the passage; question hunt in pairs.\nPlenary: retell the passage in one sentence.", content: "Syllable: a beat in a word.\nWord attack: chunking, sounding out, using context clues.\nComprehension: who / what / where questions first." } }),
    db.lessonNoteTemplate.create({ data: { schoolId, subjectId: SCI, classroomId: b4.id, name: "B4 Science — Investigating living things", strand: "Life Science", subStrand: "Living and Non-living Things", contentStandard: "B4.1.1.1 Identify characteristics of living things", description: "Observation-led lesson on the characteristics of living things.", objectives: "By the end of the lesson learners can:\n1. List the characteristics of living things\n2. Group specimens as living / non-living\n3. Explain why a car is not alive", activities: "Starter: I-spy with classroom objects.\nMain: group specimens sorting activity; concept map on manila card.\nPlenary: exit ticket — two characteristics of living things.", content: "Living things: movement, respiration, growth, reproduction, excretion, nutrition (MRS GREN simplified).\nNon-living things lack these characteristics." } }),
    db.lessonNoteTemplate.create({ data: { schoolId, subjectId: MATHS, classroomId: jhs3.id, name: "JHS 3 Mathematics — Algebraic expressions", strand: "Algebra", subStrand: "Algebraic Expressions", contentStandard: "B9.1.2.1 Simplify and factorise algebraic expressions", description: "BECE-focus algebra skeleton: simplifying, substituting, factorising.", objectives: "By the end of the lesson learners can:\n1. Simplify expressions by collecting like terms\n2. Substitute values into expressions\n3. Factorise simple expressions", activities: "Starter: mental substitution drill.\nMain: worked examples; pair practice from BECE past questions.\nPlenary: error-analysis — spot the mistake in a worked solution.", content: "Like terms share the same variables and powers.\nSubstitution: replace letters with values, keep BODMAS.\nFactorisation: take out the highest common factor." } }),
    db.lessonNoteTemplate.create({ data: { schoolId, subjectId: ISCI, classroomId: jhs3.id, name: "JHS 3 Integrated Science — Ecosystems", strand: "Ecology", subStrand: "Ecosystems", contentStandard: "B9.1.4.1 Explain the components of an ecosystem", description: "Ecosystem components, food chains and human impact.", objectives: "By the end of the lesson learners can:\n1. Define ecosystem, habitat and population\n2. Draw a food chain with correct arrows\n3. Suggest one human activity that disturbs an ecosystem", activities: "Starter: name that habitat picture sort.\nMain: field walk sketch; food-chain construction in groups.\nPlenary: discussion — Galamsey and the river ecosystem.", content: "Ecosystem = community of organisms + their environment.\nFood chain: producer → primary consumer → secondary consumer.\nHuman impact: deforestation, mining, pollution." } }),
    db.lessonNoteTemplate.create({ data: { schoolId, name: "Blank GES lesson-note skeleton", description: "Generic five-part GES lesson plan — any class, any subject.", objectives: "By the end of the lesson learners can:\n1. \n2. \n3. ", activities: "Starter / introduction (5 min):\nMain activity (25 min):\nPlenary / consolidation (10 min):\nAssessment:", content: "Strand / sub-strand:\nContent standard:\nLearning indicator:\nCore points:\nTeaching & learning resources:" } }),
  ]);

  // ── 2. scheme of learning (syllabus topics) ───────────────────
  console.log("🗺️  Scheme of learning…");
  type TopicSeed = [subjectId: string, classroomId: string, week: number, strand: string, subStrand: string, topic: string, indicator: string, done: boolean];
  const topics: TopicSeed[] = [
    // B4 Mathematics — Number
    [MATHS, b4.id, 1, "Number", "Counting and Numeration", "Reading and writing numerals up to 10,000", "B4.1.1.1.1", true],
    [MATHS, b4.id, 2, "Number", "Counting and Numeration", "Place value of digits in 5-digit numbers", "B4.1.1.1.2", true],
    [MATHS, b4.id, 3, "Number", "Counting and Numeration", "Rounding numbers to the nearest 10 and 100", "B4.1.1.1.3", true],
    [MATHS, b4.id, 4, "Number", "Fractions", "Equivalent fractions using concrete models", "B4.1.2.1.1", true],
    [MATHS, b4.id, 5, "Number", "Fractions", "Comparing and ordering like fractions", "B4.1.2.1.2", true],
    [MATHS, b4.id, 6, "Number", "Fractions", "Adding fractions with the same denominator", "B4.1.2.1.3", true],
    [MATHS, b4.id, 7, "Number", "Fractions", "Subtracting fractions with the same denominator", "B4.1.2.1.4", true],
    [MATHS, b4.id, 8, "Measurement", "Mass", "Measuring mass in grams and kilograms", "B4.1.3.1.1", false],
    [MATHS, b4.id, 9, "Measurement", "Mass", "Adding and subtracting masses", "B4.1.3.1.2", false],
    [MATHS, b4.id, 10, "Geometry", "Shape", "Properties of 2-D shapes: squares and rectangles", "B4.1.4.1.1", false],
    [MATHS, b4.id, 11, "Geometry", "Shape", "Perimeter of squares and rectangles", "B4.1.4.1.2", false],
    [MATHS, b4.id, 12, "Number", "Number Operations", "Multiplication facts: 6 and 7 times tables", "B4.1.1.2.1", false],
    // B4 English — Reading & Writing
    [ENGL, b4.id, 1, "Reading", "Word Attack Skills", "Two-syllable words: chunking and blending", "B4.1.2.1.1", true],
    [ENGL, b4.id, 2, "Reading", "Comprehension", "Literal questions on a short passage", "B4.1.2.2.1", true],
    [ENGL, b4.id, 3, "Reading", "Comprehension", "Inferential questions: reading between the lines", "B4.1.2.2.2", true],
    [ENGL, b4.id, 4, "Writing", "Composition", "Composing simple sentences with correct punctuation", "B4.1.3.1.1", true],
    [ENGL, b4.id, 5, "Writing", "Composition", "Writing a friendly letter", "B4.1.3.1.2", true],
    [ENGL, b4.id, 6, "Speaking & Listening", "Oral Language", "Retelling a story heard in class", "B4.1.1.1.1", true],
    [ENGL, b4.id, 7, "Writing", "Composition", "Descriptive writing: my market day", "B4.1.3.1.3", false],
    [ENGL, b4.id, 8, "Reading", "Comprehension", "Using context clues for new words", "B4.1.2.1.2", false],
    [ENGL, b4.id, 9, "Grammar", "Usage", "Regular plural nouns", "B4.1.4.1.1", false],
    [ENGL, b4.id, 10, "Grammar", "Usage", "Subject–verb agreement in the present tense", "B4.1.4.1.2", false],
    // B4 Science
    [SCI, b4.id, 1, "Life Science", "Living & Non-living", "Characteristics of living things", "B4.1.1.1.1", true],
    [SCI, b4.id, 2, "Life Science", "Living & Non-living", "Grouping things as living and non-living", "B4.1.1.1.2", true],
    [SCI, b4.id, 3, "Life Science", "Plants", "Parts of a plant and their functions", "B4.1.2.1.1", true],
    [SCI, b4.id, 4, "Life Science", "Plants", "What plants need to grow", "B4.1.2.1.2", true],
    [SCI, b4.id, 5, "Matter", "States of Matter", "Solids, liquids and gases around us", "B4.1.3.1.1", true],
    [SCI, b4.id, 6, "Matter", "States of Matter", "Changing states: melting and freezing", "B4.1.3.1.2", false],
    [SCI, b4.id, 7, "Matter", "States of Matter", "Evaporation and condensation in daily life", "B4.1.3.1.3", false],
    [SCI, b4.id, 8, "Life Science", "Animals", "Habitats of common animals", "B4.1.4.1.1", false],
    // JHS 3 Mathematics
    [MATHS, jhs3.id, 1, "Number & Numeration", "Number Operations", "Order of operations (BODMAS) revision", "B9.1.1.1.1", true],
    [MATHS, jhs3.id, 2, "Number & Numeration", "Fractions & Decimals", "Converting between fractions and decimals", "B9.1.1.2.1", true],
    [MATHS, jhs3.id, 3, "Number & Numeration", "Fractions & Decimals", "Recurring decimals", "B9.1.1.2.2", true],
    [MATHS, jhs3.id, 4, "Algebra", "Algebraic Expressions", "Simplifying expressions — collecting like terms", "B9.1.2.1.1", true],
    [MATHS, jhs3.id, 5, "Algebra", "Algebraic Expressions", "Substitution into expressions", "B9.1.2.1.2", true],
    [MATHS, jhs3.id, 6, "Algebra", "Algebraic Expressions", "Factorising simple expressions", "B9.1.2.1.3", true],
    [MATHS, jhs3.id, 7, "Algebra", "Linear Equations", "Solving linear equations in one unknown", "B9.1.2.2.1", true],
    [MATHS, jhs3.id, 8, "Algebra", "Linear Equations", "Word problems leading to linear equations", "B9.1.2.2.2", true],
    [MATHS, jhs3.id, 9, "Algebra", "Linear Inequalities", "Solving linear inequalities", "B9.1.2.3.1", false],
    [MATHS, jhs3.id, 10, "Geometry", "Angles", "Angles on a straight line and at a point", "B9.1.3.1.1", false],
    [MATHS, jhs3.id, 11, "Geometry", "Angles", "Properties of parallel lines and transversals", "B9.1.3.1.2", false],
    [MATHS, jhs3.id, 12, "Statistics", "Data Handling", "Mean, median and mode of ungrouped data", "B9.1.4.1.1", false],
    // JHS 3 Integrated Science
    [ISCI, jhs3.id, 1, "Ecology", "Ecosystems", "Components of an ecosystem", "B9.1.4.1.1", true],
    [ISCI, jhs3.id, 2, "Ecology", "Ecosystems", "Food chains and food webs", "B9.1.4.1.2", true],
    [ISCI, jhs3.id, 3, "Ecology", "Ecosystems", "Human activities that disturb ecosystems", "B9.1.4.1.3", true],
    [ISCI, jhs3.id, 4, "Physics", "Energy", "Forms of energy and energy transformations", "B9.1.5.1.1", true],
    [ISCI, jhs3.id, 5, "Physics", "Energy", "Renewable and non-renewable energy sources", "B9.1.5.1.2", true],
    [ISCI, jhs3.id, 6, "Chemistry", "Water", "The water cycle", "B9.1.6.1.1", true],
    [ISCI, jhs3.id, 7, "Chemistry", "Water", "Water pollution and purification", "B9.1.6.1.2", false],
    [ISCI, jhs3.id, 8, "Biology", "Respiration", "Aerobic and anaerobic respiration", "B9.1.7.1.1", false],
    [ISCI, jhs3.id, 9, "Biology", "Respiration", "Gaseous exchange in humans", "B9.1.7.1.2", false],
    [ISCI, jhs3.id, 10, "Physics", "Light", "Reflection of light and plane mirrors", "B9.1.8.1.1", false],
  ];
  await db.syllabusTopic.createMany({
    data: topics.map(([subjectId, classroomId, week, strand, subStrand, topic, indicator, done]) => ({
      schoolId, classroomId, subjectId, termId, weekNumber: week, strand, subStrand, topic, indicator,
      completed: done, completedAt: done ? weekDate(T0, week, 5) : null,
    })),
  });

  // ── 3. lesson notes (full workflow demo) ──────────────────────
  console.log("📝 Lesson notes…");
  async function note(opts: {
    staffId: string; classroomId: string; subjectId: string; week: number; title: string;
    status: string; objectives: string; activities: string; content: string; description: string;
    templateId?: string; feedback?: string; version?: number;
  }) {
    const n = await db.lessonNote.create({
      data: {
        schoolId, staffId: opts.staffId, classroomId: opts.classroomId, subjectId: opts.subjectId, termId,
        templateId: opts.templateId ?? null, title: opts.title, description: opts.description,
        weekNumber: opts.week, lessonDate: weekDate(T0, opts.week, opts.week % 2 ? 1 : 3),
        objectives: opts.objectives, activities: opts.activities, content: opts.content,
        status: opts.status, feedback: opts.feedback ?? null, version: opts.version ?? 1,
        submittedAt: opts.status === "DRAFT" ? null : weekDate(T0, opts.week, 5),
        reviewedAt: opts.status === "ENDORSED" || opts.status === "REVISION_REQUIRED" ? weekDate(T0, opts.week, 6) : null,
        reviewedByName: opts.status === "ENDORSED" || opts.status === "REVISION_REQUIRED" ? headName : null,
      },
    });
    const log: { action: string; actorName: string; comment?: string }[] = [{ action: "SUBMIT", actorName: n.staffId === isaac?.id ? "Mr. Isaac Boateng" : n.staffId === kwame?.id ? "Mr. Kwame Duah" : "Madam Abena Osei" }];
    if (opts.status === "ENDORSED") log.push({ action: "ENDORSE", actorName: headName, comment: "Well-structured lesson — approved." });
    if (opts.status === "REVISION_REQUIRED") log.push({ action: "REQUEST_REVISION", actorName: headName, comment: opts.feedback });
    if ((opts.version ?? 1) > 1) log.splice(1, 0, { action: "RESUBMIT", actorName: "Mr. Isaac Boateng", comment: "Revised after reviewer feedback" });
    for (const [i, l] of log.entries()) {
      await db.lessonNoteReview.create({
        data: { noteId: n.id, action: l.action, actorName: l.actorName, comment: l.comment ?? null, createdAt: new Date(weekDate(T0, opts.week, 5).getTime() + i * 36e5) },
      });
    }
    return n;
  }

  if (isaac) {
    // B4 Mathematics — endorsed weeks 1–5 (the healthy weekly rhythm)
    for (const w of [1, 2, 3, 4, 5]) {
      await note({
        staffId: isaac.id, classroomId: b4.id, subjectId: MATHS, week: w, status: "ENDORSED", templateId: tpl[0].id,
        title: ["Numerals up to 10,000", "Place value in 5-digit numbers", "Rounding to 10 and 100", "Equivalent fractions", "Ordering like fractions"][w - 1],
        description: `Week ${w} number work — Basic 4 Gold Mathematics`,
        objectives: "By the end of the lesson learners can:\n1. Work confidently with the week's number focus\n2. Explain their method to a partner\n3. Solve BECE-style level questions for their stage",
        activities: "Starter (5 min): mental maths chain.\nMain (25 min): concrete → pictorial → abstract progression; pair practice on slates.\nPlenary (10 min): exit ticket, two questions.",
        content: "Key vocab displayed on the word wall. Differentiation: support group works with place-value charts; extension group tackles challenge cards.",
      });
    }
    // week 6 returned for revision (resubmitted once, now pending again)
    const w6 = await note({
      staffId: isaac.id, classroomId: b4.id, subjectId: MATHS, week: 6, status: "PENDING", version: 2, templateId: tpl[0].id,
      title: "Adding fractions with the same denominator",
      description: "Week 6 number work — Basic 4 Gold Mathematics",
      objectives: "By the end of the lesson learners can:\n1. Add fractions with the same denominator\n2. Represent the sum with a fraction model\n3. Simplify the answer where possible",
      activities: "Starter: fraction wall recap.\nMain: paper-folding addition; guided examples; pair practice.\nPlenary: true/false fraction sums.",
      content: "Add numerators, keep the denominator. Simplify using equivalence. Common error: adding denominators — addressed with the fraction wall.",
    });
    await db.lessonNote.update({ where: { id: w6.id }, data: { feedback: "Please add a differentiated activity for the lower-ability group and show the fraction wall resource in the activities section." } });
    // week 8 (current week) submitted and awaiting review
    await note({
      staffId: isaac.id, classroomId: b4.id, subjectId: MATHS, week: 8, status: "PENDING",
      title: "Measuring mass in grams and kilograms",
      description: "Week 8 measurement — Basic 4 Gold Mathematics",
      objectives: "By the end of the lesson learners can:\n1. Read a kitchen scale in grams\n2. Estimate and measure mass in kilograms\n3. Convert between grams and kilograms",
      activities: "Starter: heaviest-object guessing game.\nMain: station rotation — rice, stones, water bottle; record and compare.\nPlenary: match the mass to the object.",
      content: "1 kg = 1000 g. Estimation strategies: compare to a known 1 kg reference. Reading scales between labelled marks.",
    });
    // science: one endorsed, one draft
    await note({
      staffId: isaac.id, classroomId: b4.id, subjectId: SCI, week: 4, status: "ENDORSED", templateId: tpl[2].id,
      title: "Parts of a plant and their functions",
      description: "Week 4 life science — Basic 4 Gold Science",
      objectives: "By the end of the lesson learners can:\n1. Name the parts of a flowering plant\n2. State one function of each part\n3. Label a plant diagram correctly",
      activities: "Starter: plant parts song.\nMain: outdoor observation of the school garden; labelling worksheet in pairs.\nPlenary: function matching game.",
      content: "Roots absorb water and anchor the plant; stems carry water and support; leaves make food (photosynthesis); flowers make seeds.",
    });
    await note({
      staffId: isaac.id, classroomId: b4.id, subjectId: SCI, week: 9, status: "DRAFT",
      title: "Evaporation and condensation in daily life",
      description: "Next week's matter lesson — Basic 4 Gold Science (draft)",
      objectives: "By the end of the lesson learners can:\n1. Describe evaporation and condensation\n2. Give one everyday example of each\n3. Explain how the water cycle uses both",
      activities: "Starter: where did the puddle go?\nMain: kettle demonstration (teacher-led); mirror-breath investigation.\nPlenary: cycle diagram on the board.",
      content: "Evaporation: liquid → gas (heating). Condensation: gas → liquid (cooling).",
    });
  }

  if (kwame) {
    await note({
      staffId: kwame.id, classroomId: jhs3.id, subjectId: MATHS, week: 7, status: "ENDORSED", templateId: tpl[3].id,
      title: "Solving linear equations in one unknown",
      description: "Week 7 algebra — JHS 3 Mathematics",
      objectives: "By the end of the lesson learners can:\n1. Solve linear equations in one unknown\n2. Check solutions by substitution\n3. Solve BECE past questions on linear equations",
      activities: "Starter: balance-scale puzzles.\nMain: worked examples (3 levels); BECE past-question relay in teams.\nPlenary: error analysis of a wrong solution.",
      content: "Do the same to both sides. Inverse operations. Check by substitution — always.",
    });
    await note({
      staffId: kwame.id, classroomId: jhs3.id, subjectId: MATHS, week: 9, status: "PENDING",
      title: "Solving linear inequalities",
      description: "Week 9 algebra — JHS 3 Mathematics",
      objectives: "By the end of the lesson learners can:\n1. Solve linear inequalities in one unknown\n2. Represent solutions on a number line\n3. Handle sign reversal when multiplying by negatives",
      activities: "Starter: number-line warm-up.\nMain: guided discovery of the sign-reversal rule; graded practice.\nPlenary: true/false inequality statements.",
      content: "Same rules as equations, except multiplying/dividing by a negative reverses the inequality sign.",
    });
  }

  if (abena) {
    await note({
      staffId: abena.id, classroomId: b4.id, subjectId: ENGL, week: 7, status: "REVISION_REQUIRED", templateId: tpl[1].id,
      title: "Descriptive writing: my market day",
      description: "Week 7 writing — Basic 4 Gold English",
      objectives: "By the end of the lesson learners can:\n1. Use the five senses to describe a scene\n2. Write a descriptive paragraph\n3. Use at least three adjectives correctly",
      activities: "Starter: sense walk in the compound.\nMain: shared writing of the opening; independent paragraphs.\nPlenary: author's chair — two readers.",
      content: "Descriptive writing uses the senses: sight, sound, smell, touch, taste. Adjectives make descriptions vivid.",
      feedback: "Objectives are fine but the plenary needs a success criterion — how will learners know their paragraph is descriptive enough? Please add a simple checklist.",
    });
  }

  // ── 4. study material ─────────────────────────────────────────
  console.log("📚 Study material…");
  await db.studyMaterial.createMany({
    data: [
      { schoolId, classroomId: b4.id, subjectId: MATHS, authorStaffId: isaac!.id, title: "Basic 4 Maths — fractions revision set", description: "20 practice questions on equivalent fractions and adding like fractions, with answers.", linkUrl: "https://drive.google.com/file/d/DEMO-fractions-revision/view", body: "Also try the fraction-wall matching game we played in class — draw the wall, shade 1/2, 2/4 and 3/6. What do you notice?" },
      { schoolId, classroomId: b4.id, subjectId: ENGL, authorStaffId: abena!.id, title: "English past questions — reading comprehension", description: "Three short passages with literal and inferential questions.", body: "Passage 1: Ama's first market day.\nAnswer questions 1–5 in full sentences.\nPassage 2: The clever tortoise (folk tale).\nPassage 3: Water in our village (informational)." },
      { schoolId, classroomId: jhs3.id, subjectId: MATHS, authorStaffId: kwame!.id, title: "BECE algebra — 10 most common question types", description: "Worked examples of the recurring BECE algebra patterns, 2015–2024.", linkUrl: "https://drive.google.com/file/d/DEMO-bece-algebra/view", body: null },
      { schoolId, classroomId: jhs3.id, subjectId: ISCI, authorStaffId: kwame!.id, title: "Science practical guide: testing for starch and sugar", description: "Step-by-step practical guide for the food-test practical.", body: "You will need: iodine solution, Benedict's solution, test tubes, Bunsen burner (teacher only).\nSafety: goggles on before any heating. Report all spills immediately." },
    ],
  });

  const counts = {
    templates: await db.lessonNoteTemplate.count({ where: { schoolId } }),
    notes: await db.lessonNote.count({ where: { schoolId } }),
    topics: await db.syllabusTopic.count({ where: { schoolId } }),
    materials: await db.studyMaterial.count({ where: { schoolId } }),
  };
  console.log("🎉 Curriculum seed done:", JSON.stringify(counts));
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

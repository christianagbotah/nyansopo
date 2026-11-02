/* Crèche / early-years seed — GES NaCCA developmental-domain demo data
   for Golden Excellence Academy: the proficiency rating scale (EX/GD/SD/NA),
   the five developmental domains with observable skills, and term ratings
   for a handful of learners in the KG class.
   Idempotent — safe to re-run. Run:
   DATABASE_URL=postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public bun prisma/seed-creche.ts */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

// deterministic PRNG (seed.ts style)
let _s = 20261107;
function rnd() {
  _s |= 0; _s = (_s + 0x6d2b79f5) | 0;
  let t = Math.imul(_s ^ (_s >>> 15), 1 | _s);
  t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
  return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
}

const SCALE = [
  { code: "EX", label: "Excellent", sortOrder: 1 },
  { code: "GD", label: "Good", sortOrder: 2 },
  { code: "SD", label: "Developing", sortOrder: 3 },
  { code: "NA", label: "Needs Attention", sortOrder: 4 },
];
const DOMAINS: { name: string; skills: string[] }[] = [
  { name: "Communication & Language", skills: ["Listens and responds to simple instructions", "Speaks in understandable sentences", "Uses new words in conversation", "Shows interest in story books", "Answers questions about a story"] },
  { name: "Physical & Motor Development", skills: ["Runs, jumps and climbs with control", "Holds crayon / pencil correctly", "Threads beads and buttons clothing", "Balances on one foot", "Catches and throws a ball"] },
  { name: "Social & Emotional", skills: ["Plays and shares with others", "Follows classroom routines", "Expresses feelings with words", "Shows care for others", "Separates happily from caregiver"] },
  { name: "Cognitive / Discovery", skills: ["Counts objects up to 20", "Sorts and matches by colour / shape", "Recognises own name in print", "Asks questions about the environment", "Completes simple puzzles"] },
  { name: "Creative Arts", skills: ["Sings familiar songs and rhymes", "Draws and colours with purpose", "Moves creatively to music", "Role-plays everyday activities"] },
];

async function main() {
  const schools = await db.school.findMany({ select: { id: true, code: true } });
  const gea = schools.find((s) => s.code === "GEA");
  if (!gea) { console.log("no GEA school — run the core seed first"); return; }
  const schoolId = gea.id;

  // 1. rating scale — upsert each code (idempotent)
  console.log("👶 Rating scale…");
  for (const s of SCALE) {
    await db.crecheRatingScale.upsert({
      where: { schoolId_code: { schoolId, code: s.code } },
      create: { schoolId, ...s }, update: { label: s.label, sortOrder: s.sortOrder },
    });
  }

  // 2. domains & skills — create only when none exist
  console.log("🧩 Developmental domains…");
  const existingDomains = await db.crecheDomain.count({ where: { schoolId } });
  if (existingDomains === 0) {
    for (const [di, d] of DOMAINS.entries()) {
      const dom = await db.crecheDomain.create({ data: { schoolId, name: d.name, sortOrder: di + 1, active: true } });
      await db.crecheSkill.createMany({ data: d.skills.map((name, si) => ({ schoolId, domainId: dom.id, name, sortOrder: si + 1 })) });
    }
  } else {
    console.log(`⏭️  ${existingDomains} domains already configured`);
  }

  // 3. ratings for the KG class, current term
  console.log("⭐ Term ratings…");
  const term = await db.term.findFirst({ where: { academicYear: { schoolId }, isCurrent: true } });
  if (!term) { console.log("no current term — run the core seed first"); return; }
  // prefer KG 2, fall back to any KG/creche-level class, else the first class
  const classrooms = await db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" } });
  const kg = classrooms.find((c) => c.gradeLevel === "KG2") ?? classrooms.find((c) => /^(KG|CR|NU)/i.test(c.gradeLevel)) ?? classrooms[0];
  if (!kg) { console.log("no classrooms — run the core seed first"); return; }
  const skills = await db.crecheSkill.findMany({ where: { schoolId }, orderBy: [{ sortOrder: "asc" }] });
  const enrolled = await db.enrollment.findMany({ where: { classroomId: kg.id, status: "ACTIVE" }, include: { student: true }, orderBy: { rollNo: "asc" }, take: 5 });

  const already = await db.crecheRating.count({ where: { schoolId, termId: term.id } });
  if (already > 0) {
    console.log(`⏭️  ${already} ratings already recorded for the current term`);
  } else if (!enrolled.length || !skills.length) {
    console.log("no enrolled learners or skills found — skipping ratings");
  } else {
    // per-learner bias so the demo class shows a realistic mix of EX/GD with some SD/NA
    const biases = [0.72, 0.55, 0.62, 0.38, 0.48];
    let created = 0;
    for (const [li, en] of enrolled.entries()) {
      const bias = biases[li % biases.length];
      for (const sk of skills) {
        const r = rnd();
        const code = r < bias ? "EX" : r < bias + (1 - bias) * 0.55 ? "GD" : r < bias + (1 - bias) * 0.85 ? "SD" : "NA";
        await db.crecheRating.create({
          data: { schoolId, studentId: en.studentId, termId: term.id, skillId: sk.id, code, byName: "Madam Akosua Frimpong (KG)" },
        });
        created++;
      }
    }
    console.log(`   ${created} ratings for ${enrolled.length} learners in ${kg.name} · ${term.name}`);
  }

  const counts = {
    scale: await db.crecheRatingScale.count({ where: { schoolId } }),
    domains: await db.crecheDomain.count({ where: { schoolId } }),
    skills: await db.crecheSkill.count({ where: { schoolId } }),
    ratings: await db.crecheRating.count({ where: { schoolId } }),
  };
  console.log("🎉 Crèche seed done:", JSON.stringify(counts));
}

export { main as seedCreche };

if (process.argv[1]?.includes("seed-creche")) {
  main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());
}

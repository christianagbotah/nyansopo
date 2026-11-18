/* Wave 35 — legacy marksheet library: conduct items, interest items and
   remark templates for every school (CI3 student_marksheet.php parity).

   Legacy doctrine (aggregation + Conduct_items / Interest_items /
   Teacher_remarks_templates / Head_teacher_remarks):
     • conduct — up to 15 database-driven checkboxes per learner per term
       (legacy aggregation.c1..c15 slots);
     • interest — one database-driven pick per learner;
     • teacher remarks — a template library the class teacher picks from
       (positive / neutral / negative tone) or free text;
     • head teacher remarks — percentage-band auto-remark the head can
       override, exactly like Head_teacher_remarks::find_by_percentage.

   Idempotent: wipes and rebuilds the school-wide catalogs so the
   marksheet sheet and the settings report-library tab share one source.
   Run: DATABASE_URL=... bun prisma/seed-marksheet.ts */
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

const CONDUCTS: [string, number][] = [
  ["Polite & Courteous", 1],
  ["Honest & Truthful", 2],
  ["Punctual & Regular", 3],
  ["Neat & Tidy", 4],
  ["Hardworking", 5],
  ["Respects Authority", 6],
  ["Obeys School Rules", 7],
  ["Shows Leadership", 8],
  ["Helpful to Others", 9],
  ["Team Player", 10],
  ["Self-Control", 11],
  ["Careful with Property", 12],
  ["Good Sportsmanship", 13],
  ["Culturally Aware", 14],
  ["Environmentally Conscious", 15],
];

const INTERESTS: [string, number][] = [
  ["Football", 1],
  ["Athletics", 2],
  ["Music & Dance", 3],
  ["Drama & Poetry", 4],
  ["Reading Club", 5],
  ["Science Club", 6],
  ["Debate & Quiz", 7],
  ["ICT / Coding", 8],
  ["Art & Drawing", 9],
  ["Guidance & Counselling", 10],
  ["Scouts / Cadet", 11],
];

const TEACHER_POSITIVE = [
  "A very hardworking learner. Keep it up!",
  "Excellent performance — a role model for the class.",
  "Consistently attentive and always completes assignments. Wonderful term.",
  "A delight to teach; brilliant participation in class.",
];
const TEACHER_NEUTRAL = [
  "A fair effort this term. More seriousness will lift the result.",
  "Capable of much more; steady practice at home is needed.",
  "Average participation; encourage regular revision.",
];
const TEACHER_NEGATIVE = [
  "Performance is below expectation. Needs close supervision at home.",
  "Very weak engagement this term; parent support is required.",
  "Frequent distraction in class; set firm study routines at home.",
];

const HEAD_BANDS: [number, number, string][] = [
  [90, 100, "An outstanding learner — a credit to this school."],
  [80, 89, "A very good result. Aim for the very top next term."],
  [70, 79, "A good result, but you are capable of even more."],
  [60, 69, "Satisfactory — work harder next term."],
  [50, 59, "Average performance; serious improvement is needed."],
  [40, 49, "A weak result. The learner must sit up next term."],
  [0, 39, "A very poor result. Parents must intervene urgently."],
];

async function main() {
  const schools = await db.school.findMany({ select: { id: true, name: true } });
  let conductCount = 0, interestCount = 0, templateCount = 0;
  for (const sc of schools) {
    await db.conductItem.deleteMany({ where: { schoolId: sc.id } });
    await db.remarkTemplate.deleteMany({ where: { schoolId: sc.id } });
    for (const [name, sortOrder] of CONDUCTS) {
      await db.conductItem.create({ data: { schoolId: sc.id, name, kind: "CONDUCT", sortOrder, active: true } });
      conductCount++;
    }
    for (const [name, sortOrder] of INTERESTS) {
      await db.conductItem.create({ data: { schoolId: sc.id, name, kind: "INTEREST", sortOrder, active: true } });
      interestCount++;
    }
    const mk = (category: string, text: string, minPercent: number | null, maxPercent: number | null) =>
      db.remarkTemplate.create({ data: { schoolId: sc.id, category, text, minPercent, maxPercent, active: true } });
    for (const t of TEACHER_POSITIVE) { await mk("TEACHER_POSITIVE", t, null, null); templateCount++; }
    for (const t of TEACHER_NEUTRAL) { await mk("TEACHER_NEUTRAL", t, null, null); templateCount++; }
    for (const t of TEACHER_NEGATIVE) { await mk("TEACHER_NEGATIVE", t, null, null); templateCount++; }
    for (const [min, max, text] of HEAD_BANDS) { await mk("HEAD_RANGE", text, min, max); templateCount++; }
  }
  const conductIds = await db.conductItem.count();
  const templateRows = await db.remarkTemplate.count();
  console.log(`✅ Marksheet library seed complete — ${conductIds} conduct/interest items (${CONDUCTS.length} conduct + ${INTERESTS.length} interest per school) + ${templateRows} remark templates (${TEACHER_POSITIVE.length + TEACHER_NEUTRAL.length + TEACHER_NEGATIVE.length} teacher + ${HEAD_BANDS.length} head bands per school) across ${schools.length} school(s)`);
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

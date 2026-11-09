// ── Bill-item catalog seed (CI3 bill_item parity — student_payment.php tab 1).
// Idempotent: upserts by (schoolId, title).
import { db } from "../src/lib/db";

async function main() {
  const schools = await db.school.findMany();
  let total = 0;
  for (const school of schools) {
    const classrooms = await db.classroom.findMany({ where: { schoolId: school.id }, orderBy: { name: "asc" } });
    const byGrade = (prefixes: RegExp[]) => classrooms.filter((c) => prefixes.some((p) => p.test(c.gradeLevel)));
    const items: { title: string; category: string; classCategory: string; specificClassIds: string[]; amountCents: number; description: string }[] = [
      { title: "TUITION FEE", category: "Tuition", classCategory: "ALL", specificClassIds: [], amountCents: 25000, description: "Termly tuition" },
      { title: "EXAMINATION FEE", category: "Exams", classCategory: "ALL", specificClassIds: [], amountCents: 3000, description: "End-of-term examinations" },
      { title: "BOOKS & LEARNING MATERIALS", category: "Books", classCategory: "ALL", specificClassIds: [], amountCents: 4500, description: "Textbooks and workbooks" },
      { title: "ICT & COMPUTING", category: "ICT", classCategory: "PRIMARY", specificClassIds: [], amountCents: 2500, description: "ICT lab usage" },
      { title: "CRECHE CARE & FEEDING", category: "Meals", classCategory: "CRECHE", specificClassIds: [], amountCents: 6000, description: "Day care with meals" },
      { title: "SPORTS & CULTURE", category: "Sports", classCategory: "ALL", specificClassIds: [], amountCents: 1500, description: "PE, sports day and cultural activities" },
      { title: "GRADUATION & CERTIFICATION", category: "Graduation", classCategory: "JHS", specificClassIds: [], amountCents: 8000, description: "Final-year graduation and certificates" },
    ];
    // one class-specific item so the specific-classes picker has a live example
    const jhs = byGrade([/^B[7-9]$/]);
    if (jhs.length) {
      items.push({ title: "BECE REGISTRATION", category: "Exams", classCategory: "JHS", specificClassIds: jhs.slice(-1).map((c) => c.id), amountCents: 12000, description: "WAEC BECE entry (final-year class only)" });
    }
    for (const [i, it] of items.entries()) {
      await db.billItem.upsert({
        where: { schoolId_title: { schoolId: school.id, title: it.title } },
        update: { category: it.category, classCategory: it.classCategory, specificClassIds: it.specificClassIds, amountCents: it.amountCents, description: it.description, sortOrder: i },
        create: { schoolId: school.id, ...it, sortOrder: i },
      });
      total++;
    }
  }
  console.log(`✅ Bill-item seed complete — ${total} catalog items across ${schools.length} school(s)`);
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

/* Wave 37 — benefit categories & beneficiary register (legacy benefit_category
   / beneficiary_list / benefit_categories.php parity).

   Seeds the welfare library every school shares — Capitation Grant (GES),
   School Feeding Programme, LEAP Support and a Needy-but-Brilliant
   scholarship — with per-class charge matrices ('a' = all classes) exactly
   like the legacy details JSON; then stamps ~12 Golden Excellence Academy
   learners onto categories for the current term.

   Idempotent: skips when rows already exist.
   Run: DATABASE_URL=... bun prisma/seed-beneficiaries.ts */
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

const CATEGORIES: { name: string; code: string; details: Record<string, unknown> }[] = [
  {
    name: "CAPITATION GRANT (GES)",
    code: "CAP-GES",
    details: {
      discountType: "amount",
      classes: { a: { feedingCents: 0, classesCents: 0, tuitionCents: 2500 } },
    },
  },
  {
    name: "SCHOOL FEEDING PROGRAMME",
    code: "SFP",
    details: {
      discountType: "amount",
      classes: { a: { feedingCents: 0, classesCents: 300, tuitionCents: 0 } },
    },
  },
  {
    name: "LEAP SUPPORT",
    code: "LEAP",
    details: {
      discountType: "amount",
      classes: { a: { feedingCents: 0, classesCents: 0, tuitionCents: 1500 } },
    },
  },
  {
    name: "SCHOLARSHIP — NEEDY BUT BRILLIANT",
    code: "NBS",
    details: {
      discountType: "percentage",
      classes: { a: { feedingCents: 5000, classesCents: 5000, tuitionCents: 5000 } },
    },
  },
];

async function main() {
  const schools = await db.school.findMany({ select: { id: true, name: true } });
  for (const sc of schools) {
    const existing = await db.benefitCategory.count({ where: { schoolId: sc.id } });
    if (!existing) {
      for (const c of CATEGORIES) {
        await db.benefitCategory.create({ data: { schoolId: sc.id, name: c.name, code: c.code, detailsJson: c.details, active: true, createdByName: "seed" } });
      }
    }
  }
  const catCount = await db.benefitCategory.count();

  /* beneficiaries: GEA current term, one per demo classroom — the register
     keeps frozen per-category amounts like the legacy beneficiary_list */
  const gea = await db.school.findFirst({ where: { name: { contains: "Golden Excellence" } } });
  let beneficiaries = 0;
  if (gea) {
    const year = await db.academicYear.findFirst({ where: { schoolId: gea.id, isCurrent: true } });
    const term = year ? await db.term.findFirst({ where: { academicYearId: year.id, isCurrent: true } }) : null;
    if (term) {
      const cats = await db.benefitCategory.findMany({ where: { schoolId: gea.id, active: true } });
      const existing = await db.beneficiary.count({ where: { schoolId: gea.id, termId: term.id } });
      if (!existing) {
        const enrollments = await db.enrollment.findMany({
          where: { status: "ACTIVE", classroom: { schoolId: gea.id } },
          include: { student: { select: { id: true, firstName: true, lastName: true } }, classroom: { select: { name: true, gradeLevel: true } } },
          take: 400,
        });
        /* spread across classes: every 9th enrollment becomes a beneficiary */
        const picks: typeof enrollments = [];
        for (let i = 2; i < enrollments.length; i += 9) picks.push(enrollments[i]);
        for (const [i, e] of picks.entries()) {
          const cat = cats[i % cats.length];
          const d = (cat.detailsJson as { classes?: Record<string, { feedingCents: number; classesCents: number; tuitionCents: number }> } | null)?.classes?.a;
          await db.beneficiary.create({
            data: {
              schoolId: gea.id, studentId: e.studentId, termId: term.id,
              classroomName: e.classroom.name,
              categoriesJson: [{ categoryId: cat.id, name: cat.name, feedingCents: d?.feedingCents ?? 0, classesCents: d?.classesCents ?? 0, tuitionCents: d?.tuitionCents ?? 0 }],
              note: i % 3 === 0 ? "Verified against the district welfare register." : null,
              createdByName: "seed",
            },
          });
          beneficiaries++;
        }
      }
    }
  }

  console.log(`✅ Benefit seed complete — ${catCount} categories (${CATEGORIES.length} per school: Capitation Grant, School Feeding, LEAP, Scholarship) across ${schools.length} school(s) + ${beneficiaries} Golden Excellence Academy beneficiaries stamped on the current term`);
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

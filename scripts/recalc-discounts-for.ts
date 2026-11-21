/* Re-materialise a learner's discounted daily rates (runs recalcDailySubsidies).
   Usage: bun scripts/recalc-discounts-for.ts "<student first or last name>" [...] */
import { PrismaClient } from "@prisma/client";
import { recalcDailySubsidies } from "../src/lib/discounts";

const db = new PrismaClient();

async function main() {
  const terms = process.argv.slice(2).filter(Boolean);
  const students = terms.length
    ? await db.student.findMany({
        where: { OR: terms.flatMap((t) => [{ firstName: { contains: t } }, { lastName: { contains: t } }]) },
        include: { enrollments: { where: { status: "ACTIVE" }, take: 1 } },
        take: 20,
      })
    : await db.student.findMany({ where: { enrollments: { some: { status: "ACTIVE" } } }, take: 200 });
  if (!students.length) { console.log("no matching learners"); return; }
  for (const s of students) {
    const r = await recalcDailySubsidies(s.id, { performedByName: "recalc script", performedByRole: "system", reason: "class-rate base fix — re-materialisation" });
    const changed = r.services.filter((x) => x.from !== x.to);
    if (changed.length) console.log(`${s.firstName} ${s.lastName}: ${changed.map((c) => `${c.name} ${c.from ?? "list"}→${c.to ?? "list"}`).join(" · ")}`);
    else console.log(`${s.firstName} ${s.lastName}: unchanged`);
  }
  await db.$disconnect();
}
main();

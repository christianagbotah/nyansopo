/* Fees parity smoke — clone-previous-term structures + bulk arrears CSV import.
   Creates its own data and CLEANS UP afterwards.
   Run: DATABASE_URL=postgresql://… bun scripts/smoke-fees-parity.ts
   (needs `bun run dev` on :3000) */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();
const BASE = "http://localhost:3000";
let pass = 0, fail = 0;
const check = (label: string, cond: boolean, extra = "") => {
  if (cond) { pass++; console.log(`  ✅ ${label}`); }
  else { fail++; console.log(`  ❌ ${label} ${extra}`); }
};

async function main() {
  const user = await db.user.findUnique({ where: { email: "head@nyansapo.gh" } });
  const school = await db.school.findUnique({ where: { code: "GEA" } });
  if (!user || !school) { console.log("missing demo data — run the seed first"); return; }
  const H = { "content-type": "application/json", "x-user-id": user.id };
  const schoolId = school.id;
  const post = (body: any) => fetch(`${BASE}/api/school/${schoolId}/fees`, { method: "POST", headers: H, body: JSON.stringify(body) });

  const currentTerm = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
  const oldTerm = await db.term.findFirst({ where: { isCurrent: false, academicYear: { schoolId } }, orderBy: { startsAt: "desc" } });
  if (!currentTerm || !oldTerm) { console.log("need a current + a past term — aborting"); return; }

  console.log("── (a) clone previous term's structures ──");
  // a fresh classroom guarantees a cloneable gap regardless of demo state
  const classroom = await db.classroom.create({
    data: { schoolId, name: "SMOKE Clone Room", gradeLevel: "B9", capacity: 10 },
  });
  const temp = await db.feeStructure.create({
    data: { schoolId, termId: oldTerm.id, classroomId: classroom.id, totalCents: 123456 },
  });
  await db.feeItem.create({ data: { feeStructureId: temp.id, name: "SMOKE Tuition", amountCents: 100000 } });
  await db.feeItem.create({ data: { feeStructureId: temp.id, name: "SMOKE PTA (optional)", amountCents: 23456, isOptional: true } });
  const res = await post({ action: "clone-structures", sourceTermId: oldTerm.id });
  const body = await res.json();
  check("clone 200 with cloned ≥ 1", res.ok && body.cloned >= 1, JSON.stringify(body).slice(0, 120));
  const copy = await db.feeStructure.findFirst({ where: { schoolId, termId: currentTerm.id, classroomId: classroom.id }, include: { items: true } });
  check("copied structure carries total + items (incl. optional flag)", copy?.totalCents === 123456 && copy?.items.length === 2 && copy?.items.some((i) => i.isOptional));
  check("self-clone rejected", (await post({ action: "clone-structures", sourceTermId: currentTerm.id })).status === 400);
  // cleanup everything this section created
  if (copy) { await db.feeItem.deleteMany({ where: { feeStructureId: copy.id } }); await db.feeStructure.delete({ where: { id: copy.id } }); }
  await db.feeItem.deleteMany({ where: { feeStructureId: temp.id } });
  await db.feeStructure.delete({ where: { id: temp.id } });
  await db.classroom.delete({ where: { id: classroom.id } });

  console.log("\n── (b) bulk arrears CSV import ──");
  const students = await db.student.findMany({
    where: { enrollments: { some: { schoolId, status: "ACTIVE" } } },
    take: 2, orderBy: { nyansapoIndex: "asc" },
  });
  if (students.length < 2) { console.log("need 2 enrolled learners — skipping arrears check"); }
  else {
    const preCounters = await db.codeCounter.findMany({ where: { scope: `INV:${new Date().getFullYear()}` } });
    const csv = [
      "studentIndex,description,amount",
      `${students[0].nyansapoIndex},Balance from Term 2,246.00`,
      `${students[1].nyansapoIndex},Unpaid PTA levy,80`,
      "NY-9999-999999,Ghost learner,50",           // unknown index → row error
      `${students[0].nyansapoIndex},No amount,`,   // invalid amount → row error
    ].join("\n");
    const res = await post({ action: "import-arrears", csv });
    const body = await res.json();
    check("import 200 · imported=2, failed=2", res.ok && body.imported === 2 && body.failed === 2, JSON.stringify(body).slice(0, 160));
    check("total imported = ₵326.00", body.importedCents === 32600, String(body.importedCents));
    const arrearsInvoices = await db.invoice.findMany({
      where: { schoolId, termId: currentTerm.id, items: { some: { name: { startsWith: "Arrears — " } } } },
      include: { items: true },
    });
    check("2 OVERDUE arrears invoices created", arrearsInvoices.length === 2 && arrearsInvoices.every((i) => i.status === "OVERDUE"), `found ${arrearsInvoices.length}`);
    check("arrear item carries the description", arrearsInvoices.some((i) => i.items[0]?.name === "Arrears — Balance from Term 2"));
    // re-run → duplicates skipped, nothing new
    const res2 = await post({ action: "import-arrears", csv });
    const body2 = await res2.json();
    const dupRow = (body2.results ?? []).find((r: any) => r.duplicate);
    check("re-run skips duplicates", body2.imported === 0 && !!dupRow, JSON.stringify(body2).slice(0, 140));
    check("bad header rejected", (await post({ action: "import-arrears", csv: "name,amount\nX,10" })).status === 400);
    // cleanup
    for (const inv of arrearsInvoices) {
      await db.invoiceItem.deleteMany({ where: { invoiceId: inv.id } });
      await db.invoice.delete({ where: { id: inv.id } });
    }
    for (const pre of preCounters) await db.codeCounter.update({ where: { scope: pre.scope }, data: { next: pre.next } });
  }

  console.log(`\n${pass} passed, ${fail} failed`);
  process.exit(fail ? 1 : 0);
}
main();

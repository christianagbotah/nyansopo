/* Wave 31 — audit trail demo history for Golden Excellence Academy.

   Backfills the compliance book so the viewer opens with a lived-in
   register: payment collections and voids drawn from real Payment rows,
   result publications from real TermResult batches, certificate issues,
   wallet adjustments and an invoice discount approval — all shaped like
   the events the instrumented API routes write from now on.

   Idempotent: skips when the register already holds rows.
   Run: DATABASE_URL=... bun prisma/seed-audit.ts */
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

const ACTOR_POOL: { name: string; role: string }[] = [
  { name: "Madam Akosua Danso", role: "SCHOOL_ADMIN" },
  { name: "Mr. Kwesi Mensah", role: "ACCOUNTANT" },
  { name: "Ama Serwaa (Cashier)", role: "CASHIER" },
  { name: "Yaw Owusu (Cashier)", role: "CASHIER" },
  { name: "Mr. Samuel Adjei", role: "TEACHER" },
];
const pick = <T>(arr: T[], i: number) => arr[i % arr.length];

async function main() {
  const school = await db.school.findFirst({ where: { code: "GEA" } });
  if (!school) {
    console.log("school GEA not found — skipping audit seed");
    return;
  }
  const existing = await db.auditLog.count({ where: { schoolId: school.id } });
  if (existing > 0) {
    console.log(`✅ Audit seed skipped — register already holds ${existing} events`);
    return;
  }

  const payments = await db.payment.findMany({
    where: { schoolId: school.id, voided: false },
    orderBy: { paidAt: "desc" },
    take: 60,
    include: { student: { select: { firstName: true, lastName: true } } },
  });
  const voided = await db.payment.findMany({
    where: { schoolId: school.id, voided: true },
    take: 3,
    include: { student: { select: { firstName: true, lastName: true } } },
  });
  const cards = await db.reportCard.findMany({
    where: { published: true, student: { enrollments: { some: { schoolId: school.id } } } },
    take: 1,
    include: { term: { include: { academicYear: { select: { name: true } } } } },
  });
  const docs = await db.issuedDocument.findMany({
    where: { schoolId: school.id },
    take: 12,
    orderBy: { issueDate: "desc" },
  });

  const rows: any[] = [];
  let t = 0;

  // payment collections — the everyday hum of the bursary
  payments.forEach((p, i) => {
    const actor = pick(ACTOR_POOL, i + 1);
    const when = new Date(p.paidAt);
    when.setUTCHours(8 + (i % 9), (i * 7) % 60);
    rows.push({
      schoolId: school.id,
      actorName: actor.name, actorRole: actor.role,
      action: "CREATE", entityType: "PAYMENT",
      entityId: p.id,
      entityLabel: `${p.receiptNo} · ${p.student?.firstName ?? ""} ${p.student?.lastName ?? ""}`.trim(),
      summary: `Collected ₵${(p.amountCents / 100).toFixed(2)} (${p.method}) for ${p.category === "DAILY" ? "daily services" : p.category === "FEES" ? "termly fees" : p.category.toLowerCase()}`,
      afterJson: { receiptNo: p.receiptNo, amountCents: p.amountCents, method: p.method, category: p.category, receivedByName: p.receivedByName ?? actor.name },
      ip: `10.${20 + (i % 4)}.${(i * 3) % 250}.${(i * 17) % 250}`,
      createdAt: when,
    });
    t++;
  });

  // voided receipts — the governance moments
  voided.forEach((p, i) => {
    const actor = pick(ACTOR_POOL, i + 2);
    const when = new Date(p.paidAt);
    when.setUTCDate(when.getUTCDate() + 1);
    rows.push({
      schoolId: school.id,
      actorName: actor.name, actorRole: actor.role,
      action: "VOID", entityType: "PAYMENT",
      entityId: p.id,
      entityLabel: `${p.receiptNo} · ₵${(p.amountCents / 100).toFixed(2)}`,
      summary: `Voided receipt ${p.receiptNo} (₵${(p.amountCents / 100).toFixed(2)}, ${p.method}) — duplicate entry at the till`,
      beforeJson: { receiptNo: p.receiptNo, amountCents: p.amountCents, method: p.method, voided: false },
      afterJson: { receiptNo: p.receiptNo, amountCents: p.amountCents, method: p.method, voided: true, reason: "duplicate entry at the till" },
      ip: `10.20.${(i * 5) % 250}.${(i * 11) % 250}`,
      createdAt: when,
    });
    t++;
  });

  // results publication — the headteacher's signature act
  if (cards.length) {
    const c = cards[0];
    rows.push({
      schoolId: school.id,
      actorName: ACTOR_POOL[0].name, actorRole: ACTOR_POOL[0].role,
      action: "PUBLISH", entityType: "RESULT",
      entityId: `${c.termId}:${c.classroomName}`,
      entityLabel: `${c.classroomName} · ${c.term.name} ${c.term.academicYear.name}`,
      summary: `Published term results and report cards for ${c.classroomName} (${c.term.name} ${c.term.academicYear.name})`,
      afterJson: { termId: c.termId, classroom: c.classroomName, published: true, publishedAt: c.publishedAt?.toISOString() ?? null },
      ip: "10.20.4.18",
      createdAt: c.publishedAt ?? new Date(),
    });
    t++;
  }

  // certificate issues from the signature drawer
  docs.forEach((d, i) => {
    rows.push({
      schoolId: school.id,
      actorName: ACTOR_POOL[0].name, actorRole: ACTOR_POOL[0].role,
      action: d.status === "REVOKED" ? "REVOKE" : "ISSUE",
      entityType: "CERTIFICATE",
      entityId: d.id,
      entityLabel: `${d.serialNo} · ${d.studentName}`,
      summary: d.status === "REVOKED"
        ? `Revoked ${d.type.replace(/_/g, " ").toLowerCase()} ${d.serialNo} (${d.studentName})`
        : `Issued ${d.type.replace(/_/g, " ").toLowerCase()} ${d.serialNo} to ${d.studentName} (${d.className}, ${d.sessionYear})`,
      afterJson: { serialNo: d.serialNo, type: d.type, status: d.status },
      ip: "10.20.4.18",
      createdAt: d.createdAt,
    });
    t++;
  });

  // a couple of governed adjustments — wallet top-up & discount approval
  const student = await db.student.findFirst({
    where: { enrollments: { some: { schoolId: school.id } } },
  });
  if (student) {
    rows.push({
      schoolId: school.id,
      actorName: ACTOR_POOL[1].name, actorRole: ACTOR_POOL[1].role,
      action: "ADJUST", entityType: "FEES",
      entityLabel: `Wallet · ${student.firstName} ${student.lastName}`,
      summary: `Topped up the prepaid wallet with ₵50.00 for ${student.firstName} ${student.lastName}`,
      beforeJson: { prepaidCents: 0 }, afterJson: { prepaidCents: 5000 },
      ip: "10.20.2.31",
      createdAt: new Date(Date.now() - 3 * 24 * 3600 * 1000),
    });
    rows.push({
      schoolId: school.id,
      actorName: ACTOR_POOL[0].name, actorRole: ACTOR_POOL[0].role,
      action: "APPROVE", entityType: "DISCOUNT",
      entityLabel: "Sibling discount · 10%",
      summary: "Approved a 10% sibling discount on next term's bill for two learners",
      afterJson: { profile: "Sibling", method: "PERCENTAGE", value: 10 },
      ip: "10.20.4.18",
      createdAt: new Date(Date.now() - 5 * 24 * 3600 * 1000),
    });
    t += 2;
  }

  await db.auditLog.createMany({ data: rows });
  console.log(`✅ Audit seed complete — ${t} events for ${school.name}`);
}

main()
  .catch((e) => { console.error(e); process.exit(1); })
  .finally(() => db.$disconnect());

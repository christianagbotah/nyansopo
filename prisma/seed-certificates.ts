/* Wave 30 — certificates & documents demo data for Golden Excellence Academy.

   Fills the headteacher's signature drawer: serial-numbered School Leaving
   Certificates (SLC), Testimonials (TSM), Admission Letters (ADL) and
   Introductory Letters (INT) for the current academic year, plus one revoked
   leaving certificate so the register shows the full audit trail (who issued
   it, who revoked it, the red REVOKED watermark on reprint).

   Idempotent: skips when the document register already holds rows.
   Run: DATABASE_URL=... bun prisma/seed-certificates.ts */
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

const TYPE_PREFIX: Record<string, string> = {
  LEAVING_CERT: "SLC",
  TESTIMONIAL: "TSM",
  ADMISSION_LETTER: "ADL",
  INTRO_LETTER: "INT",
};

const REASONS: Record<string, string[]> = {
  LEAVING_CERT: [
    "Successful completion of JHS — proceeding to senior high school",
    "Relocation of parents to Kumasi",
    "Transfer to a school nearer the family home",
  ],
  INTRO_LETTER: [
    "Admission interview at Ghana National SHS",
    "Collection of BECE placement documents at the district office",
    "Transfer interview at St. Augustine's Preparatory",
  ],
};

const REMARKS = [
  "Honest, punctual and respectful; represented the school at the district science & maths quiz.",
  "Hardworking and well-behaved; served as class prefect and a member of the debate club.",
  "Diligent and courteous; a reliable member of the school choir and athletics team.",
  "Obedient and neatly presented; showed keen interest in cultural activities.",
  "Quiet, focused and cooperative; commendable improvement in mathematics.",
];

async function main() {
  const school = await db.school.findFirst({ where: { name: { contains: "Golden" } } });
  if (!school) throw new Error("Golden Excellence Academy school not found — run the base seed first");

  const existing = await db.issuedDocument.count({ where: { schoolId: school.id } });
  if (existing > 0) {
    console.log(`⏭️  Certificates seed skipped — ${existing} documents already on the register`);
    return;
  }

  const head = (await db.user.findFirst({ where: { schoolId: school.id, role: { in: ["SUPER_ADMIN", "SCHOOL_ADMIN"] } }, select: { fullName: true } }))?.fullName ?? "The Headteacher";
  const secretary = (await db.user.findFirst({ where: { schoolId: school.id, role: "NON_TEACHING" }, select: { fullName: true } }))?.fullName ?? head;

  const year = new Date().getUTCFullYear();
  const sessionYear = `${year}/${year + 1}`;
  const activeStudents = await db.student.findMany({
    where: { enrollments: { some: { schoolId: school.id, status: "ACTIVE" } } },
    select: { id: true, firstName: true, lastName: true, otherNames: true, nyansapoIndex: true, dateOfBirth: true },
    take: 60,
  });
  if (activeStudents.length < 10) throw new Error("Not enough learners to seed certificates");
  const fullName = (s: typeof activeStudents[number]) =>
    [s.lastName, s.firstName, s.otherNames].filter(Boolean).join(" ").replace(/\s+/g, " ").trim();

  const classrooms = await db.classroom.findMany({
    where: { schoolId: school.id },
    orderBy: { gradeLevel: "asc" },
    select: { id: true, name: true, gradeLevel: true },
  });
  const jhs = classrooms.filter((c) => /^B[7-9]/.test(c.gradeLevel) || /JHS/i.test(c.name));
  const primary = classrooms.filter((c) => /^B[1-6]/.test(c.gradeLevel));
  const pickClass = (i: number) => (jhs.length && i % 3 !== 2 ? jhs[i % jhs.length].name : primary.length ? primary[i % primary.length].name : classrooms[i % classrooms.length].name);

  const day = (month: number, d: number) => new Date(Date.UTC(year, month, d));
  const rows: {
    student: typeof activeStudents[number]; type: string; serialNo: string;
    issueDate: Date; className: string; reason: string | null; remarks: string; issuedBy: string;
  }[] = [];
  let n = 0;

  // 2 leaving certificates (issued when learners exited during the year)
  for (let i = 0; i < 2; i++, n++) {
    rows.push({
      student: activeStudents[n],
      type: "LEAVING_CERT",
      serialNo: `SLC-${year}-${String(i + 1).padStart(4, "0")}`,
      issueDate: day(1, 12 + i * 9),
      className: pickClass(n),
      reason: REASONS.LEAVING_CERT[i % REASONS.LEAVING_CERT.length],
      remarks: REMARKS[n % REMARKS.length],
      issuedBy: head,
    });
  }
  // 1 revoked leaving certificate — the audit-trail exhibit
  rows.push({
    student: activeStudents[n],
    type: "LEAVING_CERT",
    serialNo: `SLC-${year}-0003`,
    issueDate: day(2, 20),
    className: pickClass(n),
    reason: REASONS.LEAVING_CERT[n % REASONS.LEAVING_CERT.length],
    remarks: REMARKS[n % REMARKS.length],
    issuedBy: secretary,
  });
  // 4 testimonials for continuing learners
  for (let i = 0; i < 4; i++, n++) {
    rows.push({
      student: activeStudents[n],
      type: "TESTIMONIAL",
      serialNo: `TSM-${year}-${String(i + 1).padStart(4, "0")}`,
      issueDate: day(4, 6 + i * 5),
      className: pickClass(n),
      reason: null,
      remarks: REMARKS[n % REMARKS.length],
      issuedBy: head,
    });
  }
  // 2 admission letters for the new intake
  for (let i = 0; i < 2; i++, n++) {
    rows.push({
      student: activeStudents[n],
      type: "ADMISSION_LETTER",
      serialNo: `ADL-${year}-${String(i + 1).padStart(4, "0")}`,
      issueDate: day(0, 9 + i * 4),
      className: pickClass(n),
      reason: null,
      remarks: REMARKS[(n + 2) % REMARKS.length],
      issuedBy: head,
    });
  }
  // 2 introductory letters
  for (let i = 0; i < 2; i++, n++) {
    rows.push({
      student: activeStudents[n],
      type: "INTRO_LETTER",
      serialNo: `INT-${year}-${String(i + 1).padStart(4, "0")}`,
      issueDate: day(5, 14 + i * 6),
      className: pickClass(n),
      reason: REASONS.INTRO_LETTER[i % REASONS.INTRO_LETTER.length],
      remarks: REMARKS[(n + 1) % REMARKS.length],
      issuedBy: secretary,
    });
  }

  await db.issuedDocument.createMany({
    data: rows.map((r) => ({
      schoolId: school.id,
      studentId: r.student.id,
      type: r.type,
      serialNo: r.serialNo,
      issueDate: r.issueDate,
      studentName: fullName(r.student),
      studentIndex: r.student.nyansapoIndex,
      className: r.className,
      sessionYear,
      dateOfBirth: r.student.dateOfBirth,
      reason: r.reason,
      remarks: r.remarks,
      status: "ISSUED",
      issuedByName: r.issuedBy,
    })),
  });

  // revoke the exhibit row (SLC-0003) with a recorded revocation
  const exhibit = rows.find((r) => r.serialNo === `SLC-${year}-0003`)!;
  await db.issuedDocument.update({
    where: { schoolId_serialNo: { schoolId: school.id, serialNo: exhibit.serialNo } },
    data: {
      status: "REVOKED",
      revokedByName: head,
      revokedAt: new Date(Date.UTC(year, 2, 5, 10, 30)),
    },
  });

  const counts = TYPE_PREFIX;
  const byType = Object.keys(counts).map((t) => `${rows.filter((r) => r.type === t).length} ${counts[t]}`).join(" · ");
  console.log(`✅ Certificates seed complete — ${rows.length} documents (${byType}, 1 revoked) for ${school.name}`);
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

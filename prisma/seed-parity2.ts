/* Parity-2 seed — reference data for the final iEduPro parity batch:
   WAEC raw-score grades · boarding (houses/dormitories/beds/boarders) ·
   question papers · library book requests · receipt modification requests ·
   a week of portfolio (SBA) entries · ops-setting toggles · handover sample.
   Idempotent — safe to re-run. Run: bun prisma/seed-parity2.ts */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();
const C = (cedis: number) => Math.round(cedis * 100);

/* WAEC WASSCE/BECE scale — exact CI3 raw_score_grade auto-seed
   (views/backend/admin/raw_score_grade.php lines 5-15) */
const WAEC_GRADES = [
  { name: "EXCELLENT", gradePoint: "A1", gradePointNumeric: 1, markFrom: 80, markUpto: 100, comment: "Excellent performance" },
  { name: "VERY GOOD", gradePoint: "B2", gradePointNumeric: 2, markFrom: 70, markUpto: 79, comment: "Very good performance" },
  { name: "GOOD", gradePoint: "B3", gradePointNumeric: 3, markFrom: 65, markUpto: 69, comment: "Good performance" },
  { name: "CREDIT", gradePoint: "C4", gradePointNumeric: 4, markFrom: 60, markUpto: 64, comment: "Credit" },
  { name: "CREDIT", gradePoint: "C5", gradePointNumeric: 5, markFrom: 55, markUpto: 59, comment: "Credit" },
  { name: "CREDIT", gradePoint: "C6", gradePointNumeric: 6, markFrom: 50, markUpto: 54, comment: "Credit" },
  { name: "PASS", gradePoint: "D7", gradePointNumeric: 7, markFrom: 45, markUpto: 49, comment: "Pass" },
  { name: "PASS", gradePoint: "E8", gradePointNumeric: 8, markFrom: 40, markUpto: 44, comment: "Pass" },
  { name: "FAIL", gradePoint: "F9", gradePointNumeric: 9, markFrom: 0, markUpto: 39, comment: "Fail" },
];

async function main() {
  const schools = await db.school.findMany({ select: { id: true, code: true } });
  console.log(`🏫 parity-2 seed for ${schools.length} schools`);

  for (const school of schools) {
    const schoolId = school.id;

    // ── 1. WAEC raw-score grades (skip when already present) ──
    const haveGrades = await db.rawScoreGrade.count({ where: { schoolId } });
    if (!haveGrades) {
      await db.rawScoreGrade.createMany({
        data: WAEC_GRADES.map((g, i) => ({ ...g, schoolId, sortOrder: i })),
      });
      console.log(`✅ ${school.code}: 9 WAEC raw-score grades seeded (A1 80-100 … F9 0-39)`);
    }

    // ── 2. ops-setting toggles ──
    const toggles: Record<string, string> = {
      BOARDING_SYSTEM: "yes", // iEduPro settings.boarding_system
      PORTFOLIO_ASSESSMENT: "1", // iEduPro portfolio_assessment_enabled
      RAW_SCORE_GRADING: "Yes", // iEduPro settings.raw_score
      SSNIT_TIER1_EMPLOYER: "13.5",
      SSNIT_TIER1_EMPLOYEE: "5.5",
      SSNIT_TIER2: "5.0",
    };
    for (const [key, value] of Object.entries(toggles)) {
      await db.opsSetting.upsert({
        where: { schoolId_key: { schoolId, key } },
        create: { schoolId, key, value },
        update: { value, updatedAt: new Date() },
      });
    }

    // ── 3. boarding: houses → dormitories → beds → boarders ──
    const houseCount = await db.boardingHouse.count({ where: { schoolId } });
    if (!houseCount) {
      const masters = await db.staff.findMany({
        where: { schoolId, category: "TEACHING" },
        orderBy: { fullName: "asc" }, take: 2,
      });
      const [h1, h2] = await Promise.all([
        db.boardingHouse.create({
          data: {
            schoolId, name: "Agyei House",
            description: "Named for the school's founding house — boys' wing",
            houseMasterId: masters[0]?.id ?? null,
            capacity: 60, yearEstablished: "2012", gpsCode: "GH-545-231",
            termlyFeeCents: C(450),
          },
        }),
        db.boardingHouse.create({
          data: {
            schoolId, name: "Sutherland House",
            description: "Girls' wing — the calm compound",
            houseMasterId: masters[1]?.id ?? null,
            capacity: 54, yearEstablished: "2015", gpsCode: "GH-545-232",
            termlyFeeCents: C(450),
          },
        }),
      ]);

      const dormSpec = [
        { houseId: h1.id, name: "Agyei Block A", type: "Male", floor: "Ground", prefix: "AGA", beds: 10 },
        { houseId: h1.id, name: "Agyei Block B", type: "Male", floor: "First", prefix: "AGB", beds: 8 },
        { houseId: h2.id, name: "Sutherland Wing 1", type: "Female", floor: "Ground", prefix: "SU1", beds: 10 },
        { houseId: h2.id, name: "Sutherland Wing 2", type: "Female", floor: "First", prefix: "SU2", beds: 8 },
      ];
      const dorms: { id: string; prefix: string; beds: number }[] = [];
      for (const d of dormSpec) {
        const dorm = await db.dormitory.create({
          data: {
            schoolId, houseId: d.houseId, name: d.name, type: d.type, floor: d.floor,
            capacity: d.beds, description: `${d.type} dormitory — ${d.beds} beds`,
          },
        });
        dorms.push({ id: dorm.id, prefix: d.prefix, beds: d.beds });
        await db.bed.createMany({
          data: Array.from({ length: d.beds }, (_, i) => ({
            schoolId, dormitoryId: dorm.id,
            code: `${d.prefix}-${String(i + 1).padStart(3, "0")}`,
            bedType: i % 3 === 2 ? "BUNK" : "SINGLE",
          })),
        });
      }

      // assign boarders: pick JHS learners, alternate by gender into wings
      const jhs = await db.enrollment.findMany({
        where: { schoolId, status: "ACTIVE", classroom: { gradeLevel: { in: ["B7", "B8", "B9"] } } },
        include: { student: true }, take: 12,
      });
      const maleDorms = dorms.filter((d) => d.prefix.startsWith("AG"));
      const femaleDorms = dorms.filter((d) => d.prefix.startsWith("SU"));
      let assigned = 0;
      const takenCodes = new Set<string>();
      for (const en of jhs) {
        const isMale = en.student.gender === "M";
        const pool = (isMale ? maleDorms : femaleDorms)
          .flatMap((d) => Array.from({ length: d.beds }, (_, i) => ({ code: `${d.prefix}-${String(i + 1).padStart(3, "0")}`, dormId: d.id })))
          .filter((b) => !takenCodes.has(b.code));
        if (!pool.length) continue;
        const bed = pool[assigned % pool.length];
        takenCodes.add(bed.code);
        await db.bed.update({
          where: { schoolId_code: { schoolId, code: bed.code } },
          data: { status: "ASSIGNED", studentId: en.studentId, assignedAt: new Date() },
        });
        await db.enrollment.update({ where: { id: en.id }, data: { residenceType: "BOARDING" } });
        assigned++;
      }
      console.log(`✅ ${school.code}: 2 houses · 4 dormitories · 36 beds · ${assigned} boarders assigned`);
    }

    // ── 4. question papers ──
    const paperCount = await db.questionPaper.count({ where: { schoolId } });
    if (!paperCount) {
      const classrooms = await db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" } });
      const jhs3 = classrooms.find((c) => /JHS 3|B9/i.test(c.name)) ?? classrooms[0];
      const b4 = classrooms.find((c) => /Basic 4/i.test(c.name)) ?? classrooms[1] ?? classrooms[0];
      await db.questionPaper.createMany({
        data: [
          {
            schoolId, classroomId: jhs3.id, title: "Mathematics — End of Term 1 Paper",
            examName: "End of Term 1 Examination 2026/27", authorName: "Madam Grace Mensah",
            body: `<h3>SECTION A — OBJECTIVE (40 marks)</h3><p>Answer ALL questions.</p><ol><li>Evaluate 3(2x − 4) when x = 5. <b>(2 marks)</b></li><li>Simplify (a³b²)² ÷ a²b⁴. <b>(2 marks)</b></li><li>Find the HCF of 48 and 180. <b>(2 marks)</b></li></ol><h3>SECTION B — THEORY (60 marks)</h3><p>Answer ANY FOUR questions.</p><ol start="4"><li>(a) Solve the simultaneous equations 2x + y = 7, x − y = 2. <b>(5 marks)</b><br/>(b) The ratio of boys to girls in JHS 3 is 3:4. If there are 28 learners, how many are girls? <b>(5 marks)</b></li><li>A trader bought a bag of rice for ₵420 and sold it for ₵483. Find the percentage profit. <b>(10 marks)</b></li></ol>`,
          },
          {
            schoolId, classroomId: jhs3.id, title: "Integrated Science — Section B Only",
            examName: "End of Term 1 Examination 2026/27", authorName: "Mr. Kwabena Owusu",
            body: `<h3>THEORY — Answer any FOUR (40 marks)</h3><ol><li>(a) Define photosynthesis. <b>(2 marks)</b><br/>(b) State TWO conditions necessary for photosynthesis. <b>(4 marks)</b></li><li>Draw and label the parts of a plant cell. <b>(10 marks)</b></li><li>Explain the difference between renewable and non-renewable energy, giving one example each. <b>(10 marks)</b></li></ol>`,
          },
          {
            schoolId, classroomId: b4.id, title: "English Language — Comprehension & Composition",
            examName: "End of Term 1 Examination 2026/27", authorName: "Madam Akosua Danso",
            body: `<h3>SECTION A — COMPREHENSION (20 marks)</h3><p>Read the passage below and answer the questions that follow.</p><p><i>Adjoa loved market days in Madina. The air smelled of roasted plantain and fresh tomatoes…</i></p><ol><li>Why did Adjoa wake up early on market days? <b>(2 marks)</b></li><li>Give one word from the passage that means the same as "crowded". <b>(2 marks)</b></li></ol><h3>SECTION B — COMPOSITION (20 marks)</h3><p>Write a letter to your friend describing your school's sports day. Address: Kanda, Accra.</p>`,
          },
        ],
      });
      console.log(`✅ ${school.code}: 3 question papers seeded`);
    }

    // ── 5. library book requests (pending queue) ──
    const reqCount = await db.bookRequest.count({ where: { schoolId } });
    if (!reqCount) {
      const books = await db.libraryBook.findMany({ where: { schoolId }, take: 3, orderBy: { title: "asc" } });
      const students = await db.student.findMany({
        where: { schoolId: undefined, enrollments: { some: { schoolId, status: "ACTIVE" } } },
        take: 3,
      });
      if (books.length && students.length) {
        const day = 864e5;
        await db.bookRequest.createMany({
          data: books.map((b, i) => ({
            schoolId, bookId: b.id, studentId: students[i % students.length].id,
            startAt: new Date(Date.now() + day), endAt: new Date(Date.now() + 8 * day),
          })),
        });
        console.log(`✅ ${school.code}: ${books.length} pending book requests`);
      }
    }

    // ── 6. receipt modification requests (approval queue) ──
    const modCount = await db.modificationRequest.count({ where: { schoolId } });
    if (!modCount) {
      const cashierUser = await db.user.findFirst({
        where: { schoolId, role: "CASHIER" },
        include: { staff: true },
      });
      const pay = await db.payment.findFirst({
        where: { schoolId, method: "CASH" },
        orderBy: { paidAt: "desc" },
      });
      if (pay && cashierUser) {
        await db.modificationRequest.create({
          data: {
            schoolId, targetType: "PAYMENT", recordId: pay.id, recordCode: pay.receiptNo,
            requestType: "DELETE",
            reason: "Receipt issued to the wrong learner — guardian paid at the bank the same morning.",
            requestedById: cashierUser.id,
            requestedByName: cashierUser.fullName,
            originalData: JSON.stringify({ id: pay.id, receiptNo: pay.receiptNo, amountCents: pay.amountCents, method: pay.method }),
          },
        });
      }
      const inv = await db.invoice.findFirst({ where: { schoolId }, orderBy: { createdAt: "desc" } });
      const bursarUser = await db.user.findFirst({ where: { schoolId, role: "ACCOUNTANT" } });
      if (inv && bursarUser) {
        await db.modificationRequest.create({
          data: {
            schoolId, targetType: "INVOICE", recordId: inv.id, recordCode: inv.invoiceNo,
            requestType: "EDIT",
            reason: "Term 2 invoice duplicated the sports levy — remove the ₵15 line.",
            requestedById: bursarUser.id,
            requestedByName: bursarUser.fullName,
            originalData: JSON.stringify({ id: inv.id, invoiceNo: inv.invoiceNo, totalCents: inv.totalCents }),
          },
        });
      }
      console.log(`✅ ${school.code}: modification-approval requests seeded`);
    }

    // ── 7. portfolio (weekly SBA) entries — one completed week for Basic 4 maths ──
    const portfolioCount = await db.portfolioEntry.count({ where: { schoolId } });
    if (!portfolioCount) {
      const b4 = await db.classroom.findFirst({ where: { schoolId, name: { contains: "Basic 4" } } });
      const maths = await db.subject.findFirst({ where: { schoolId, name: { contains: "Mathematics" } } });
      if (b4 && maths) {
        const ens = await db.enrollment.findMany({
          where: { schoolId, status: "ACTIVE", classroomId: b4.id }, take: 10,
        });
        // most recent completed ISO week (Mon–Fri)
        const now = new Date();
        const monday = new Date(now);
        monday.setUTCDate(now.getUTCDate() - ((now.getUTCDay() + 6) % 7) - 7);
        const isoWeek = `${monday.getUTCFullYear()}-W${String(
          Math.ceil((((monday.getTime() - Date.UTC(monday.getUTCFullYear(), 0, 1)) / 864e5) + 1) / 7),
        ).padStart(2, "0")}`;
        const strands = ["B4.1.1.1", "B4.1.2.1", "B4.1.2.2", "B4.2.1.1", "B4.2.1.2"];
        const rows: any[] = [];
        ens.forEach((en, si) => {
          for (let d = 0; d < 5; d++) {
            const date = new Date(monday);
            date.setUTCDate(monday.getUTCDate() + d);
            // deterministic per-student/day score 4–10
            const score = 4 + ((si * 3 + d * 5 + 7) % 7);
            rows.push({
              schoolId, studentId: en.studentId, classroomId: b4.id, subjectId: maths.id,
              subjectName: maths.name, week: isoWeek, entryDate: date,
              strandCode: strands[d], score,
            });
          }
        });
        await db.portfolioEntry.createMany({ data: rows });
        console.log(`✅ ${school.code}: ${rows.length} portfolio entries (${isoWeek}, Basic 4 maths)`);
      }
    }

    // ── 8. handover report sample ──
    const handoverCount = await db.handoverReport.count({ where: { schoolId } });
    if (!handoverCount) {
      const cashierUser = await db.user.findFirst({ where: { schoolId, role: "CASHIER" }, include: { staff: true } });
      const bursarUser = await db.user.findFirst({ where: { schoolId, role: "ACCOUNTANT" } });
      if (cashierUser && bursarUser) {
        await db.handoverReport.create({
          data: {
            schoolId, date: new Date(Date.now() - 864e5),
            cashierId: cashierUser.id, cashierName: cashierUser.fullName,
            receiverId: bursarUser.id, receiverName: bursarUser.fullName,
            totalCents: C(1284.5), cashCents: C(940),
          },
        });
        console.log(`✅ ${school.code}: handover report sample`);
      }
    }
  }

  console.log("🎉 parity-2 seed done");
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

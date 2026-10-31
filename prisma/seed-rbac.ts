/* Nyansapo RBAC + portal seed — admin ladder users (levels 1–5), librarian,
   non-teaching staff, parent & learner accounts, library catalogue & loans,
   transport per-trip fares and boarding history.
   Idempotent standalone run: DATABASE_URL=… bun prisma/seed-rbac.ts */
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();
const C = (cedis: number) => Math.round(cedis * 100);

let _s = 20261031;
function rnd() {
  _s |= 0; _s = (_s + 0x6d2b79f5) | 0;
  let t = Math.imul(_s ^ (_s >>> 15), 1 | _s);
  t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
  return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
}
const pick = <T,>(a: T[]) => a[Math.floor(rnd() * a.length)];
const int = (min: number, max: number) => min + Math.floor(rnd() * (max - min + 1));

function lastSchoolDays(n: number, before: Date): Date[] {
  const out: Date[] = [];
  const d = new Date(before);
  while (out.length < n) {
    const day = d.getDay();
    if (day !== 0 && day !== 6) out.push(new Date(d));
    d.setDate(d.getDate() - 1);
  }
  return out.reverse();
}
const dayKey = (d: Date) => new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));

async function main() {
  const pw = "nyansapo";
  const GEA = await db.school.findUnique({ where: { code: "GEA" } });
  if (!GEA) throw new Error("Run the base seed first (prisma/seed.ts) — school GEA not found");
  console.log(`🏫 ${GEA.name}`);

  /* ── 1. staff records for librarian & non-teaching staff ── */
  console.log("👩‍🏫 staff records…");
  const librarianStaff = await db.staff.upsert({
    where: { id: (await db.staff.findFirst({ where: { schoolId: GEA.id, fullName: "Efe Dzide" } }))?.id ?? "__none__" },
    update: {},
    create: {
      schoolId: GEA.id, fullName: "Efe Dzide", role: "ASSISTANT", title: "Librarian",
      category: "NON_TEACHING", position: "Librarian", department: "Library",
      staffCode: "NTS-00021", qualification: "Dip. Library & Information Studies",
      email: "librarian@nyansapo.gh", phone: "+233 24 303 4455",
      employmentType: "FULL_TIME", salaryCents: C(1450), hiredAt: new Date("2022-01-10"),
      ssnitNumber: "C99887766", momoNumber: "0243034455", active: true,
    },
  });
  const driverStaff = await db.staff.upsert({
    where: { id: (await db.staff.findFirst({ where: { schoolId: GEA.id, fullName: "Yaw Frimpong" } }))?.id ?? "__none__" },
    update: {},
    create: {
      schoolId: GEA.id, fullName: "Yaw Frimpong", role: "ASSISTANT", title: "Bus Driver",
      category: "NON_TEACHING", position: "Driver", department: "Transport",
      staffCode: "NTS-00022", qualification: "License C",
      email: "staff@nyansapo.gh", phone: "+233 20 707 1188",
      employmentType: "FULL_TIME", salaryCents: C(1100), hiredAt: new Date("2023-02-01"),
      ssnitNumber: "C88776655", momoNumber: "0207071188", active: true,
    },
  });

  /* ── 2. link existing demo accounts to their staff records ── */
  console.log("🔗 linking staff accounts…");
  const linkIf = async (email: string, staffEmail: string) => {
    const u = await db.user.findUnique({ where: { email } });
    if (!u || u.staffId) return;
    const st = await db.staff.findFirst({ where: { schoolId: GEA.id, email: staffEmail } });
    if (st) await db.user.update({ where: { id: u.id }, data: { staffId: st.id } });
  };
  await linkIf("head@nyansapo.gh", "head@nyansapo.gh");
  await linkIf("teacher@nyansapo.gh", "teacher@nyansapo.gh");
  await linkIf("bursar@nyansapo.gh", "bursar@nyansapo.gh");

  /* ── 3. parent & learner links ── */
  console.log("👨‍👩‍👧 parent & learner accounts…");
  // parent: two GEA learners in Basic 4/5 whose guardian becomes Mrs. Comfort Adjei
  const parentCandidates = await db.enrollment.findMany({
    where: { schoolId: GEA.id, status: "ACTIVE", classroom: { name: { contains: "Basic 4" } } },
    include: { student: true, classroom: true }, take: 12,
  });
  if (parentCandidates.length < 2) throw new Error("Not enough Basic 4 learners for the parent demo");
  const chosen = parentCandidates.slice(0, 2);
  for (const e of chosen) {
    await db.student.update({
      where: { id: e.studentId },
      data: { guardianName: "Mrs. Comfort Adjei", guardianPhone: "+233 24 818 3344", guardianEmail: "parent@nyansapo.gh", relationship: "Mother" },
    });
  }
  const parentStudentIds = chosen.map((e) => e.studentId);

  // learner: a GEA learner with marks & report cards (JHS 3)
  const learnerEnrollment = (
    await db.enrollment.findMany({
      where: { schoolId: GEA.id, status: "ACTIVE", classroom: { name: { in: ["JHS 3 (B9)", "JHS 3 Gold", "B9"] } } },
      include: { student: { include: { reportCards: { take: 1 }, marks: { take: 1 } } }, classroom: true },
      take: 10,
    })
  ).find((e) => e.student.marks.length > 0)
    ?? (await db.enrollment.findMany({
      where: { schoolId: GEA.id, status: "ACTIVE" },
      include: { student: { include: { reportCards: { take: 1 }, marks: { take: 1 } } }, classroom: true },
      take: 60,
    })).find((e) => e.student.marks.length > 0);
  if (!learnerEnrollment) throw new Error("No learner with marks found for the student demo account");
  await db.student.update({
    where: { id: learnerEnrollment.studentId },
    data: { guardianEmail: "student@nyansapo.gh" },
  });

  /* ── 4. the admin ladder + portal accounts ── */
  console.log("👥 users…");
  const users: { email: string; fullName: string; role: string; title: string; phone: string; staffId?: string; studentId?: string; linkedStudentIds?: string[] }[] = [
    { email: "super@nyansapo.gh", fullName: "Madam Akosua Danso", role: "SUPER_ADMIN", title: "System Administrator — Golden Excellence Academy", phone: "+233 24 900 1001" },
    { email: "cashier@nyansapo.gh", fullName: "Lawrencia Ahwireng", role: "CASHIER", title: "Front-Desk Cashier — Golden Excellence Academy", phone: "+233 24 552 7788" },
    { email: "conductor@nyansapo.gh", fullName: "Kwabena Mensah", role: "BUS_CONDUCTOR", title: "Bus Conductor — Madina–Atomic & Ashaley Botwe routes", phone: "+233 20 414 5566" },
    { email: "librarian@nyansapo.gh", fullName: "Efe Dzide", role: "LIBRARIAN", title: "Librarian — Golden Excellence Academy", phone: "+233 24 303 4455", staffId: librarianStaff.id },
    { email: "staff@nyansapo.gh", fullName: "Yaw Frimpong", role: "NON_TEACHING", title: "Bus Driver — Transport department", phone: "+233 20 707 1188", staffId: driverStaff.id },
    { email: "parent@nyansapo.gh", fullName: "Mrs. Comfort Adjei", role: "PARENT", title: "Parent — two learners in Basic 4", phone: "+233 24 818 3344", linkedStudentIds: parentStudentIds },
    { email: "student@nyansapo.gh", fullName: learnerEnrollment.student.firstName + " " + learnerEnrollment.student.lastName, role: "STUDENT", title: `Learner — ${learnerEnrollment.classroom.name}`, phone: "", studentId: learnerEnrollment.studentId },
  ];
  for (const u of users) {
    await db.user.upsert({
      where: { email: u.email },
      update: { role: u.role, title: u.title, fullName: u.fullName, staffId: u.staffId ?? null, studentId: u.studentId ?? null, linkedStudentIds: u.linkedStudentIds ? JSON.stringify(u.linkedStudentIds) : null, status: "ACTIVE" },
      create: {
        email: u.email, password: pw, fullName: u.fullName, phone: u.phone || null, role: u.role, title: u.title,
        schoolId: GEA.id, staffId: u.staffId ?? null, studentId: u.studentId ?? null,
        linkedStudentIds: u.linkedStudentIds ? JSON.stringify(u.linkedStudentIds) : null,
      },
    });
    console.log(`   • ${u.email} — ${u.role}`);
  }

  // bursar keeps payroll scope; give head@ the level-2 admin it already has
  await db.user.update({ where: { email: "head@nyansapo.gh" }, data: { title: "Head of School (Administrator — Level 2) — Golden Excellence Academy" } });

  /* ── 5. transport per-trip fares + boarding history ── */
  console.log("🚌 boardings…");
  const routes = await db.transportRoute.findMany({ where: { schoolId: GEA.id }, include: { assignments: { where: { active: true } } } });
  const days = lastSchoolDays(6, new Date());
  for (const r of routes) {
    if (!r.farePerTripCents) {
      await db.transportRoute.update({ where: { id: r.id }, data: { farePerTripCents: C(r.name.includes("Atomic") ? 4 : 3.5) } });
    }
    const fare = r.farePerTripCents || C(4);
    for (const day of days) {
      for (const a of r.assignments) {
        const roll = rnd();
        const direction = roll < 0.08 ? "NONE" : roll < 0.55 ? "BOTH" : roll < 0.8 ? "IN" : "OUT";
        const fareDue = direction === "BOTH" ? fare * 2 : direction === "NONE" ? 0 : fare;
        const paid = direction === "NONE" ? 0 : rnd() < 0.82 ? fareDue : Math.round(fareDue * 0.5);
        await db.transportBoarding.upsert({
          where: { assignmentId_date: { assignmentId: a.id, date: dayKey(day) } },
          update: {},
          create: { assignmentId: a.id, routeId: r.id, date: dayKey(day), direction, fareCents: fareDue, paidCents: paid, recordedByName: "Kwabena Mensah" },
        });
      }
    }
  }

  /* ── 6. library catalogue & loans ── */
  console.log("📚 library…");
  const catalogue: { title: string; author: string; isbn?: string; category: string; copies: number; shelf: string }[] = [
    { title: "Anansi the Spider: A Tale from the Ashanti", author: "Gerald McDermott", isbn: "978-0805003116", category: "Storybook", copies: 6, shelf: "A-1" },
    { title: "The Cunning Tortoise & Other Ghanaian Tales", author: "Meshack Asare", category: "Storybook", copies: 4, shelf: "A-1" },
    { title: "Sosu's Call", author: "Meshack Asare", isbn: "978-0888995381", category: "Storybook", copies: 3, shelf: "A-2" },
    { title: "GES Mathematics for JHS 2", author: "Ministry of Education", category: "Textbook", copies: 24, shelf: "T-3" },
    { title: "GES English Language for JHS 3", author: "Ministry of Education", category: "Textbook", copies: 20, shelf: "T-3" },
    { title: "Integrated Science for Basic Schools 6", author: "Adomako & Boadi", category: "Textbook", copies: 18, shelf: "T-4" },
    { title: "OWOP: Our World Our People for Basic 5", author: "Ghana Education Service", category: "Textbook", copies: 15, shelf: "T-4" },
    { title: "Ladybird Readers: Ghana Edition Level 3", author: "Ladybird", category: "Language", copies: 10, shelf: "L-2" },
    { title: "Junior Graphic Story Collection", author: "Graphic Communications", category: "Fiction", copies: 8, shelf: "F-1" },
    { title: "The Girl Who Can (African Short Stories)", author: "Ama Ata Aidoo", isbn: "978-9988550271", category: "Fiction", copies: 5, shelf: "F-1" },
    { title: "Kente Cloth & Ghanaian Adinkra Symbols", author: "Kwaku Ofori-Ansah", category: "History", copies: 4, shelf: "H-2" },
    { title: "Oxford School Atlas for West Africa", author: "Oxford", category: "Reference", copies: 6, shelf: "R-1" },
    { title: "Young Scientists Encyclopedia: Human Body", author: "Kingfisher", category: "Science", copies: 5, shelf: "S-2" },
    { title: "BECE Past Questions 2021–2025 (Compiled)", author: "WAEC", category: "Reference", copies: 12, shelf: "R-2" },
  ];
  const bookIds: Record<string, string> = {};
  for (const b of catalogue) {
    const existing = await db.libraryBook.findFirst({ where: { schoolId: GEA.id, title: b.title }, include: { loans: { where: { returnedAt: null } } } });
    if (existing) { bookIds[b.title] = existing.id; continue; }
    const book = await db.libraryBook.create({
      data: { schoolId: GEA.id, title: b.title, author: b.author, isbn: b.isbn ?? null, category: b.category, copies: b.copies, available: b.copies, shelf: b.shelf },
    });
    bookIds[b.title] = book.id;
  }

  // circulation: active, overdue and returned loans for GEA learners
  const learners = await db.enrollment.findMany({
    where: { schoolId: GEA.id, status: "ACTIVE" },
    include: { student: true, classroom: true }, take: 60,
  });
  const activeTitles = ["Anansi the Spider: A Tale from the Ashanti", "Sosu's Call", "The Girl Who Can (African Short Stories)", "Ladybird Readers: Ghana Edition Level 3", "Kente Cloth & Ghanaian Adinkra Symbols", "GES Mathematics for JHS 2", "Junior Graphic Story Collection"];
  let issuedCount = 0;
  for (let i = 0; i < learners.length && issuedCount < 11; i++) {
    const e = learners[int(0, learners.length - 1)];
    const title = pick(activeTitles);
    const bookId = bookIds[title];
    if (!bookId) continue;
    const book = await db.libraryBook.findUnique({ where: { id: bookId } });
    if (!book || book.available < 1) continue;
    const already = await db.libraryLoan.findFirst({ where: { bookId, studentId: e.studentId, returnedAt: null } });
    if (already) continue;
    const mode = issuedCount % 4; // 0 = overdue, 1/2 = active fresh, 3 = already returned
    const daysAgo = mode === 0 ? 24 : int(1, 6);
    const loanDays = 14;
    const issuedAt = new Date(Date.now() - daysAgo * 864e5);
    const dueAt = new Date(issuedAt.getTime() + loanDays * 864e5);
    if (mode === 3) {
      await db.libraryLoan.create({
        data: { bookId, studentId: e.studentId, schoolId: GEA.id, issuedAt, dueAt, returnedAt: new Date(Date.now() - int(0, 3) * 864e5), issuedByName: "Efe Dzide", conditionNote: "Good" },
      });
    } else {
      await db.libraryLoan.create({
        data: { bookId, studentId: e.studentId, schoolId: GEA.id, issuedAt, dueAt, issuedByName: "Efe Dzide" },
      });
      await db.libraryBook.update({ where: { id: bookId }, data: { available: { decrement: 1 } } });
    }
    issuedCount++;
  }
  console.log(`   ${issuedCount} loans issued`);

  /* ── 7. payslips for the new staff in the latest payroll run ── */
  console.log("💰 payslips for new staff…");
  const latestRun = await db.payrollRun.findFirst({ where: { schoolId: GEA.id }, orderBy: { period: "desc" } });
  if (latestRun) {
    for (const st of [librarianStaff, driverStaff]) {
      if (!st.salaryCents) continue;
      const exists = await db.payslip.findFirst({ where: { payrollRunId: latestRun.id, staffId: st.id } });
      if (exists) continue;
      const { calcPayslip } = await import("../src/lib/payroll");
      const calc = calcPayslip(st.salaryCents, [], []);
      await db.payslip.create({
        data: {
          payrollRunId: latestRun.id, staffId: st.id, baseSalaryCents: st.salaryCents,
          allowancesJson: "[]", grossCents: calc.grossCents,
          ssnitEmployeeCents: calc.ssnitEmployeeCents, ssnitEmployerCents: calc.ssnitEmployerCents,
          payeCents: calc.payeCents, otherDeductionsJson: "[]",
          totalDeductionsCents: calc.totalDeductionsCents, netCents: calc.netCents,
          status: latestRun.status === "PAID" ? "PAID" : "PENDING",
          paidAt: latestRun.status === "PAID" ? latestRun.paidAt : null,
          reference: `PS-${latestRun.period}/${st.staffCode ?? st.id.slice(-4)}`,
          workingDays: 22, daysPresent: 22,
        },
      });
      await db.payrollRun.update({
        where: { id: latestRun.id },
        data: {
          totalGrossCents: { increment: calc.grossCents },
          totalDeductionsCents: { increment: calc.totalDeductionsCents },
          totalNetCents: { increment: calc.netCents },
          totalEmployerSsnitCents: { increment: calc.ssnitEmployerCents },
        },
      });
      console.log(`   • ${st.fullName} → ${latestRun.period}`);
    }
  }

  /* ── 8. announcements for parents ── */
  console.log("📢 notices…");
  const existingNotice = await db.announcement.findFirst({ where: { schoolId: GEA.id, title: "PTA General Meeting — First Friday" } });
  if (!existingNotice) {
    await db.announcement.create({
      data: { schoolId: GEA.id, title: "PTA General Meeting — First Friday", body: "Dear parents, our termly PTA meeting holds this Friday at 2:30pm in the main hall. Class teachers will share terminal reports and the bursar will be available for fee questions. Please be seated by 2:15pm.", audience: "PARENTS", category: "CALENDAR", priority: "HIGH", postedByName: "Madam Grace Mensah" },
    });
    await db.announcement.create({
      data: { schoolId: GEA.id, title: "MoMo payments now land instantly", body: "Fee payments by MoMo with your child's Nyansapo index as reference are now reconciled same-day. No more waiting 24 hours!", audience: "PARENTS", category: "GENERAL", priority: "NORMAL", postedByName: "Mr. Patrick Quaye" },
    });
  }

  console.log("✅ RBAC & portal seed complete — sign in with super / cashier / conductor / librarian / staff / parent / student @nyansapo.gh (password: nyansapo)");
}

main()
  .catch((e) => { console.error(e); process.exit(1); })
  .finally(() => db.$disconnect());

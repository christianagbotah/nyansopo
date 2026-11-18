import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { sortClassrooms } from "@/lib/classes";

// ── DRIVE LIST (iEduPro CI3 `admin/get_drive_list` + `drive_list_page` parity)
//    The debtors board: every fee stream that can leave a learner owing, each
//    with its own card. Legacy offered six fixed types (billed invoices,
//    feeding, classes, transport, water, breakfast — all columns of
//    daily_fee_wallet). This schema models daily fees as services, so the
//    board is built dynamically: the invoices card plus one card per active
//    daily service carrying OWED charges — stronger parity, same doctrine:
//
//    GET ?action=cards                     — card summary (arrears + counts)
//        ?action=list&type=invoices|service:{serviceId}&classroom={id}
//                                           — class-grouped debtors register
//
//    Arrears doctrine (matches the daily module ledger):
//      • invoices  → sum(total − discount − paid) over UNPAID / PARTIAL /
//                    OVERDUE, written-offs excluded
//      • services  → sum(DailyCharge.amountCents) over status = OWED
//    Grouping: each learner's ACTIVE enrollment classroom; learners without
//    an active placement land in the trailing "Unassigned" group so no debt
//    silently disappears from the board.

type DebtRow = { studentId: string; amountCents: number };

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["fees.view", "cashier.reports", "daily.collect"] });
  if (!auth.ok) return auth.res;
  const url = new URL(req.url);
  const q = (k: string) => url.searchParams.get(k) ?? undefined;
  const action = q("action") ?? "cards";

  // classroom label per student (ACTIVE placement wins)
  const enrollments = await db.enrollment.findMany({
    where: { schoolId, status: "ACTIVE" },
    include: { classroom: { select: { id: true, name: true } } },
  });
  const clsByStudent = new Map<string, { id: string; name: string }>();
  for (const e of enrollments) if (!clsByStudent.has(e.studentId)) clsByStudent.set(e.studentId, { id: e.classroomId, name: e.classroom.name });

  type Group = { classroom: string; classroomId: string; students: DebtRow[]; totalCents: number };
  const group = (rows: DebtRow[]): Map<string, Group> => {
    const groups = new Map<string, Group>();
    for (const r of rows) {
      if (r.amountCents <= 0) continue;
      const cls = clsByStudent.get(r.studentId);
      const key = cls?.id ?? "unassigned";
      const g = groups.get(key) ?? { classroom: cls?.name ?? "Unassigned", classroomId: key, students: [], totalCents: 0 };
      g.students.push(r);
      g.totalCents += r.amountCents;
      groups.set(key, g);
    }
    return groups;
  };

  if (action === "cards") {
    const [invoices, services, classrooms] = await Promise.all([
      db.invoice.findMany({
        where: { schoolId, writtenOff: false, status: { in: ["UNPAID", "PARTIAL", "OVERDUE"] } },
        select: { studentId: true, totalCents: true, discountCents: true, paidCents: true },
      }),
      db.dailyService.findMany({ where: { schoolId }, select: { id: true, name: true, category: true, active: true }, orderBy: { name: "asc" } }),
      db.classroom.findMany({ where: { schoolId }, select: { id: true, name: true } }),
    ]);

    const svcOwed = new Map<string, { cents: number; count: number }>();
    // distinct learners per service (a learner can carry many OWED day-rows) —
    // CI3 counted students, not charge rows
    const svcDistinct = await db.dailyCharge.groupBy({ by: ["serviceId", "studentId"], where: { schoolId, status: "OWED" }, _sum: { amountCents: true } });
    for (const r of svcDistinct) {
      const e = svcOwed.get(r.serviceId) ?? { cents: 0, count: 0 };
      e.cents += r._sum.amountCents ?? 0;
      e.count += 1;
      svcOwed.set(r.serviceId, e);
    }
    const invByStudent = new Map<string, number>();
    for (const i of invoices) {
      const owe = i.totalCents - i.discountCents - i.paidCents;
      if (owe <= 0) continue;
      invByStudent.set(i.studentId, (invByStudent.get(i.studentId) ?? 0) + owe);
    }

    const cards = [
      { key: "invoices", kind: "INVOICES" as const, serviceId: null as string | null, label: "Billed invoices", category: "INVOICE", arrearsCents: [...invByStudent.values()].reduce((a, b) => a + b, 0), studentCount: invByStudent.size },
      ...services
        .filter((s) => (svcOwed.get(s.id)?.cents ?? 0) > 0)
        .map((s) => ({ key: `service:${s.id}`, kind: "SERVICE" as const, serviceId: s.id, label: s.name, category: s.category, arrearsCents: svcOwed.get(s.id)!.cents, studentCount: svcOwed.get(s.id)!.count })),
    ];

    return NextResponse.json({
      cards,
      classrooms: sortClassrooms(classrooms).map((c) => ({ id: c.id, name: c.name })),
    });
  }

  // ── action=list ──
  const type = q("type") ?? "invoices";
  const classroomFilter = q("classroom");
  let label = "Billed invoices";
  let category = "INVOICE";
  let groups: Map<string, Group>;

  if (type === "invoices") {
    const invoices = await db.invoice.findMany({
      where: { schoolId, writtenOff: false, status: { in: ["UNPAID", "PARTIAL", "OVERDUE"] } },
      select: { studentId: true, totalCents: true, discountCents: true, paidCents: true },
    });
    // merge per-student before grouping (one row per learner, like CI3 GROUP BY student)
    const byStudent = new Map<string, number>();
    for (const i of invoices) {
      const owe = i.totalCents - i.discountCents - i.paidCents;
      if (owe > 0) byStudent.set(i.studentId, (byStudent.get(i.studentId) ?? 0) + owe);
    }
    groups = group([...byStudent.entries()].map(([studentId, amountCents]) => ({ studentId, amountCents })));
  } else if (type.startsWith("service:")) {
    const serviceId = type.slice("service:".length);
    const svc = await db.dailyService.findUnique({ where: { id: serviceId }, select: { name: true, category: true, schoolId: true } });
    if (!svc || svc.schoolId !== schoolId) return NextResponse.json({ error: "Unknown service" }, { status: 404 });
    label = svc.name;
    category = svc.category;
    const charges = await db.dailyCharge.groupBy({ by: ["studentId"], where: { schoolId, serviceId, status: "OWED" }, _sum: { amountCents: true } });
    groups = group(charges.map((c) => ({ studentId: c.studentId, amountCents: c._sum.amountCents ?? 0 })));
  } else {
    return NextResponse.json({ error: "type must be invoices or service:{id}" }, { status: 400 });
  }

  // attach student names; classroom filter applied after grouping so it also
  // matches the learner's current placement
  const studentIds = [...new Set([...groups.values()].flatMap((g) => g.students.map((s) => s.studentId)))];
  const students = await db.student.findMany({ where: { id: { in: studentIds } }, select: { id: true, firstName: true, lastName: true, otherNames: true, nyansapoIndex: true } });
  const nameOf = new Map(students.map((s) => [s.id, [s.firstName, s.lastName, s.otherNames].filter(Boolean).join(" ")]));
  const idxOf = new Map(students.map((s) => [s.id, s.nyansapoIndex]));

  const out = [...groups.values()]
    .filter((g) => !classroomFilter || g.classroomId === classroomFilter)
    .map((g) => ({
      classroom: g.classroom,
      classroomId: g.classroomId,
      students: g.students.map((s) => ({ studentId: s.studentId, name: nameOf.get(s.studentId) ?? "—", indexNo: idxOf.get(s.studentId) ?? null, amountCents: s.amountCents })).sort((a, b) => b.amountCents - a.amountCents),
      studentCount: g.students.length,
      totalCents: g.totalCents,
    }))
    .sort((a, b) => a.classroom.localeCompare(b.classroom));

  return NextResponse.json({
    type,
    label,
    category,
    groups: out,
    grandTotalCents: out.reduce((a, g) => a + g.totalCents, 0),
    studentCount: out.reduce((a, g) => a + g.studentCount, 0),
    classroomFilter: classroomFilter ?? null,
  });
}

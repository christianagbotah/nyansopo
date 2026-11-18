import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { audit } from "@/lib/audit";
import { sortClassrooms } from "@/lib/classes";

// ── RECEIVABLES (iEduPro CI3 `admin/get_aging_report` + `print_payment_reminders`
//    + `drivelist_reminder_templates` parity) — wave 38
//
//    The money the school is owed, seen two ways:
//
//    GET ?action=aging       — Accounts-receivable aging: every invoice with a
//                              balance splits into four age buckets by the days
//                              since it was raised (Current 0-30 / 31-60 / 61-90
//                              / Over 90), with the per-invoice breakdown table.
//                              Written-offs are excluded — they left the book.
//    GET ?action=templates   — reminder letter templates (placeholder library
//                              mirrors the legacy {{…}} tokens exactly)
//    GET ?action=recipients  — per-learner debtors for the letter run (total
//                              owing across invoices, guardian + class), with
//                              optional classroom filter
//
//    POST (fees.manage): template-save / template-delete / template-set-default

type Bucket = { key: string; label: string; range: string; amountCents: number; invoices: number; learners: number };
const bucketOf = (days: number) => (days <= 30 ? 0 : days <= 60 ? 1 : days <= 90 ? 2 : 3);
const BUCKET_LABELS = ["Current (0–30 days)", "31–60 days", "61–90 days", "Over 90 days"];
const BUCKET_RANGES = ["0–30", "31–60", "61–90", "90+"];

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["fees.view", "cashier.reports", "daily.collect"] });
  if (!auth.ok) return auth.res;
  const url = new URL(req.url);
  const action = url.searchParams.get("action") ?? "aging";

  if (action === "templates") {
    // first visit on a fresh install: seed the standard letter (legacy shipped a default row)
    if ((await db.reminderTemplate.count({ where: { schoolId } })) === 0) {
      await db.reminderTemplate.create({
        data: { schoolId, name: "Standard fee reminder", content: DEFAULT_TEMPLATE, isDefault: true, createdByName: "System" },
      });
    }
    const templates = await db.reminderTemplate.findMany({
      where: { schoolId },
      orderBy: [{ isDefault: "desc" }, { updatedAt: "desc" }],
      select: { id: true, name: true, content: true, isDefault: true, createdByName: true, updatedAt: true },
    });
    return NextResponse.json({ templates });
  }

  if (action === "recipients") {
    const classroom = url.searchParams.get("classroom") ?? "all";
    const [invoices, enrollments] = await Promise.all([
      db.invoice.findMany({
        where: { schoolId, writtenOff: false, status: { in: ["UNPAID", "PARTIAL", "OVERDUE"] } },
        select: { studentId: true, totalCents: true, discountCents: true, paidCents: true, createdAt: true },
      }),
      db.enrollment.findMany({
        where: { schoolId, status: "ACTIVE" },
        select: { studentId: true, classroom: { select: { id: true, name: true } } },
        orderBy: { rollNo: "asc" },
      }),
    ]);
    const clsByStudent = new Map<string, { id: string; name: string }>();
    for (const e of enrollments) if (!clsByStudent.has(e.studentId)) clsByStudent.set(e.studentId, e.classroom);

    const byStudent = new Map<string, { owingCents: number; oldest: Date }>();
    for (const i of invoices) {
      const owe = i.totalCents - i.discountCents - i.paidCents;
      if (owe <= 0) continue;
      const cur = byStudent.get(i.studentId) ?? { owingCents: 0, oldest: i.createdAt };
      cur.owingCents += owe;
      if (i.createdAt < cur.oldest) cur.oldest = i.createdAt;
      byStudent.set(i.studentId, cur);
    }

    const ids = [...byStudent.keys()];
    const students = ids.length
      ? await db.student.findMany({
          where: { id: { in: ids }, status: "ACTIVE" },
          select: { id: true, firstName: true, lastName: true, otherNames: true, nyansapoIndex: true, guardianName: true, guardianPhone: true },
        })
      : [];
    const rows = students
      .map((s) => {
        const debt = byStudent.get(s.id)!;
        const cls = clsByStudent.get(s.id);
        return {
          studentId: s.id,
          name: [s.lastName, s.firstName, s.otherNames].filter(Boolean).join(" "),
          indexNo: s.nyansapoIndex,
          classroom: cls?.name ?? "Unassigned",
          classroomId: cls?.id ?? null,
          guardianName: s.guardianName,
          guardianPhone: s.guardianPhone,
          owingCents: debt.owingCents,
          oldestDays: Math.max(0, Math.floor((Date.now() - debt.oldest.getTime()) / 86_400_000)),
        };
      })
      .filter((r) => classroom === "all" || r.classroomId === classroom)
      .sort((a, b) => b.owingCents - a.owingCents);

    return NextResponse.json({
      rows,
      totalCents: rows.reduce((a, r) => a + r.owingCents, 0),
      classrooms: sortClassrooms(
        [...new Map([...clsByStudent.values()].map((c) => [c.id, c])).values()],
      ).map((c) => ({ id: c.id, name: c.name })),
    });
  }

  // ── action=aging (default) ──
  const invoices = await db.invoice.findMany({
    where: { schoolId, writtenOff: false, status: { in: ["UNPAID", "PARTIAL", "OVERDUE"] } },
    select: {
      id: true, invoiceNo: true, studentId: true, totalCents: true, discountCents: true, paidCents: true,
      status: true, createdAt: true, classroomName: true,
      student: { select: { firstName: true, lastName: true, otherNames: true } },
    },
  });

  const now = Date.now();
  const buckets: Bucket[] = BUCKET_LABELS.map((label, i) => ({ key: `b${i}`, label, range: BUCKET_RANGES[i], amountCents: 0, invoices: 0, learners: 0 }));
  const learnerBuckets = new Map<number, Set<string>>();
  const rows = invoices
    .map((i) => {
      const balance = i.totalCents - i.discountCents - i.paidCents;
      const days = Math.max(0, Math.floor((now - i.createdAt.getTime()) / 86_400_000));
      const b = bucketOf(days);
      return { i, balance, days, b };
    })
    .filter((r) => r.balance > 0)
    .sort((a, b) => b.days - a.days || b.balance - a.balance);

  for (const r of rows) {
    buckets[r.b].amountCents += r.balance;
    buckets[r.b].invoices += 1;
    const set = learnerBuckets.get(r.b) ?? new Set<string>();
    set.add(r.i.studentId);
    learnerBuckets.set(r.b, set);
  }
  for (let i = 0; i < buckets.length; i++) buckets[i].learners = learnerBuckets.get(i)?.size ?? 0;

  return NextResponse.json({
    buckets,
    rows: rows.map(({ i, balance, days, b }) => ({
      invoiceNo: i.invoiceNo,
      studentId: i.studentId,
      student: [i.student.lastName, i.student.firstName, i.student.otherNames].filter(Boolean).join(" "),
      classroom: i.classroomName,
      raisedAt: i.createdAt.toISOString(),
      days,
      bucket: b,
      bucketLabel: BUCKET_LABELS[b],
      balanceCents: balance,
      status: i.status,
    })),
    totals: {
      owingCents: buckets.reduce((a, b) => a + b.amountCents, 0),
      invoices: rows.length,
      learners: new Set(rows.map((r) => r.i.studentId)).size,
    },
  });
}

const DEFAULT_TEMPLATE = `Dear {{parent_name}},

RE: OUTSTANDING FEES — {{student}} ({{student_id}}, {{class_name}})

Our records show an outstanding balance of {{amount_owe}} on your ward's fees as at {{current_date}}. Kindly settle on or before {{expected_date}} so teaching and learning are not interrupted.

Payment may be made at the school accounts office, by MoMo, or through the payment link already shared with you. Please quote the learner's index number when paying.

Thank you for your continued support.

Yours faithfully,
Management
{{school_name}}`;

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["fees.manage"] });
  if (!auth.ok) return auth.res;
  const body = await req.json().catch(() => null) as any;
  if (!body?.action) return NextResponse.json({ error: "Missing action" }, { status: 400 });
  const actor = { id: auth.user.id, fullName: auth.user.fullName, role: auth.user.role };

  const ensureDefault = async () => {
    const count = await db.reminderTemplate.count({ where: { schoolId } });
    if (count === 0) {
      await db.reminderTemplate.create({
        data: { schoolId, name: "Standard fee reminder", content: DEFAULT_TEMPLATE, isDefault: true, createdByName: actor.fullName },
      });
    }
  };

  if (body.action === "template-save") {
    const name = String(body.name ?? "").trim();
    const content = String(body.content ?? "").trim();
    if (!name || !content) return NextResponse.json({ error: "Template name and content are required" }, { status: 400 });
    await ensureDefault();
    const setDefault = Boolean(body.isDefault);
    if (setDefault) await db.reminderTemplate.updateMany({ where: { schoolId }, data: { isDefault: false } });
    const data = { name, content, isDefault: setDefault };
    const tpl = body.id
      ? await db.reminderTemplate.update({ where: { id: body.id }, data }).catch(() => null)
      : await db.reminderTemplate.create({ data: { ...data, schoolId, createdByName: actor.fullName } });
    if (!tpl) return NextResponse.json({ error: "Template not found" }, { status: 404 });
    if (!setDefault) {
      const anyDefault = await db.reminderTemplate.findFirst({ where: { schoolId, isDefault: true } });
      if (!anyDefault) await db.reminderTemplate.update({ where: { id: tpl.id }, data: { isDefault: true } });
    }
    await audit({
      schoolId, actor, action: body.id ? "UPDATE" : "CREATE", entityType: "reminder_template", entityId: tpl.id,
      entityLabel: name, summary: `${body.id ? "Updated" : "Created"} reminder letter template "${name}"${setDefault ? " (set as default)" : ""}`,
      req,
    });
    return NextResponse.json({ template: tpl });
  }

  if (body.action === "template-delete") {
    const tpl = await db.reminderTemplate.findFirst({ where: { id: String(body.id ?? ""), schoolId } });
    if (!tpl) return NextResponse.json({ error: "Template not found" }, { status: 404 });
    await db.reminderTemplate.delete({ where: { id: tpl.id } });
    if (tpl.isDefault) {
      const next = await db.reminderTemplate.findFirst({ where: { schoolId }, orderBy: { updatedAt: "desc" } });
      if (next) await db.reminderTemplate.update({ where: { id: next.id }, data: { isDefault: true } });
    }
    await audit({
      schoolId, actor, action: "DELETE", entityType: "reminder_template", entityId: tpl.id,
      entityLabel: tpl.name, summary: `Deleted reminder letter template "${tpl.name}"`, before: { name: tpl.name }, req,
    });
    return NextResponse.json({ deleted: true });
  }

  if (body.action === "template-set-default") {
    const tpl = await db.reminderTemplate.findFirst({ where: { id: String(body.id ?? ""), schoolId } });
    if (!tpl) return NextResponse.json({ error: "Template not found" }, { status: 404 });
    await db.reminderTemplate.updateMany({ where: { schoolId }, data: { isDefault: false } });
    await db.reminderTemplate.update({ where: { id: tpl.id }, data: { isDefault: true } });
    await audit({
      schoolId, actor, action: "UPDATE", entityType: "reminder_template", entityId: tpl.id,
      entityLabel: tpl.name, summary: `Set "${tpl.name}" as the default reminder letter template`, req,
    });
    return NextResponse.json({ ok: true });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

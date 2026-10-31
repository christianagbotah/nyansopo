import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

// ── GET /api/school/[id]/ops/discounts — catalog + applications ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET");
  if (!auth.ok) return auth.res;

  const discounts = await db.discount.findMany({
    where: { schoolId },
    orderBy: { name: "asc" },
    include: { _count: { select: { applications: { where: { status: "ACTIVE" } } } } },
  });
  const applications = await db.discountApplication.findMany({
    where: { schoolId },
    orderBy: { createdAt: "desc" },
    include: { discount: true, student: true },
  });
  const services = await db.dailyService.findMany({ where: { schoolId }, orderBy: { name: "asc" } });

  return NextResponse.json({
    discounts: discounts.map((d) => ({
      id: d.id, name: d.name, type: d.type, value: d.value, scope: d.scope,
      autoApply: d.autoApply, active: d.active, notes: d.notes,
      applied: d._count.applications,
    })),
    applications: applications.map((a) => ({
      id: a.id, discountId: a.discountId, discountName: a.discount.name, type: a.discount.type,
      value: a.valueOverride ?? a.discount.value, scope: a.discount.scope,
      serviceId: a.serviceId, student: `${a.student.firstName} ${a.student.lastName}`,
      studentId: a.studentId, guardianPhone: a.student.guardianPhone,
      status: a.status, reason: a.reason, appliedByName: a.appliedByName, createdAt: a.createdAt,
    })),
    services: services.map((s) => ({ id: s.id, name: s.name, price: s.priceCents })),
  });
}

// ── POST /api/school/[id]/ops/discounts ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST");
  if (!auth.ok) return auth.res;
  const user = auth.user;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;

  if (action === "save-discount") {
    const { id, name, type, value, scope, autoApply, notes } = body;
    if (!name || !Number(value)) return NextResponse.json({ error: "Name and value are required" }, { status: 400 });
    if (type === "PERCENT" && (Number(value) <= 0 || Number(value) > 100)) return NextResponse.json({ error: "Percent must be 1–100" }, { status: 400 });
    const data = { name, type: type ?? "PERCENT", value: Number(value), scope: scope ?? "INVOICE", autoApply: !!autoApply, notes: notes ?? null };
    const d = id ? await db.discount.update({ where: { id }, data }) : await db.discount.create({ data: { ...data, schoolId } });
    return NextResponse.json({ discount: d });
  }

  if (action === "toggle-discount") {
    const d = await db.discount.findUnique({ where: { id: body.id } });
    if (!d || d.schoolId !== schoolId) return NextResponse.json({ error: "Discount not found" }, { status: 404 });
    const updated = await db.discount.update({ where: { id: body.id }, data: { active: !d.active } });
    return NextResponse.json({ discount: updated });
  }

  if (action === "apply") {
    const { discountId, studentId, serviceId, reason, valueOverride } = body;
    const d = await db.discount.findUnique({ where: { id: discountId } });
    if (!d || d.schoolId !== schoolId) return NextResponse.json({ error: "Discount not found" }, { status: 404 });
    if (d.scope === "DAILY_SERVICE" && !serviceId) return NextResponse.json({ error: "Choose the service this subsidy applies to" }, { status: 400 });
    const app = await db.discountApplication.create({
      data: { discountId, studentId, schoolId, serviceId: serviceId ?? null, valueOverride: valueOverride ? Number(valueOverride) : null, reason: reason ?? null, appliedByName: user.fullName },
    });
    // DAILY_SERVICE applications immediately set the subsidised rate on the learner's enrolment
    if (d.scope === "DAILY_SERVICE" && serviceId) {
      const pct = (app.valueOverride ?? d.value) / 100;
      const svc = await db.dailyService.findUnique({ where: { id: serviceId } });
      if (svc) {
        const override = Math.round(svc.priceCents * (1 - pct));
        await db.dailyServiceEnrollment.upsert({
          where: { serviceId_studentId: { serviceId, studentId } },
          update: { active: true, priceCentsOverride: override },
          create: { serviceId, studentId, priceCentsOverride: override },
        });
      }
    }
    return NextResponse.json({ application: app });
  }

  if (action === "revoke") {
    const app = await db.discountApplication.findUnique({ where: { id: body.applicationId }, include: { discount: true } });
    if (!app || app.schoolId !== schoolId) return NextResponse.json({ error: "Application not found" }, { status: 404 });
    await db.discountApplication.update({ where: { id: app.id }, data: { status: "REVOKED" } });
    // restore standard daily rate if this was a service subsidy
    if (app.discount.scope === "DAILY_SERVICE" && app.serviceId) {
      const en = await db.dailyServiceEnrollment.findUnique({ where: { serviceId_studentId: { serviceId: app.serviceId, studentId: app.studentId } } });
      if (en) {
        const others = await db.discountApplication.count({ where: { studentId: app.studentId, serviceId: app.serviceId, status: "ACTIVE" } });
        if (others === 0) await db.dailyServiceEnrollment.update({ where: { id: en.id }, data: { priceCentsOverride: null } });
      }
    }
    return NextResponse.json({ ok: true });
  }

  // apply a discount to a single existing invoice (ad-hoc or from the catalog)
  if (action === "apply-to-invoice") {
    const { invoiceId, discountId, type, value, reason } = body;
    const inv = await db.invoice.findUnique({ where: { id: invoiceId } });
    if (!inv || inv.schoolId !== schoolId) return NextResponse.json({ error: "Invoice not found" }, { status: 404 });
    let dType = type as string | undefined, dValue = Number(value), name = reason || "Discount", notes: string | null = null;
    if (discountId) {
      const d = await db.discount.findUnique({ where: { id: discountId } });
      if (!d || d.schoolId !== schoolId) return NextResponse.json({ error: "Discount not found" }, { status: 404 });
      dType = d.type; dValue = d.value; name = d.name; notes = d.notes;
    }
    if (!dType || !dValue) return NextResponse.json({ error: "Discount type and value required" }, { status: 400 });
    const gross = inv.totalCents;
    const amount = dType === "PERCENT" ? Math.round((gross * dValue) / 100) : Math.min(dValue, gross);
    const newDiscount = Math.min(gross, (inv.discountCents || 0) + amount);
    const balance = gross - newDiscount;
    const status = inv.paidCents >= balance ? "PAID" : inv.paidCents > 0 ? "PARTIAL" : "UNPAID";
    const updated = await db.invoice.update({ where: { id: invoiceId }, data: { discountCents: newDiscount, discountReason: name, status } });
    return NextResponse.json({ invoice: { id: updated.id, discount: updated.discountCents, discountReason: updated.discountReason, status: updated.status } });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

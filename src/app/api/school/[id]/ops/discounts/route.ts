import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { applyDailySubsidy, removeDailySubsidy, effectiveValue } from "@/lib/discounts";

// ── GET /api/school/[id]/ops/discounts — catalog + profiles + applications ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["discounts.manage"] });
  if (!auth.ok) return auth.res;

  const [discounts, profiles, applications, services, classrooms] = await Promise.all([
    db.discount.findMany({
      where: { schoolId },
      orderBy: { name: "asc" },
      include: { _count: { select: { applications: { where: { status: "ACTIVE" } } } } },
    }),
    db.discountProfile.findMany({
      where: { schoolId },
      orderBy: { createdAt: "desc" },
      include: { rules: true, _count: { select: { applications: { where: { status: "ACTIVE" } } } } },
    }),
    db.discountApplication.findMany({
      where: { schoolId },
      orderBy: { createdAt: "desc" },
      include: { discount: true, profile: { include: { rules: true } }, student: { include: { enrollments: { where: { status: "ACTIVE" }, include: { classroom: true }, take: 1 } } } },
    }),
    db.dailyService.findMany({ where: { schoolId }, orderBy: { name: "asc" } }),
    db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" }, select: { id: true, name: true } }),
  ]);

  return NextResponse.json({
    discounts: discounts.map((d) => ({
      id: d.id, name: d.name, type: d.type, value: d.value, scope: d.scope,
      autoApply: d.autoApply, active: d.active, notes: d.notes,
      applied: d._count.applications,
    })),
    profiles: profiles.map((p) => ({
      id: p.id, name: p.name, category: p.category, method: p.method, value: p.value,
      feeItemName: p.feeItemName, description: p.description, active: p.active, applied: p._count.applications,
      rules: p.rules.map((r) => ({ id: r.id, classroomName: r.classroomName, feeItemName: r.feeItemName, value: r.value })),
    })),
    applications: applications.map((a) => ({
      id: a.id, discountId: a.discountId, profileId: a.profileId,
      discountName: a.discount?.name ?? a.profile?.name ?? "Discount",
      type: a.discount?.type ?? a.profile?.method ?? "PERCENT",
      value: a.valueOverride ?? a.discount?.value ?? a.profile?.value ?? 0,
      scope: a.discount?.scope ?? (a.profile?.category === "DAILY_SERVICE" ? "DAILY_SERVICE" : "TUITION"),
      serviceId: a.serviceId, student: `${a.student.firstName} ${a.student.lastName}`,
      studentIndex: a.student.nyansapoIndex, classroom: a.student.enrollments[0]?.classroom?.name ?? null,
      studentId: a.studentId, guardianPhone: a.student.guardianPhone,
      status: a.status, reason: a.reason, appliedByName: a.appliedByName,
      approvedByName: a.approvedByName, decidedAt: a.decidedAt, createdAt: a.createdAt,
    })),
    counts: {
      pending: applications.filter((a) => a.status === "PENDING").length,
      approved: applications.filter((a) => a.status === "ACTIVE" || a.status === "APPROVED").length,
      rejected: applications.filter((a) => a.status === "REJECTED").length,
    },
    services: services.map((s) => ({ id: s.id, name: s.name, price: s.priceCents })),
    classrooms: classrooms.map((c) => ({ id: c.id, name: c.name })),
  });
}

// ── POST /api/school/[id]/ops/discounts ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["discounts.manage"] });
  if (!auth.ok) return auth.res;
  const user = auth.user;

  // ── profile CRUD (iedupro "Discount Profiles") ──────────────
  if (action === "save-profile") {
    const { id, name, category, method, value, feeItemName, description, active } = body;
    if (!name?.trim() || !Number(value)) return NextResponse.json({ error: "Profile name and value are required" }, { status: 400 });
    if (method === "PERCENT" && (Number(value) <= 0 || Number(value) > 100)) return NextResponse.json({ error: "Percent must be 1–100" }, { status: 400 });
    const clash = await db.discountProfile.findFirst({ where: { schoolId, name: name.trim(), ...(id ? { id: { not: id } } : {}) } });
    if (clash) return NextResponse.json({ error: "A profile with this name already exists" }, { status: 400 });
    const data = {
      name: String(name).trim(), category: category ?? "INVOICE", method: method ?? "PERCENT",
      value: Number(value), feeItemName: feeItemName?.trim() || "*",
      description: description?.trim() || null, active: active ?? true,
    };
    const p = id ? await db.discountProfile.update({ where: { id }, data }) : await db.discountProfile.create({ data: { ...data, schoolId } });
    return NextResponse.json({ profile: p });
  }

  if (action === "toggle-profile") {
    const p = await db.discountProfile.findUnique({ where: { id: body.id } });
    if (!p || p.schoolId !== schoolId) return NextResponse.json({ error: "Profile not found" }, { status: 404 });
    const updated = await db.discountProfile.update({ where: { id: body.id }, data: { active: !p.active } });
    return NextResponse.json({ profile: updated });
  }

  if (action === "delete-profile") {
    const p = await db.discountProfile.findUnique({ where: { id: body.id }, include: { _count: { select: { applications: true } } } });
    if (!p || p.schoolId !== schoolId) return NextResponse.json({ error: "Profile not found" }, { status: 404 });
    if (p._count.applications > 0) return NextResponse.json({ error: "Profile has applications — deactivate it instead" }, { status: 400 });
    await db.discountRule.deleteMany({ where: { profileId: p.id } });
    await db.discountProfile.delete({ where: { id: p.id } });
    return NextResponse.json({ ok: true });
  }

  if (action === "save-rule") {
    const { profileId, id, classroomName, feeItemName, value } = body;
    const p = await db.discountProfile.findUnique({ where: { id: profileId } });
    if (!p || p.schoolId !== schoolId) return NextResponse.json({ error: "Profile not found" }, { status: 404 });
    if (!Number(value)) return NextResponse.json({ error: "Rule value is required" }, { status: 400 });
    const data = { classroomName: classroomName?.trim() || null, feeItemName: feeItemName?.trim() || null, value: Number(value) };
    const r = id ? await db.discountRule.update({ where: { id }, data }) : await db.discountRule.create({ data: { ...data, profileId } });
    return NextResponse.json({ rule: r });
  }

  if (action === "delete-rule") {
    const r = await db.discountRule.findUnique({ where: { id: body.id }, include: { profile: true } });
    if (!r || r.profile.schoolId !== schoolId) return NextResponse.json({ error: "Rule not found" }, { status: 404 });
    await db.discountRule.delete({ where: { id: r.id } });
    return NextResponse.json({ ok: true });
  }

  // ── catalog discounts ───────────────────────────────────────
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

  // ── apply discount or profile to a learner (iedupro "Apply Discount") ──
  // approveNow (default true for school staff) activates immediately with an audit trail;
  // otherwise the assignment waits in the approvals queue.
  if (action === "apply") {
    const { discountId, profileId, studentId, serviceId, reason, valueOverride, approveNow } = body;
    if (!studentId) return NextResponse.json({ error: "Choose a learner" }, { status: 400 });
    const student = await db.student.findUnique({ where: { id: studentId } });
    if (!student) return NextResponse.json({ error: "Learner not found" }, { status: 404 });

    let scope = "INVOICE", type = "PERCENT", baseValue = 0;
    if (discountId) {
      const d = await db.discount.findUnique({ where: { id: discountId } });
      if (!d || d.schoolId !== schoolId) return NextResponse.json({ error: "Discount not found" }, { status: 404 });
      if (d.scope === "DAILY_SERVICE" && !serviceId) return NextResponse.json({ error: "Choose the service this subsidy applies to" }, { status: 400 });
      scope = d.scope; type = d.type; baseValue = d.value;
    } else if (profileId) {
      const p = await db.discountProfile.findUnique({ where: { id: profileId } });
      if (!p || p.schoolId !== schoolId) return NextResponse.json({ error: "Profile not found" }, { status: 404 });
      if (p.category === "DAILY_SERVICE" && !serviceId) return NextResponse.json({ error: "Choose the service this subsidy applies to" }, { status: 400 });
      scope = p.category; type = p.method; baseValue = p.value;
    } else return NextResponse.json({ error: "Pick a discount or profile" }, { status: 400 });

    const approved = approveNow !== false; // default: school staff approve their own assignment
    const app = await db.discountApplication.create({
      data: {
        discountId: discountId ?? null, profileId: profileId ?? null, studentId, schoolId,
        serviceId: serviceId ?? null, valueOverride: valueOverride ? Number(valueOverride) : null,
        reason: reason ?? null, appliedByName: user.fullName,
        status: approved ? "ACTIVE" : "PENDING",
        approvedByName: approved ? user.fullName : null, decidedAt: approved ? new Date() : null,
      },
    });
    if (approved && scope === "DAILY_SERVICE" && serviceId) {
      await applyDailySubsidy(serviceId, studentId, type, app.valueOverride ?? baseValue);
    }
    return NextResponse.json({ application: app });
  }

  // ── approvals workflow (iedupro "Discount Approvals") ──────
  if (action === "approve" || action === "reject") {
    const app = await db.discountApplication.findUnique({ where: { id: body.applicationId }, include: { discount: true, profile: true } });
    if (!app || app.schoolId !== schoolId) return NextResponse.json({ error: "Application not found" }, { status: 404 });
    if (app.status !== "PENDING") return NextResponse.json({ error: "Only pending applications can be decided" }, { status: 400 });
    const updated = await db.discountApplication.update({
      where: { id: app.id },
      data: action === "approve"
        ? { status: "ACTIVE", approvedByName: user.fullName, decidedAt: new Date() }
        : { status: "REJECTED", approvedByName: user.fullName, decidedAt: new Date() },
    });
    // on approval, DAILY_SERVICE subsidies take effect on the learner's rate
    if (action === "approve" && (app.discount?.scope ?? app.profile?.category) === "DAILY_SERVICE" && app.serviceId) {
      const type = app.discount?.type ?? app.profile?.method ?? "PERCENT";
      const src = app.discount
        ? { kind: "discount" as const, id: app.discount.id, name: app.discount.name, type: app.discount.type, value: app.discount.value, scope: app.discount.scope }
        : { kind: "profile" as const, id: app.profileId!, name: app.profile!.name, type: app.profile!.method, value: app.profile!.value, category: app.profile!.category };
      const eff = await effectiveValue(src, app.studentId);
      await applyDailySubsidy(app.serviceId, app.studentId, app.valueOverride ? type : eff.type, app.valueOverride ?? eff.value);
    }
    return NextResponse.json({ application: updated });
  }

  if (action === "revoke") {
    const app = await db.discountApplication.findUnique({ where: { id: body.applicationId }, include: { discount: true, profile: true } });
    if (!app || app.schoolId !== schoolId) return NextResponse.json({ error: "Application not found" }, { status: 404 });
    await db.discountApplication.update({ where: { id: app.id }, data: { status: "REVOKED" } });
    if ((app.discount?.scope ?? app.profile?.category) === "DAILY_SERVICE" && app.serviceId) {
      await removeDailySubsidy(app.serviceId, app.studentId);
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

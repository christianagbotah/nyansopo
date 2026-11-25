import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { audit } from "@/lib/audit";

// ── NTC CPD & LICENSURE (wave 58 — Ghana National Teaching Council) ──
// GET  — the licence register (staff + NTC fields + licence status), the
//        CPD activity log, the school's point targets, and a per-staff
//        points summary computed over each teacher's licence cycle.
// POST — save-licence · log-activity · verify · reject · delete-activity ·
//        save-targets
//
// NTC licence renewal requires CPD points across three programme
// categories over the licence cycle (default targets 20 / 40 / 40 over
// 3 years — tuned per school via CpdSetting). Points only count once an
// activity is VERIFIED by the head of school.

const CATEGORIES = ["MANDATORY", "RANK", "RECOMMENDED"] as const;
const num = (v: unknown, d = 0) => (Number.isFinite(Number(v)) ? Number(v) : d);
const DAY = 864e5;

/** Licence status bucket from today's date. */
export function licenceStatus(expiry: Date | null | undefined): "VALID" | "EXPIRING" | "CRITICAL" | "EXPIRED" | "NONE" {
  if (!expiry) return "NONE";
  const days = Math.floor((new Date(expiry).getTime() - Date.now()) / DAY);
  if (days < 0) return "EXPIRED";
  if (days <= 30) return "CRITICAL";
  if (days <= 90) return "EXPIRING";
  return "VALID";
}

/** Parse a yyyy-mm-dd (or ISO) string into a Date, or null. */
function parseDate(v: unknown): Date | null {
  if (typeof v !== "string" || !v.trim()) return null;
  const d = new Date(v.length <= 10 ? `${v}T00:00:00` : v);
  return Number.isNaN(d.getTime()) ? null : d;
}

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["staffcpd.view"] });
  if (!auth.ok) return auth.res;

  const [staff, activities, settingRow] = await Promise.all([
    db.staff.findMany({
      where: { schoolId },
      orderBy: [{ active: "desc" }, { fullName: "asc" }],
      select: {
        id: true, fullName: true, role: true, title: true, category: true, position: true,
        qualification: true, sex: true, staffCode: true, active: true,
        ntcLicenceNo: true, ntcCategory: true, ntcIssued: true, ntcExpiry: true,
      },
    }),
    db.cpdActivity.findMany({
      where: { schoolId },
      orderBy: { activityDate: "desc" },
      include: { staff: { select: { id: true, fullName: true, category: true } } },
    }),
    db.cpdSetting.findUnique({ where: { schoolId } }),
  ]);

  const setting = settingRow ?? { targetMandatory: 20, targetRank: 40, targetRecommended: 40, cycleYears: 3 };

  // per-staff points within their licence cycle (licence dates when present,
  // else the rolling school cycle window)
  const summary = staff.map((s) => {
    const start = s.ntcIssued ? new Date(s.ntcIssued).getTime() : Date.now() - setting.cycleYears * 365 * DAY;
    const end = s.ntcExpiry ? new Date(s.ntcExpiry).getTime() : Date.now();
    const mine = activities.filter(
      (a) => a.staffId === s.id && a.status === "VERIFIED" && new Date(a.activityDate).getTime() >= start && new Date(a.activityDate).getTime() <= end,
    );
    const byCat = { MANDATORY: 0, RANK: 0, RECOMMENDED: 0 } as Record<string, number>;
    for (const a of mine) byCat[a.category] = (byCat[a.category] ?? 0) + a.points;
    return {
      staffId: s.id,
      points: { MANDATORY: byCat.MANDATORY, RANK: byCat.RANK, RECOMMENDED: byCat.RECOMMENDED },
      total: byCat.MANDATORY + byCat.RANK + byCat.RECOMMENDED,
      logged: activities.filter((a) => a.staffId === s.id && a.status === "LOGGED").length,
      cycle: { start: new Date(start).toISOString(), end: new Date(end).toISOString(), usesLicenceDates: !!s.ntcIssued && !!s.ntcExpiry },
    };
  });

  return NextResponse.json({ staff, activities, setting, summary });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["staffcpd.manage"] });
  if (!auth.ok) return auth.res;
  const actor = { id: auth.user!.id, fullName: auth.user!.fullName ?? "CPD officer", role: auth.user!.role };
  const body = await req.json().catch(() => ({}));
  const action = String(body.action ?? "");

  // ── licence record on the staff row ─────────────────────────────
  if (action === "save-licence") {
    const staff = await db.staff.findFirst({ where: { id: String(body.staffId ?? ""), schoolId } });
    if (!staff) return NextResponse.json({ error: "Staff member not found" }, { status: 404 });
    const issued = parseDate(body.ntcIssued);
    const expiry = parseDate(body.ntcExpiry);
    if (issued && expiry && expiry <= issued) return NextResponse.json({ error: "Expiry must be after the issue date" }, { status: 400 });
    const data = {
      ntcLicenceNo: String(body.ntcLicenceNo ?? "").trim() || null,
      ntcCategory: body.ntcCategory ? String(body.ntcCategory) : null,
      ntcIssued: issued,
      ntcExpiry: expiry,
    };
    await db.staff.update({ where: { id: staff.id }, data });
    await audit({
      schoolId, actor, action: "UPDATE", entityType: "Staff", entityId: staff.id, entityLabel: staff.fullName,
      summary: `NTC licence record saved for ${staff.fullName}${data.ntcLicenceNo ? ` — ${data.ntcLicenceNo}` : " (cleared)"}${expiry ? `, expires ${new Date(expiry).toISOString().slice(0, 10)}` : ""}`, req,
    });
    return NextResponse.json({ ok: true });
  }

  // ── CPD activity log ────────────────────────────────────────────
  if (action === "log-activity") {
    const staff = await db.staff.findFirst({ where: { id: String(body.staffId ?? ""), schoolId } });
    if (!staff) return NextResponse.json({ error: "Staff member not found" }, { status: 404 });
    const title = String(body.title ?? "").trim();
    if (!title) return NextResponse.json({ error: "Activity title is required" }, { status: 400 });
    const category = CATEGORIES.includes(body.category) ? body.category : "RECOMMENDED";
    const activityDate = parseDate(body.activityDate) ?? new Date();
    const points = Math.max(0, Math.round(num(body.points) * 10) / 10);
    const row = await db.cpdActivity.create({
      data: {
        schoolId, staffId: staff.id, title, category, activityDate, points,
        provider: body.provider ? String(body.provider).trim() : null,
        hours: body.hours === "" || body.hours == null ? null : Math.max(0, num(body.hours)),
        evidence: body.evidence ? String(body.evidence).trim() : null,
        note: body.note ? String(body.note).trim() : null,
        status: "LOGGED",
      },
    });
    await audit({
      schoolId, actor, action: "CREATE", entityType: "CpdActivity", entityId: row.id, entityLabel: `${staff.fullName} · ${title}`,
      summary: `CPD activity logged: ${title} (${category}, ${points} pts) for ${staff.fullName}`, req,
    });
    return NextResponse.json({ activity: row });
  }

  const activity = body.id
    ? await db.cpdActivity.findFirst({ where: { id: String(body.id), schoolId }, include: { staff: { select: { fullName: true } } } })
    : null;

  if (action === "verify" || action === "reject") {
    if (!activity) return NextResponse.json({ error: "Activity not found" }, { status: 404 });
    const next = action === "verify" ? "VERIFIED" : "REJECTED";
    await db.cpdActivity.update({
      where: { id: activity.id },
      data: { status: next, verifiedByName: actor.fullName, verifiedAt: new Date() },
    });
    await audit({
      schoolId, actor, action: "UPDATE", entityType: "CpdActivity", entityId: activity.id, entityLabel: `${activity.staff.fullName} · ${activity.title}`,
      summary: `CPD activity ${next.toLowerCase()}: ${activity.title} (${activity.points} pts) — ${activity.staff.fullName}`, req,
    });
    return NextResponse.json({ ok: true });
  }

  if (action === "delete-activity") {
    if (!activity) return NextResponse.json({ error: "Activity not found" }, { status: 404 });
    await db.cpdActivity.delete({ where: { id: activity.id } });
    await audit({
      schoolId, actor, action: "DELETE", entityType: "CpdActivity", entityId: activity.id, entityLabel: `${activity.staff.fullName} · ${activity.title}`,
      summary: `CPD activity deleted: ${activity.title} — ${activity.staff.fullName}`, req,
    });
    return NextResponse.json({ ok: true });
  }

  // ── school CPD point targets ────────────────────────────────────
  if (action === "save-targets") {
    const data = {
      targetMandatory: Math.max(0, Math.round(num(body.targetMandatory, 20) * 10) / 10),
      targetRank: Math.max(0, Math.round(num(body.targetRank, 40) * 10) / 10),
      targetRecommended: Math.max(0, Math.round(num(body.targetRecommended, 40) * 10) / 10),
      cycleYears: Math.min(6, Math.max(1, Math.round(num(body.cycleYears, 3)))),
    };
    const row = await db.cpdSetting.upsert({ where: { schoolId }, create: { schoolId, ...data }, update: data });
    await audit({
      schoolId, actor, action: "UPDATE", entityType: "CpdSetting", entityId: row.id, entityLabel: "CPD targets",
      summary: `CPD cycle targets set: ${data.targetMandatory} mandatory / ${data.targetRank} rank / ${data.targetRecommended} recommended over ${data.cycleYears} year(s)`, req,
    });
    return NextResponse.json({ setting: row });
  }

  return NextResponse.json({ error: `Unknown action: ${action}` }, { status: 400 });
}

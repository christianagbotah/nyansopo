import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { audit } from "@/lib/audit";

// ── SYNC CENTER (iEduPro CI3 sync_dashboard / sync_devices / sync_conflicts /
//    sync_conflict_merge / sync_audit_trail / sync_metrics parity) — wave 39
//
//    The legacy system pushed rows between WAMP servers and the cloud with a
//    device registry, a conflict queue and an audit journal. Nyansapo's sync
//    truth is the PWA offline queue (payments / attendance / marks / enrolment),
//    so the Sync Center supervises exactly that pipeline:
//
//    GET ?action=dashboard — devices with last sync + lifetime ops, KPI cards
//                             (today / 7-day synced-failed-conflicts, health
//                             score), 14-day flush timeline, per-kind breakdown,
//                             sync policy (CI3 sync_table_management parity)
//    GET ?action=conflicts — the pending queue + resolved history
//    GET ?action=events    — the sync journal (flush / register / revoke /
//                             resolve rows) with filters
//
//    POST (sync.manage):
//      device-save          — register a device (minted DEV-0001-style code),
//                             or rename / notes edit
//      device-toggle        — ACTIVE ⇄ REVOKED (a revoked device's flushes are
//                             rejected at /api/sync — the kill switch)
//      conflict-resolve     — LOCAL_WINS (replay the frozen queued payload now) /
//                             REMOTE_WINS (discard) / IGNORED, with a note;
//                             every resolution lands in the journal
//      policy-save          — per-kind sync policy stored as SchoolSetting
//                             sync.policy — a disabled kind is refused at flush
//                             time and journaled as FAILED

const HEALTH = (opts: { failures7: number; conflictsPending: number; revoked: number }) => {
  // CI3 sync_health_score: 100 minus 2 per failed op, 10 per pending conflict,
  // 15 per revoked device still attempting flushes — floored at 0, capped at 100.
  const raw = 100 - opts.failures7 * 2 - opts.conflictsPending * 10 - opts.revoked * 15;
  return Math.max(0, Math.min(100, Math.round(raw)));
};

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["sync.view"] });
  if (!auth.ok) return auth.res;
  const url = new URL(req.url);
  const action = url.searchParams.get("action") ?? "dashboard";

  if (action === "conflicts") {
    const rows = await db.syncConflict.findMany({ where: { schoolId }, orderBy: { createdAt: "desc" }, take: 200 });
    // resolve learner names once so the UI can render a human side-by-side
    const studentIds = new Set<string>();
    const parse = (s: string) => { try { return JSON.parse(s) as any; } catch { return {}; } };
    for (const r of rows) {
      const local = parse(r.localState); const remote = parse(r.remoteState);
      for (const m of local.marks ?? []) if (m.studentId) studentIds.add(m.studentId);
      for (const m of remote.marks ?? []) if (m.studentId) studentIds.add(m.studentId);
      for (const rec of local.records ?? []) if (rec.studentId) studentIds.add(rec.studentId);
      for (const rec of remote.records ?? []) if (rec.studentId) studentIds.add(rec.studentId);
    }
    const students = studentIds.size
      ? await db.student.findMany({ where: { id: { in: [...studentIds] } }, select: { id: true, firstName: true, lastName: true, otherNames: true } })
      : [];
    const nameOf = new Map(students.map((s) => [s.id, [s.lastName, s.firstName, s.otherNames].filter(Boolean).join(" ")] as const));
    const enrich = (r: (typeof rows)[number]) => {
      const local = parse(r.localState); const remote = parse(r.remoteState);
      const cells = new Map<string, { name: string; local: string; remote: string }>();
      const put = (sid: string, side: "local" | "remote", value: string) => {
        const cur = cells.get(sid) ?? { name: nameOf.get(sid) ?? sid.slice(-6).toUpperCase(), local: "—", remote: "—" };
        cur[side] = value; cells.set(sid, cur);
      };
      if (r.table === "marks") {
        for (const m of local.marks ?? []) put(m.studentId, "local", m.score === null || m.score === "" ? "—" : `${m.score}`);
        for (const m of remote.marks ?? []) put(m.studentId, "remote", m.score === null || m.score === undefined ? "—" : `${m.score}`);
      } else {
        for (const rec of local.records ?? []) put(rec.studentId, "local", rec.status ?? "—");
        for (const rec of remote.records ?? []) put(rec.studentId, "remote", rec.status ?? "—");
      }
      return { ...r, diffRows: [...cells.values()], remoteTakenBy: remote.takenByName ?? null, remoteSessionId: remote.sessionId ?? null };
    };
    return NextResponse.json({
      pending: rows.filter((r) => r.status === "PENDING").map(enrich),
      resolved: rows.filter((r) => r.status !== "PENDING").map(enrich),
    });
  }

  if (action === "events") {
    const kind = url.searchParams.get("kind") ?? "all";
    const rows = await db.syncEvent.findMany({
      where: { schoolId, ...(kind !== "all" ? { kind } : {}) },
      orderBy: { createdAt: "desc" },
      take: 300,
    });
    return NextResponse.json({ events: rows });
  }

  // dashboard
  const since7 = new Date(Date.now() - 7 * 864e5);
  const since14 = new Date(Date.now() - 14 * 864e5);
  const todayStart = new Date(); todayStart.setHours(0, 0, 0, 0);
  const [devices, conflicts, events, events7, eventsToday, policyRow] = await Promise.all([
    db.syncDevice.findMany({ where: { schoolId }, orderBy: { createdAt: "asc" } }),
    db.syncConflict.findMany({ where: { schoolId }, orderBy: { createdAt: "desc" }, take: 500 }),
    db.syncEvent.findMany({ where: { schoolId, createdAt: { gte: since14 } }, orderBy: { createdAt: "asc" } }),
    db.syncEvent.findMany({ where: { schoolId, createdAt: { gte: since7 } } }),
    db.syncEvent.findMany({ where: { schoolId, createdAt: { gte: todayStart } } }),
    db.schoolSetting.findUnique({ where: { schoolId_key: { schoolId, key: "sync.policy" } } }),
  ]);

  const flushes7 = events7.filter((e) => e.kind === "FLUSH");
  const kpi = {
    todaySynced: eventsToday.reduce((s, e) => s + e.opsSynced, 0),
    todayFailed: eventsToday.reduce((s, e) => s + e.opsFailed, 0),
    synced7: flushes7.reduce((s, e) => s + e.opsSynced, 0),
    failed7: flushes7.reduce((s, e) => s + e.opsFailed, 0),
    conflictsDetected7: events7.reduce((s, e) => s + e.conflicts, 0),
    conflictsPending: conflicts.filter((c) => c.status === "PENDING").length,
    activeDevices: devices.filter((d) => d.status === "ACTIVE").length,
    revoked: devices.filter((d) => d.status === "REVOKED").length,
    lastFlushAt: events.length ? events[events.length - 1].createdAt : null,
    lastFlushUser: events.length ? events[events.length - 1].userName : null,
  };

  // 14-day timeline (CI3 sync_dashboard chart): one point per day
  const byDay = new Map<string, { synced: number; failed: number; conflicts: number; flushes: number }>();
  for (const e of events) {
    if (e.kind !== "FLUSH") continue;
    const key = e.createdAt.toISOString().slice(0, 10);
    const cur = byDay.get(key) ?? { synced: 0, failed: 0, conflicts: 0, flushes: 0 };
    cur.synced += e.opsSynced; cur.failed += e.opsFailed; cur.conflicts += e.conflicts; cur.flushes += 1;
    byDay.set(key, cur);
  }
  const timeline = [...byDay.entries()].map(([date, v]) => ({ date, ...v }));

  const policy = policyRow ? (JSON.parse(policyRow.value) as Record<string, boolean>) : { payment: true, attendance: true, marks: true, student: true };
  const health = HEALTH({ failures7: kpi.failed7, conflictsPending: kpi.conflictsPending, revoked: kpi.revoked });

  return NextResponse.json({
    devices,
    kpi,
    timeline,
    policy,
    health,
    recentConflicts: conflicts.slice(0, 6),
    recentEvents: [...events].reverse().slice(0, 12),
  });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["sync.manage"] });
  if (!auth.ok) return auth.res;
  const body = (await req.json()) as Record<string, any>;
  const action = body.action as string;

  if (action === "device-save") {
    // register (mint a sequential DEV-0001-style code) or rename/notes edit
    if (body.id) {
      const dev = await db.syncDevice.findFirst({ where: { id: body.id, schoolId } });
      if (!dev) return NextResponse.json({ error: "Device not found" }, { status: 404 });
      await db.syncDevice.update({ where: { id: dev.id }, data: { name: String(body.name ?? dev.name), notes: body.notes !== undefined ? String(body.notes) : dev.notes } });
      await audit({ schoolId, actor: { id: auth.user.id, fullName: auth.user.fullName, role: auth.user.role }, action: "UPDATE", entityType: "SyncDevice", entityId: dev.id, entityLabel: `${dev.code} ${body.name ?? dev.name}`, summary: "Sync device renamed", req });
      return NextResponse.json({ ok: true });
    }
    const name = String(body.name ?? "").trim();
    if (!name) return NextResponse.json({ error: "Device name is required" }, { status: 400 });
    const kind = ["POS_TABLET", "SCANNER", "FRONT_DESK", "BROWSER", "OTHER"].includes(body.kind) ? body.kind : "OTHER";
    const count = await db.syncDevice.count({ where: { schoolId } });
    const code = `DEV-${String(count + 1).padStart(4, "0")}`;
    const dev = await db.syncDevice.create({ data: { schoolId, code, name, kind, notes: body.notes ? String(body.notes) : null } });
    await db.syncEvent.create({ data: { schoolId, deviceCode: code, userName: auth.user.fullName, kind: "REGISTER", detail: `${name} (${kind}) registered` } });
    await audit({ schoolId, actor: { id: auth.user.id, fullName: auth.user.fullName, role: auth.user.role }, action: "CREATE", entityType: "SyncDevice", entityId: dev.id, entityLabel: `${code} ${name}`, summary: "Sync device registered", req });
    return NextResponse.json({ ok: true, code });
  }

  if (action === "device-toggle") {
    const dev = await db.syncDevice.findFirst({ where: { id: body.id, schoolId } });
    if (!dev) return NextResponse.json({ error: "Device not found" }, { status: 404 });
    const to = dev.status === "ACTIVE" ? "REVOKED" : "ACTIVE";
    await db.syncDevice.update({ where: { id: dev.id }, data: { status: to } });
    await db.syncEvent.create({ data: { schoolId, deviceCode: dev.code, userName: auth.user.fullName, kind: to === "REVOKED" ? "REVOKE" : "REGISTER", detail: `${dev.code} ${dev.name} ${to === "REVOKED" ? "revoked — flushes now rejected" : "re-activated"}` } });
    await audit({ schoolId, actor: { id: auth.user.id, fullName: auth.user.fullName, role: auth.user.role }, action: to === "REVOKED" ? "REVOKE" : "REINSTATE", entityType: "SyncDevice", entityId: dev.id, entityLabel: `${dev.code} ${dev.name}`, summary: `Sync device ${to.toLowerCase()}`, req });
    return NextResponse.json({ ok: true, status: to });
  }

  if (action === "conflict-resolve") {
    const conflict = await db.syncConflict.findFirst({ where: { id: body.id, schoolId } });
    if (!conflict) return NextResponse.json({ error: "Conflict not found" }, { status: 404 });
    if (conflict.status !== "PENDING") return NextResponse.json({ error: "Conflict already resolved" }, { status: 409 });
    const resolution = String(body.resolution ?? "");
    if (!["LOCAL_WINS", "REMOTE_WINS", "IGNORED"].includes(resolution)) return NextResponse.json({ error: "resolution must be LOCAL_WINS | REMOTE_WINS | IGNORED" }, { status: 400 });

    if (resolution === "LOCAL_WINS") {
      // replay the frozen queued payload (CI3 conflict merge "use local")
      const local = JSON.parse(conflict.localState) as any;
      if (conflict.table === "marks") {
        await db.mark.deleteMany({ where: { assessmentId: local.assessmentId } });
        for (const m of local.marks ?? []) {
          if (m.score === null || m.score === "") continue;
          await db.mark.create({ data: { assessmentId: local.assessmentId, studentId: m.studentId, score: Number(m.score), enteredOffline: true } });
        }
      } else if (conflict.table === "attendance") {
        // overwrite the online session's statuses with the queued ones
        const remote = JSON.parse(conflict.remoteState) as any;
        if (remote.sessionId) {
          for (const r of local.records ?? []) {
            await db.attendanceRecord.updateMany({ where: { sessionId: remote.sessionId, studentId: r.studentId }, data: { status: r.status, excusedReason: r.excusedReason ?? null, markedOffline: true } });
          }
        } else {
          const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
          if (term) {
            const session = await db.attendanceSession.create({ data: { classroomId: local.classroomId, schoolId, termId: term.id, date: new Date(local.date), takenByName: `${conflict.localUser} (SYNC:resolve ${conflict.id.slice(-4).toUpperCase()})` } });
            for (const r of local.records) await db.attendanceRecord.create({ data: { sessionId: session.id, studentId: r.studentId, status: r.status, excusedReason: r.excusedReason ?? null, markedOffline: true } });
          }
        }
      }
    }

    await db.syncConflict.update({ where: { id: conflict.id }, data: { status: resolution === "IGNORED" ? "IGNORED" : "RESOLVED", resolution, resolvedBy: auth.user.fullName, resolvedAt: new Date(), resolveNote: body.note ? String(body.note) : null } });
    await db.syncEvent.create({ data: { schoolId, deviceCode: conflict.deviceCode, userName: auth.user.fullName, kind: "RESOLVE", conflicts: 1, detail: `${conflict.table} · ${conflict.recordKey} → ${resolution.replace("_", " ").toLowerCase()}` } });
    await audit({ schoolId, actor: { id: auth.user.id, fullName: auth.user.fullName, role: auth.user.role }, action: "UPDATE", entityType: "SyncConflict", entityId: conflict.id, entityLabel: conflict.recordKey, summary: `Sync conflict resolved: ${resolution}`, before: { status: "PENDING" }, after: { status: resolution }, req });
    return NextResponse.json({ ok: true });
  }

  if (action === "policy-save") {
    const kinds = ["payment", "attendance", "marks", "student"] as const;
    const policy: Record<string, boolean> = {};
    for (const k of kinds) policy[k] = body.policy?.[k] !== false;
    await db.schoolSetting.upsert({ where: { schoolId_key: { schoolId, key: "sync.policy" } }, create: { schoolId, key: "sync.policy", value: JSON.stringify(policy) }, update: { value: JSON.stringify(policy) } });
    await audit({ schoolId, actor: { id: auth.user.id, fullName: auth.user.fullName, role: auth.user.role }, action: "UPDATE", entityType: "SchoolSetting", entityId: "sync.policy", entityLabel: "Sync policy", summary: `Sync policy saved: ${kinds.filter((k) => policy[k]).join(", ") || "none"} enabled`, req });
    return NextResponse.json({ ok: true, policy });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

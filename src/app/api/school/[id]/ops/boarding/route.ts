import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

// ── Boarding (iEduPro boarding_system parity): houses → dormitories → beds → boarders ──

const HOUSE_STATUSES = ["AVAILABLE", "ASSIGNED", "MAINTENANCE"];
const BED_TYPES = ["SINGLE", "BUNK"];

const fullName = (s: { firstName: string; lastName: string }) => `${s.firstName} ${s.lastName}`.trim();

/** normalise an optional select value: "" / undefined / null → null */
const optStr = (v: unknown): string | null => {
  if (typeof v !== "string") return null;
  const t = v.trim();
  return t.length ? t : null;
};

/** validate + normalise a canonical UPPERCASE enum; returns null when invalid */
const enumVal = (v: unknown, allowed: string[]): string | null => {
  if (typeof v !== "string" || !v.trim()) return null;
  const u = v.trim().toUpperCase();
  return allowed.includes(u) ? u : "…invalid…";
};

const bad = (error: string, status = 400) => NextResponse.json({ error }, { status });

// ── GET /api/school/[id]/ops/boarding — houses, dorms, beds, stats, boarders in one payload ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["boarding.view"] });
  if (!auth.ok) return auth.res;

  const houses = await db.boardingHouse.findMany({
    where: { schoolId },
    include: {
      houseMaster: true,
      prefect: true,
      dormitories: {
        include: { prefect: true, beds: { include: { student: true } } },
        orderBy: { name: "asc" },
      },
    },
    orderBy: { name: "asc" },
  });

  const [boardingEns, occupantBeds, activeEns, teachingStaff] = await Promise.all([
    db.enrollment.findMany({
      where: { schoolId, status: "ACTIVE", residenceType: "BOARDING" },
      include: { student: { include: { bedAssigned: { select: { id: true, code: true } } } }, classroom: true },
    }),
    db.bed.findMany({
      where: { schoolId, studentId: { not: null } },
      include: {
        student: { include: { enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: true } } } },
        dormitory: { include: { house: true } },
      },
      orderBy: { code: "asc" },
    }),
    db.enrollment.findMany({
      where: { schoolId, status: "ACTIVE" },
      include: { student: { include: { bedAssigned: { select: { id: true, code: true } } } }, classroom: true },
      orderBy: [{ student: { lastName: "asc" } }, { student: { firstName: "asc" } }],
      take: 500,
    }),
    db.staff.findMany({
      where: { schoolId, category: "TEACHING", active: true },
      select: { id: true, fullName: true, role: true },
      orderBy: { fullName: "asc" },
    }),
  ]);

  const houseRows = houses.map((h) => {
    const beds = h.dormitories.flatMap((d) => d.beds);
    return {
      id: h.id,
      name: h.name,
      description: h.description,
      houseMasterId: h.houseMasterId,
      houseMasterName: h.houseMaster?.fullName ?? null,
      prefectId: h.prefectId,
      prefectName: h.prefect ? fullName(h.prefect) : null,
      capacity: h.capacity,
      yearEstablished: h.yearEstablished,
      gpsCode: h.gpsCode,
      termlyFeeCents: h.termlyFeeCents,
      status: h.status,
      dormCount: h.dormitories.length,
      bedCount: beds.length,
      occupied: beds.filter((b) => b.studentId).length,
      dormitories: h.dormitories.map((d) => ({
        id: d.id,
        houseId: h.id,
        houseName: h.name,
        name: d.name,
        capacity: d.capacity,
        type: d.type,
        floor: d.floor,
        description: d.description,
        prefectId: d.prefectId,
        prefectName: d.prefect ? fullName(d.prefect) : null,
        status: d.status,
        bedCount: d.beds.length,
        occupied: d.beds.filter((b) => b.studentId).length,
        availableBeds: d.beds.filter((b) => !b.studentId && b.status === "AVAILABLE").length,
      })),
    };
  });

  const dormRows = houseRows.flatMap((h) => h.dormitories);

  const bedsFlat = houses
    .flatMap((h) =>
      h.dormitories.flatMap((d) =>
        d.beds.map((b) => ({
          id: b.id,
          code: b.code,
          dormitoryId: d.id,
          dormName: d.name,
          houseId: h.id,
          houseName: h.name,
          bedType: b.bedType,
          status: b.status,
          studentId: b.studentId,
          studentName: b.student ? fullName(b.student) : null,
          assignedAt: b.assignedAt,
        }))
      )
    )
    .sort((a, b) => a.code.localeCompare(b.code));

  const allBeds = houses.flatMap((h) => h.dormitories.flatMap((d) => d.beds));
  const occupied = allBeds.filter((b) => b.studentId).length;
  const available = allBeds.filter((b) => !b.studentId && b.status === "AVAILABLE").length;

  const unassignedBoarders = boardingEns
    .filter((e) => !e.student.bedAssigned)
    .map((e) => ({
      id: e.studentId,
      name: fullName(e.student),
      classroom: e.classroom?.name ?? null,
      gender: e.student.gender,
    }));

  return NextResponse.json({
    houses: houseRows,
    dorms: dormRows,
    beds: bedsFlat,
    stats: {
      houses: houses.length,
      dorms: dormRows.length,
      beds: allBeds.length,
      occupied,
      available,
      maintenance: allBeds.filter((b) => b.status === "MAINTENANCE").length,
      occupancyRate: allBeds.length ? Math.round((occupied / allBeds.length) * 1000) / 10 : 0,
      boarders: boardingEns.length,
      unassigned: unassignedBoarders.length,
    },
    unassignedBoarders,
    occupants: occupantBeds.map((b) => ({
      bedId: b.id,
      code: b.code,
      dormName: b.dormitory.name,
      houseName: b.dormitory.house.name,
      studentId: b.studentId!,
      studentName: b.student ? fullName(b.student) : null,
      classroom: b.student?.enrollments[0]?.classroom?.name ?? null,
      assignedAt: b.assignedAt,
    })),
    activeLearners: activeEns.map((e) => ({
      id: e.studentId,
      name: fullName(e.student),
      classroom: e.classroom?.name ?? null,
      gender: e.student.gender,
      residenceType: e.residenceType,
      bedCode: e.student.bedAssigned?.code ?? null,
    })),
    teachingStaff,
  });
}

// ── shared validators ─────────────────────────────────────────
async function checkHouseMaster(schoolId: string, staffId: string): Promise<string | null> {
  const st = await db.staff.findUnique({ where: { id: staffId } });
  if (!st || st.schoolId !== schoolId) return "House master not found at this school";
  if (st.category !== "TEACHING") return "House master must be a teaching staff member";
  return null;
}

async function checkLearner(schoolId: string, studentId: string): Promise<string | null> {
  const en = await db.enrollment.findFirst({ where: { studentId, schoolId, status: "ACTIVE" } });
  return en ? null : "Learner is not actively enrolled at this school";
}

// ── POST /api/school/[id]/ops/boarding — CRUD + assignment actions ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["boarding.manage"] });
  if (!auth.ok) return auth.res;

  // ── HOUSES ──
  if (action === "house.create" || action === "house.update") {
    const { id, name, description, houseMasterId, prefectId, capacity, yearEstablished, gpsCode, termlyFeeCents, status } = body;
    if (!name?.trim()) return bad("House name is required");
    if (action === "house.update" && !id) return bad("House id is required");

    const masterId = optStr(houseMasterId);
    if (masterId) {
      const err = await checkHouseMaster(schoolId, masterId);
      if (err) return bad(err);
    }
    const prefId = optStr(prefectId);
    if (prefId) {
      const err = await checkLearner(schoolId, prefId);
      if (err) return bad(err);
    }
    let st = "AVAILABLE";
    if (status != null && `${status}`.trim() !== "") {
      const v = enumVal(status, HOUSE_STATUSES);
      if (!v || v === "…invalid…") return bad("Status must be AVAILABLE, ASSIGNED or MAINTENANCE");
      st = v;
    }

    const data = {
      name: name.trim(),
      description: optStr(description),
      houseMasterId: masterId,
      prefectId: prefId,
      capacity: Math.max(0, Math.round(Number(capacity) || 0)),
      yearEstablished: optStr(yearEstablished),
      gpsCode: optStr(gpsCode),
      termlyFeeCents: Math.max(0, Math.round(Number(termlyFeeCents) || 0)),
      status: st,
    };

    if (action === "house.update") {
      const existing = await db.boardingHouse.findUnique({ where: { id } });
      if (!existing || existing.schoolId !== schoolId) return bad("House not found", 404);
      const house = await db.boardingHouse.update({ where: { id }, data });
      return NextResponse.json({ house });
    }
    const house = await db.boardingHouse.create({ data: { schoolId, ...data } });
    return NextResponse.json({ house });
  }

  if (action === "house.delete") {
    const h = await db.boardingHouse.findUnique({ where: { id: body.id }, include: { dormitories: { select: { id: true } } } });
    if (!h || h.schoolId !== schoolId) return bad("House not found", 404);
    if (h.dormitories.length) {
      return bad(`Cannot delete "${h.name}" — it still has ${h.dormitories.length} dormitor${h.dormitories.length === 1 ? "y" : "ies"}. Remove or reassign them first.`, 409);
    }
    await db.boardingHouse.delete({ where: { id: h.id } });
    return NextResponse.json({ ok: true });
  }

  // ── DORMITORIES ──
  if (action === "dorm.create" || action === "dorm.update") {
    const { id, houseId, name, capacity, type, floor, description, prefectId, status } = body;
    if (!name?.trim()) return bad("Dormitory name is required");
    if (action === "dorm.update" && !id) return bad("Dormitory id is required");

    let targetHouseId: string | null = houseId ?? null;
    if (action === "dorm.update") {
      const existing = await db.dormitory.findUnique({ where: { id } });
      if (!existing || existing.schoolId !== schoolId) return bad("Dormitory not found", 404);
      targetHouseId = houseId ?? existing.houseId;
    }
    if (!targetHouseId) return bad("A boarding house is required");
    const house = await db.boardingHouse.findUnique({ where: { id: targetHouseId } });
    if (!house || house.schoolId !== schoolId) return bad("Boarding house not found", 404);

    let dormType: string | null = null;
    if (typeof type === "string" && type.trim()) {
      const t = type.trim().toLowerCase();
      if (t === "male") dormType = "Male";
      else if (t === "female") dormType = "Female";
      else return bad("Dormitory type must be Male or Female");
    }

    const prefId = optStr(prefectId);
    if (prefId) {
      const err = await checkLearner(schoolId, prefId);
      if (err) return bad(err);
    }
    let st = "AVAILABLE";
    if (status != null && `${status}`.trim() !== "") {
      const v = enumVal(status, HOUSE_STATUSES);
      if (!v || v === "…invalid…") return bad("Status must be AVAILABLE, ASSIGNED or MAINTENANCE");
      st = v;
    }

    const data = {
      houseId: targetHouseId,
      name: name.trim(),
      capacity: Math.max(0, Math.round(Number(capacity) || 0)),
      type: dormType,
      floor: optStr(floor),
      description: optStr(description),
      prefectId: prefId,
      status: st,
    };

    if (action === "dorm.update") {
      const dorm = await db.dormitory.update({ where: { id }, data });
      return NextResponse.json({ dorm });
    }
    const dorm = await db.dormitory.create({ data: { schoolId, ...data } });
    return NextResponse.json({ dorm });
  }

  if (action === "dorm.delete") {
    const d = await db.dormitory.findUnique({ where: { id: body.id }, include: { beds: { select: { id: true } } } });
    if (!d || d.schoolId !== schoolId) return bad("Dormitory not found", 404);
    if (d.beds.length) {
      return bad(`Cannot delete "${d.name}" — it still has ${d.beds.length} bed${d.beds.length === 1 ? "" : "s"}. Delete the beds first.`, 409);
    }
    await db.dormitory.delete({ where: { id: d.id } });
    return NextResponse.json({ ok: true });
  }

  // ── BEDS ──
  if (action === "bed.create") {
    const { code, dormitoryId, bedType, status } = body;
    const c = `${code ?? ""}`.trim().toUpperCase();
    if (!c) return bad("Bed code is required");
    if (!dormitoryId) return bad("A dormitory is required");
    const dorm = await db.dormitory.findUnique({ where: { id: dormitoryId } });
    if (!dorm || dorm.schoolId !== schoolId) return bad("Dormitory not found", 404);
    const dup = await db.bed.findUnique({ where: { schoolId_code: { schoolId, code: c } } });
    if (dup) return bad(`Bed code ${c} is already in use`, 409);

    let bt = "SINGLE";
    if (bedType != null && `${bedType}`.trim() !== "") {
      const v = enumVal(bedType, BED_TYPES);
      if (!v || v === "…invalid…") return bad("Bed type must be SINGLE or BUNK");
      bt = v;
    }
    let st = "AVAILABLE";
    if (status != null && `${status}`.trim() !== "") {
      const v = enumVal(status, HOUSE_STATUSES);
      if (!v || v === "…invalid…") return bad("Status must be AVAILABLE, ASSIGNED or MAINTENANCE");
      st = v;
    }
    const bed = await db.bed.create({ data: { schoolId, dormitoryId, code: c, bedType: bt, status: st } });
    return NextResponse.json({ bed });
  }

  if (action === "bed.bulk") {
    const { dormitoryId, prefix, count, bedType } = body;
    const p = `${prefix ?? ""}`.trim().toUpperCase().replace(/[^A-Z0-9]/g, "");
    if (!p) return bad("A bed code prefix is required (e.g. AGA)");
    if (!dormitoryId) return bad("A dormitory is required");
    const dorm = await db.dormitory.findUnique({ where: { id: dormitoryId } });
    if (!dorm || dorm.schoolId !== schoolId) return bad("Dormitory not found", 404);

    const n = Math.round(Number(count) || 0);
    if (n < 1 || n > 100) return bad("Count must be between 1 and 100 beds");

    let bt = "SINGLE";
    if (bedType != null && `${bedType}`.trim() !== "") {
      const v = enumVal(bedType, BED_TYPES);
      if (!v || v === "…invalid…") return bad("Bed type must be SINGLE or BUNK");
      bt = v;
    }

    const wanted = Array.from({ length: n }, (_, i) => `${p}-${String(i + 1).padStart(3, "0")}`);
    const existing = await db.bed.findMany({ where: { schoolId, code: { in: wanted } }, select: { code: true } });
    const taken = new Set(existing.map((b) => b.code));
    const fresh = wanted.filter((c) => !taken.has(c));
    if (fresh.length) {
      await db.bed.createMany({ data: fresh.map((code) => ({ schoolId, dormitoryId, code, bedType: bt })) });
    }
    return NextResponse.json({ created: fresh.length, skipped: taken.size, prefix: p, codes: fresh });
  }

  if (action === "bed.update") {
    const { id, code, dormitoryId, bedType, status } = body;
    if (!id) return bad("Bed id is required");
    const bed = await db.bed.findUnique({ where: { id } });
    if (!bed || bed.schoolId !== schoolId) return bad("Bed not found", 404);

    const data: Record<string, unknown> = {};

    if (code != null && `${code}`.trim() !== "") {
      const c = `${code}`.trim().toUpperCase();
      if (c !== bed.code) {
        const dup = await db.bed.findUnique({ where: { schoolId_code: { schoolId, code: c } } });
        if (dup) return bad(`Bed code ${c} is already in use`, 409);
      }
      data.code = c;
    }
    if (dormitoryId && dormitoryId !== bed.dormitoryId) {
      if (bed.studentId) return bad("Bed is occupied — release the boarder before moving it to another dormitory", 409);
      const dorm = await db.dormitory.findUnique({ where: { id: dormitoryId } });
      if (!dorm || dorm.schoolId !== schoolId) return bad("Dormitory not found", 404);
      data.dormitoryId = dormitoryId;
    }
    if (bedType != null && `${bedType}`.trim() !== "") {
      const v = enumVal(bedType, BED_TYPES);
      if (!v || v === "…invalid…") return bad("Bed type must be SINGLE or BUNK");
      data.bedType = v;
    }
    if (status != null && `${status}`.trim() !== "") {
      const v = enumVal(status, HOUSE_STATUSES);
      if (!v || v === "…invalid…") return bad("Status must be AVAILABLE, ASSIGNED or MAINTENANCE");
      if (v !== "ASSIGNED" && bed.studentId) {
        return bad("Bed is occupied — release the boarder before changing its status", 409);
      }
      data.status = v;
    }
    const updated = await db.bed.update({ where: { id }, data });
    return NextResponse.json({ bed: updated });
  }

  if (action === "bed.delete") {
    const b = await db.bed.findUnique({ where: { id: body.id }, include: { student: true } });
    if (!b || b.schoolId !== schoolId) return bad("Bed not found", 404);
    if (b.studentId || b.status === "ASSIGNED") {
      const who = b.student ? fullName(b.student) : "a learner";
      return bad(`Bed ${b.code} is assigned to ${who} — release the boarder first`, 409);
    }
    await db.bed.delete({ where: { id: b.id } });
    return NextResponse.json({ ok: true });
  }

  // ── ASSIGNMENT ──
  if (action === "bed.assign") {
    const { bedId, studentId } = body;
    if (!bedId || !studentId) return bad("Bed and learner are required");

    const bed = await db.bed.findUnique({ where: { id: bedId }, include: { dormitory: true } });
    if (!bed || bed.schoolId !== schoolId) return bad("Bed not found", 404);
    if (bed.studentId) return bad(`Bed ${bed.code} is already occupied`, 409);
    if (bed.status !== "AVAILABLE") return bad(`Bed ${bed.code} is not available (status: ${bed.status})`, 409);

    const student = await db.student.findUnique({ where: { id: studentId }, include: { bedAssigned: true } });
    if (!student) return bad("Learner not found", 404);
    if (student.bedAssigned) return bad(`${fullName(student)} already occupies bed ${student.bedAssigned.code}`, 409);

    try {
      const result = await db.$transaction(async (tx) => {
        const updated = await tx.bed.update({
          where: { id: bed.id },
          data: { status: "ASSIGNED", studentId, assignedAt: new Date() },
        });
        const residence = await tx.enrollment.updateMany({
          where: { studentId, schoolId, status: "ACTIVE" },
          data: { residenceType: "BOARDING" },
        });
        return { bed: updated, residenceUpdated: residence.count };
      });
      return NextResponse.json({ ...result, learner: fullName(student) });
    } catch (e: unknown) {
      const code = (e as { code?: string })?.code;
      if (code === "P2002") return bad("That learner was just assigned to another bed — refresh and retry", 409);
      throw e;
    }
  }

  if (action === "bed.release") {
    const { bedId } = body;
    if (!bedId) return bad("Bed id is required");
    const bed = await db.bed.findUnique({ where: { id: bedId }, include: { student: true } });
    if (!bed || bed.schoolId !== schoolId) return bad("Bed not found", 404);
    if (!bed.studentId) return bad("This bed has no occupant to release", 400);

    const released = await db.$transaction(async (tx) => {
      const updated = await tx.bed.update({
        where: { id: bed.id },
        data: { status: "AVAILABLE", studentId: null, assignedAt: null },
      });
      const residence = await tx.enrollment.updateMany({
        where: { studentId: bed.studentId!, schoolId, status: "ACTIVE" },
        data: { residenceType: "DAY" },
      });
      return { bed: updated, residenceUpdated: residence.count, learner: bed.student ? fullName(bed.student) : null };
    });
    return NextResponse.json({ ok: true, ...released });
  }

  if (action === "student.setResidence") {
    const { studentId, residenceType } = body;
    if (!studentId) return bad("Learner is required");
    const rt = enumVal(residenceType, ["DAY", "BOARDING"]);
    if (!rt || rt === "…invalid…") return bad("Residence type must be DAY or BOARDING");
    const updated = await db.enrollment.updateMany({
      where: { studentId, schoolId, status: "ACTIVE" },
      data: { residenceType: rt },
    });
    if (!updated.count) return bad("Learner has no active enrollment at this school", 404);
    return NextResponse.json({ updated: updated.count, residenceType: rt });
  }

  return bad("Unknown action");
}

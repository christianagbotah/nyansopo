import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

// ── GET /api/school/[id]/ops/classes — classes, teachers, subjects, timetable ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["classes.view"] });
  if (!auth.ok) return auth.res;

  const url = new URL(req.url);
  const classroomId = url.searchParams.get("classroomId") ?? undefined;

  const classrooms = await db.classroom.findMany({
    where: { schoolId },
    include: {
      classTeacher: true,
      timetableSlots: { include: { subject: true, staff: true }, orderBy: [{ dayOfWeek: "asc" }, { startMinute: "asc" }] },
      _count: { select: { enrollments: { where: { status: "ACTIVE" } } } },
    },
    orderBy: [{ gradeLevel: "asc" }, { name: "asc" }],
  });

  const staff = await db.staff.findMany({
    where: { schoolId, active: true },
    select: { id: true, fullName: true, category: true, position: true, subjects: true, role: true },
    orderBy: { fullName: "asc" },
  });

  const subjects = await db.subject.findMany({ where: { schoolId }, orderBy: { name: "asc" } });

  return NextResponse.json({
    classrooms: classrooms.map((c) => ({
      id: c.id,
      name: c.name,
      gradeLevel: c.gradeLevel,
      stageLabel: c.stageLabel,
      capacity: c.capacity,
      classTeacherId: c.classTeacherId,
      classTeacher: c.classTeacher?.fullName ?? null,
      enrolled: c._count.enrollments,
      slots: c.timetableSlots.map((s) => ({
        id: s.id,
        subjectId: s.subjectId,
        subjectName: s.subject?.name ?? s.subjectName,
        subjectCode: s.subject?.code ?? null,
        staffId: s.staffId,
        staffName: s.staff?.fullName ?? null,
        dayOfWeek: s.dayOfWeek,
        startMinute: s.startMinute,
        endMinute: s.endMinute,
        notes: s.notes,
      })),
    })),
    staff: staff.map((s) => ({
      id: s.id, name: s.fullName, category: s.category, position: s.position,
      role: s.role, subjects: s.subjects ? s.subjects.split(",").map((x) => x.trim()).filter(Boolean) : [],
    })),
    subjects: subjects.map((s) => ({ id: s.id, name: s.name, code: s.code, isCore: s.isCore })),
  });
}

// ── POST /api/school/[id]/ops/classes ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["classes.manage"] });
  if (!auth.ok) return auth.res;

  if (action === "save-classroom") {
    const { id, name, gradeLevel, stageLabel, capacity, classTeacherId } = body;
    if (!name?.trim() || !gradeLevel?.trim()) return NextResponse.json({ error: "Class name and grade level are required" }, { status: 400 });
    const data = {
      name: String(name).trim(),
      gradeLevel: String(gradeLevel).trim(),
      stageLabel: stageLabel?.trim() || null,
      capacity: Math.max(1, Number(capacity) || 30),
      classTeacherId: classTeacherId || null,
    };
    if (id) {
      const existing = await db.classroom.findUnique({ where: { id } });
      if (!existing || existing.schoolId !== schoolId) return NextResponse.json({ error: "Class not found" }, { status: 404 });
      const updated = await db.classroom.update({ where: { id }, data });
      return NextResponse.json({ classroom: updated });
    }
    const created = await db.classroom.create({ data: { ...data, schoolId } });
    return NextResponse.json({ classroom: created });
  }

  if (action === "delete-classroom") {
    const { id } = body;
    const existing = await db.classroom.findUnique({ where: { id }, include: { _count: { select: { enrollments: true } } } });
    if (!existing || existing.schoolId !== schoolId) return NextResponse.json({ error: "Class not found" }, { status: 404 });
    if (existing._count.enrollments > 0) return NextResponse.json({ error: `Cannot delete — ${existing._count.enrollments} learner(s) are enrolled in this class` }, { status: 400 });
    await db.timetableSlot.deleteMany({ where: { classroomId: id } });
    await db.classroom.delete({ where: { id } });
    return NextResponse.json({ ok: true });
  }

  if (action === "save-slot") {
    const { id, classroomId, subjectId, subjectName, staffId, dayOfWeek, startMinute, endMinute, notes } = body;
    if (!classroomId) return NextResponse.json({ error: "Class is required" }, { status: 400 });
    const day = Number(dayOfWeek), start = Number(startMinute), end = Number(endMinute);
    if (!(day >= 1 && day <= 5)) return NextResponse.json({ error: "Day must be Monday–Friday" }, { status: 400 });
    if (!(start >= 0 && end > start && end <= 1440)) return NextResponse.json({ error: "Invalid time range" }, { status: 400 });
    const name = subjectId ? null : String(subjectName ?? "").trim();
    if (!subjectId && !name) return NextResponse.json({ error: "Choose a subject or type a lesson name" }, { status: 400 });
    // overlap guard (same class, same day)
    const overlapping = await db.timetableSlot.findFirst({
      where: { classroomId, dayOfWeek: day, AND: [{ startMinute: { lt: end } }, { endMinute: { gt: start } }], ...(id ? { id: { not: id } } : {}) },
    });
    if (overlapping) return NextResponse.json({ error: "This period overlaps an existing lesson on that day" }, { status: 400 });
    const data = {
      classroomId, schoolId,
      subjectId: subjectId || null,
      subjectName: name ?? (await db.subject.findUnique({ where: { id: subjectId } }))?.name ?? "Lesson",
      staffId: staffId || null,
      dayOfWeek: day, startMinute: start, endMinute: end,
      notes: notes?.trim() || null,
    };
    const slot = id
      ? await db.timetableSlot.update({ where: { id }, data })
      : await db.timetableSlot.create({ data });
    return NextResponse.json({ slot });
  }

  if (action === "delete-slot") {
    const { id } = body;
    const existing = await db.timetableSlot.findUnique({ where: { id } });
    if (!existing || existing.schoolId !== schoolId) return NextResponse.json({ error: "Period not found" }, { status: 404 });
    await db.timetableSlot.delete({ where: { id } });
    return NextResponse.json({ ok: true });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

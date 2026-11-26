import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { audit } from "@/lib/audit";
import { sendSms } from "@/lib/sms";

// ── Exam Timetable & Invigilation (wave 70) ────────────────────
// The docket every Ghanaian hall runs on: subject × date × start ×
// duration × venue with named invigilators. The API is the referee —
// a class, a venue or an invigilator can never be scheduled into two
// overlapping papers on the same day (clash guards on every write).
//   GET                     — entries + classrooms + staff + known exam names
//   POST entry.add          — one paper (clash-guarded)
//   POST entry.update       — edit a paper (clash-guarded, excludes itself)
//   POST entry.delete       — remove a paper
//   POST entry.invigilator  — set/clear the invigilator list on a paper
//   POST draft.build        — deterministic auto-draft: spreads every chosen
//                             class's subjects (from the live timetable) over
//                             N days × M windows, clash-free by construction
//   POST notify.invigilators— SMS every invigilator their duty list
// Printing is client-side (per-day docket sheet with the school header,
// like the seating chart and paper bank).

const bad = (error: string, status = 400) => NextResponse.json({ error }, { status });

const isoDay = (d: Date) => d.toISOString().slice(0, 10);
const hhmm = (m: number) => `${String(Math.floor(m / 60)).padStart(2, "0")}:${String(m % 60).padStart(2, "0")}`;

export type TtEntry = {
  id: string; examName: string; classroomId: string; classroomName: string;
  subjectName: string; date: string; startMinute: number; durationMin: number;
  venue: string | null; invigilatorIds: string[]; invigilators: string[]; createdBy: string;
};

export async function loadEntries(schoolId: string): Promise<TtEntry[]> {
  const rows = await db.examTimetableEntry.findMany({
    where: { schoolId },
    orderBy: [{ date: "asc" }, { startMinute: "asc" }],
    include: { classroom: { select: { name: true } } },
  });
  const staffIds = new Set<string>();
  for (const r of rows) for (const id of r.invigilatorIds.split(",").filter(Boolean)) staffIds.add(id);
  const staff = staffIds.size
    ? await db.staff.findMany({ where: { id: { in: [...staffIds] } }, select: { id: true, fullName: true } })
    : [];
  const nameOf = new Map(staff.map((s) => [s.id, s.fullName]));
  return rows.map((r) => {
    const ids = r.invigilatorIds.split(",").filter(Boolean);
    return {
      id: r.id, examName: r.examName, classroomId: r.classroomId, classroomName: r.classroom.name,
      subjectName: r.subjectName, date: isoDay(r.date), startMinute: r.startMinute,
      durationMin: r.durationMin, venue: r.venue, invigilatorIds: ids,
      invigilators: ids.map((i) => nameOf.get(i) ?? "Unknown staff"), createdBy: r.createdBy,
    };
  });
}

/* clash check — same-day overlapping window on (a) the same class,
   (b) the same venue, (c) any shared invigilator. excludeId lets edits
   ignore the paper being edited. Returns a human error or null. */
async function clashFor(
  schoolId: string,
  cand: { date: string; startMinute: number; durationMin: number; classroomId: string; venue: string | null; invigilatorIds: string[] },
  excludeId?: string,
): Promise<string | null> {
  const dayStart = new Date(`${cand.date}T00:00:00.000Z`);
  const rows = await db.examTimetableEntry.findMany({
    where: { schoolId, date: dayStart, ...(excludeId ? { id: { not: excludeId } } : {}) },
    include: { classroom: { select: { name: true } } },
  });
  const end = cand.startMinute + cand.durationMin;
  for (const r of rows) {
    const rEnd = r.startMinute + r.durationMin;
    const overlap = cand.startMinute < rEnd && r.startMinute < end;
    if (!overlap) continue;
    const when = `${hhmm(r.startMinute)}–${hhmm(rEnd)}`;
    if (r.classroomId === cand.classroomId)
      return `Clash: ${r.classroom.name} already sits ${r.subjectName} at ${when} on ${cand.date}`;
    if (r.venue && cand.venue && r.venue.trim().toLowerCase() === cand.venue.trim().toLowerCase())
      return `Clash: venue "${r.venue}" already hosts ${r.classroom.name} — ${r.subjectName} at ${when}`;
    const shared = r.invigilatorIds.split(",").filter((x) => cand.invigilatorIds.includes(x));
    if (shared.length) {
      const st = await db.staff.findFirst({ where: { id: shared[0] }, select: { fullName: true } });
      return `Clash: ${st?.fullName ?? "An invigilator"} is already on ${r.classroom.name} — ${r.subjectName} at ${when}`;
    }
  }
  return null;
}

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["exams.online"] });
  if (!auth.ok) return auth.res;

  const [entries, classrooms, staff, exams, subjects] = await Promise.all([
    loadEntries(schoolId),
    db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" }, select: { id: true, name: true } }),
    db.staff.findMany({
      where: { schoolId, active: true },
      orderBy: { fullName: "asc" },
      select: { id: true, fullName: true, phone: true, smsNotify: true, category: true },
    }),
    db.examTimetableEntry.findMany({ where: { schoolId }, distinct: ["examName"], select: { examName: true }, orderBy: { examName: "asc" } }),
    db.timetableSlot.findMany({ where: { schoolId }, distinct: ["subjectName"], select: { subjectName: true }, orderBy: { subjectName: "asc" } }),
  ]);

  return NextResponse.json({
    entries,
    classrooms,
    staff: staff.map((s) => ({ id: s.id, name: s.fullName, phone: !!s.phone, smsNotify: !!s.smsNotify, category: s.category })),
    examNames: exams.map((e) => e.examName),
    subjects: subjects.map((s) => s.subjectName),
  });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["exams.online"] });
  if (!auth.ok) return auth.res;
  const { user } = auth;
  const body = (await req.json().catch(() => ({}))) as Record<string, unknown>;
  const action = String(body.action ?? "");
  const optStr = (v: unknown): string | null => {
    if (typeof v !== "string") return null;
    const t = v.trim();
    return t.length ? t : null;
  };
  const strArr = (v: unknown): string[] => (Array.isArray(v) ? v.filter((x): x is string => typeof x === "string" && !!x.trim()) : []);

  /* ── add one paper ── */
  if (action === "entry.add") {
    const examName = optStr(body.examName);
    const classroomId = optStr(body.classroomId);
    const subjectName = optStr(body.subjectName);
    const date = optStr(body.date); // "YYYY-MM-DD"
    if (!examName) return bad("Exam name is required");
    if (!classroomId) return bad("Class is required");
    if (!subjectName) return bad("Subject is required");
    if (!date || !/^\d{4}-\d{2}-\d{2}$/.test(date)) return bad("A valid date (YYYY-MM-DD) is required");
    const startMinute = Math.max(0, Math.min(1439, Number(body.startMinute) || 0));
    const durationMin = Math.max(15, Math.min(360, Number(body.durationMin) || 60));
    const venue = optStr(body.venue);
    const invigilatorIds = [...new Set(strArr(body.invigilatorIds))];
    const cls = await db.classroom.findFirst({ where: { id: classroomId, schoolId } });
    if (!cls) return bad("Class not found");
    const clash = await clashFor(schoolId, { date, startMinute, durationMin, classroomId, venue, invigilatorIds });
    if (clash) return bad(clash, 409);
    const row = await db.examTimetableEntry.create({
      data: {
        schoolId, examName, classroomId, subjectName,
        date: new Date(`${date}T00:00:00.000Z`), startMinute, durationMin,
        venue, invigilatorIds: invigilatorIds.join(","), createdBy: user.fullName,
      },
    });
    await audit({
      schoolId, actor: { id: user.id, fullName: user.fullName, role: user.role },
      action: "CREATE", entityType: "ExamTimetableEntry", entityId: row.id, entityLabel: `${subjectName} — ${cls.name}`,
      summary: `Exam docket: ${examName} — ${cls.name} ${subjectName} on ${date} at ${hhmm(startMinute)} (${durationMin}min${venue ? `, ${venue}` : ""})${invigilatorIds.length ? `, ${invigilatorIds.length} invigilator(s)` : ""}`, req,
    });
    return NextResponse.json({ ok: true, id: row.id, entries: await loadEntries(schoolId) });
  }

  /* ── edit a paper ── */
  if (action === "entry.update") {
    const id = optStr(body.id);
    if (!id) return bad("id is required");
    const row = await db.examTimetableEntry.findFirst({ where: { id, schoolId } });
    if (!row) return bad("Paper not found", 404);
    const next = {
      examName: optStr(body.examName) ?? row.examName,
      classroomId: optStr(body.classroomId) ?? row.classroomId,
      subjectName: optStr(body.subjectName) ?? row.subjectName,
      date: optStr(body.date) ?? isoDay(row.date),
      startMinute: body.startMinute !== undefined ? Math.max(0, Math.min(1439, Number(body.startMinute) || 0)) : row.startMinute,
      durationMin: body.durationMin !== undefined ? Math.max(15, Math.min(360, Number(body.durationMin) || 60)) : row.durationMin,
      venue: body.venue !== undefined ? optStr(body.venue) : row.venue,
      invigilatorIds: body.invigilatorIds !== undefined ? [...new Set(strArr(body.invigilatorIds))] : row.invigilatorIds.split(",").filter(Boolean),
    };
    if (!/^\d{4}-\d{2}-\d{2}$/.test(next.date)) return bad("Date must be YYYY-MM-DD");
    const cls = await db.classroom.findFirst({ where: { id: next.classroomId, schoolId } });
    if (!cls) return bad("Class not found");
    const clash = await clashFor(schoolId, next, row.id);
    if (clash) return bad(clash, 409);
    await db.examTimetableEntry.update({
      where: { id: row.id },
      data: {
        examName: next.examName, classroomId: next.classroomId, subjectName: next.subjectName,
        date: new Date(`${next.date}T00:00:00.000Z`), startMinute: next.startMinute, durationMin: next.durationMin,
        venue: next.venue, invigilatorIds: next.invigilatorIds.join(","),
      },
    });
    await audit({
      schoolId, actor: { id: user.id, fullName: user.fullName, role: user.role },
      action: "UPDATE", entityType: "ExamTimetableEntry", entityId: row.id, entityLabel: row.subjectName,
      summary: `Exam docket edit: ${row.examName} — ${row.subjectName} now ${next.date} ${hhmm(next.startMinute)}`, req,
    });
    return NextResponse.json({ ok: true, entries: await loadEntries(schoolId) });
  }

  /* ── delete a paper ── */
  if (action === "entry.delete") {
    const id = optStr(body.id);
    if (!id) return bad("id is required");
    const row = await db.examTimetableEntry.findFirst({ where: { id, schoolId } });
    if (!row) return bad("Paper not found", 404);
    await db.examTimetableEntry.delete({ where: { id } });
    await audit({
      schoolId, actor: { id: user.id, fullName: user.fullName, role: user.role },
      action: "DELETE", entityType: "ExamTimetableEntry", entityId: id, entityLabel: row.subjectName,
      summary: `Exam docket: removed ${row.examName} — ${row.subjectName} (${isoDay(row.date)} ${hhmm(row.startMinute)})`, req,
    });
    return NextResponse.json({ ok: true, entries: await loadEntries(schoolId) });
  }

  /* ── set invigilators on a paper ── */
  if (action === "entry.invigilator") {
    const id = optStr(body.id);
    if (!id) return bad("id is required");
    const ids = [...new Set(strArr(body.invigilatorIds))];
    const row = await db.examTimetableEntry.findFirst({ where: { id, schoolId } });
    if (!row) return bad("Paper not found", 404);
    const clash = await clashFor(
      schoolId,
      { date: isoDay(row.date), startMinute: row.startMinute, durationMin: row.durationMin, classroomId: row.classroomId, venue: row.venue, invigilatorIds: ids },
      row.id,
    );
    if (clash) return bad(clash, 409);
    await db.examTimetableEntry.update({ where: { id }, data: { invigilatorIds: ids.join(",") } });
    await audit({
      schoolId, actor: { id: user.id, fullName: user.fullName, role: user.role },
      action: "UPDATE", entityType: "ExamTimetableEntry", entityId: id, entityLabel: row.subjectName,
      summary: `Exam docket invigilators set (${ids.length}) on ${row.subjectName} — ${isoDay(row.date)} ${hhmm(row.startMinute)}`, req,
    });
    return NextResponse.json({ ok: true, entries: await loadEntries(schoolId) });
  }

  /* ── deterministic auto-draft ── */
  if (action === "draft.build") {
    const examName = optStr(body.examName);
    if (!examName) return bad("Exam name is required");
    const classroomIds = [...new Set(strArr(body.classroomIds))];
    if (!classroomIds.length) return bad("Pick at least one class");
    const startDate = optStr(body.startDate);
    if (!startDate || !/^\d{4}-\d{2}-\d{2}$/.test(startDate)) return bad("Start date must be YYYY-MM-DD");
    const days = Math.max(1, Math.min(15, Number(body.days) || 5));
    const perDay = Math.max(1, Math.min(4, Number(body.perDay) || 2));
    const durationMin = Math.max(30, Math.min(240, Number(body.durationMin) || 90));
    const venue = optStr(body.venue);
    // windows: papers per day spread across the school day (Ghanaian docket rhythm)
    const windows = perDay >= 4 ? [9 * 60, 11 * 60, 13 * 60 + 30, 15 * 60]
      : perDay === 3 ? [9 * 60, 11 * 60, 13 * 60 + 30]
      : perDay === 2 ? [9 * 60, 11 * 60 + 30]
      : [9 * 60];

    // every chosen class's subjects in timetable order (the way they're taught)
    const slots = await db.timetableSlot.findMany({
      where: { schoolId, classroomId: { in: classroomIds } },
      orderBy: { startMinute: "asc" },
      select: { classroomId: true, subjectName: true },
    });
    const queues = new Map<string, string[]>();
    for (const cid of classroomIds) queues.set(cid, []);
    for (const s of slots) {
      const q = queues.get(s.classroomId)!;
      if (!q.includes(s.subjectName)) q.push(s.subjectName);
    }
    const empty = classroomIds.filter((c) => !queues.get(c)!.length);
    const candidates = classroomIds.filter((c) => queues.get(c)!.length > 0);
    if (!candidates.length) return bad("No timetable subjects found for the chosen classes — build their routine first");

    // round-robin: day → window → class. Each class appears at most once per
    // window, so the draft is clash-free by construction (venues empty,
    // invigilators assigned afterwards).
    const drafts: { classroomId: string; subjectName: string; date: string; startMinute: number }[] = [];
    let remaining = [...candidates];
    for (let d = 0; d < days && remaining.length; d++) {
      const day = new Date(new Date(`${startDate}T00:00:00.000Z`).getTime() + d * 86400000);
      const date = isoDay(day);
      for (let w = 0; w < windows.length && remaining.length; w++) {
        for (const cid of [...remaining]) {
          const q = queues.get(cid)!;
          const subject = q.shift()!;
          drafts.push({ classroomId: cid, subjectName: subject, date, startMinute: windows[w] });
          if (!q.length) remaining = remaining.filter((x) => x !== cid);
        }
      }
    }
    if (!drafts.length) return bad("Nothing to schedule — every chosen class has exhausted its subjects");
    await db.examTimetableEntry.deleteMany({ where: { schoolId, examName } }); // rebuild this exam's docket
    await db.examTimetableEntry.createMany({
      data: drafts.map((d) => ({
        schoolId, examName, classroomId: d.classroomId, subjectName: d.subjectName,
        date: new Date(`${d.date}T00:00:00.000Z`), startMinute: d.startMinute, durationMin,
        venue, invigilatorIds: "", createdBy: user.fullName,
      })),
    });
    await audit({
      schoolId, actor: { id: user.id, fullName: user.fullName, role: user.role },
      action: "CREATE", entityType: "ExamTimetableEntry", entityId: examName, entityLabel: examName,
      summary: `Exam docket auto-draft "${examName}": ${drafts.length} papers over ${days} day(s) × ${perDay} window(s), ${classroomIds.length} class(es)${venue ? `, venue ${venue}` : ""}`, req,
    });
    return NextResponse.json({
      ok: true, created: drafts.length, exhausted: remaining.length === 0,
      skippedEmpty: empty, entries: await loadEntries(schoolId),
    });
  }

  /* ── SMS every invigilator their duty list ── */
  if (action === "notify.invigilators") {
    const examName = optStr(body.examName);
    const date = optStr(body.date); // optional — limit to one day
    const rows = await db.examTimetableEntry.findMany({
      where: { schoolId, ...(examName ? { examName } : {}), ...(date ? { date: new Date(`${date}T00:00:00.000Z`) } : {}) },
      orderBy: [{ date: "asc" }, { startMinute: "asc" }],
      include: { classroom: { select: { name: true } } },
    });
    if (!rows.length) return bad("No papers match that filter");
    const duty = new Map<string, typeof rows>();
    for (const r of rows) {
      for (const sid of r.invigilatorIds.split(",").filter(Boolean)) {
        if (!duty.has(sid)) duty.set(sid, []);
        duty.get(sid)!.push(r);
      }
    }
    const ids = [...duty.keys()];
    const staffRows = ids.length ? await db.staff.findMany({ where: { id: { in: ids } }, select: { id: true, fullName: true, phone: true, smsNotify: true } }) : [];
    const results: { staff: string; sms: string }[] = [];
    for (const s of staffRows) {
      const lines = duty.get(s.id)!
        .map((r) => `${isoDay(r.date)} ${hhmm(r.startMinute)} ${r.subjectName} (${r.classroom.name}${r.venue ? `, ${r.venue}` : ""})`)
        .join("; ");
      if (s.smsNotify && s.phone) {
        const msg = await sendSms({
          db, schoolId, studentId: null,
          recipientName: s.fullName, recipientPhone: s.phone,
          body: `INVIGILATION DUTY${examName ? ` — ${examName}` : ""}: ${s.fullName}, your papers: ${lines}. Set by ${user.fullName}.`,
          category: "STAFF", sentByName: user.fullName,
        });
        results.push({ staff: s.fullName, sms: msg.status });
      } else {
        results.push({ staff: s.fullName, sms: "SKIPPED (no phone or opted out)" });
      }
    }
    await audit({
      schoolId, actor: { id: user.id, fullName: user.fullName, role: user.role },
      action: "UPDATE", entityType: "ExamTimetableEntry", entityId: examName ?? "all", entityLabel: examName ?? "Exam docket",
      summary: `Invigilation duty SMS: ${results.filter((r) => r.sms === "SENT").length}/${results.length} sent${date ? ` for ${date}` : ""}`, req,
    });
    return NextResponse.json({ ok: true, results });
  }

  return bad("Unknown action");
}

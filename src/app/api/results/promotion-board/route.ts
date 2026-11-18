import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { nextGradeLevel } from "@/lib/grade-ladder";

/* ─────────────────────────────────────────────────────────────
   Wave 23 — the end-of-year promotion board & class rollover.

   GET /api/results/promotion-board?schoolId&termId&classId  (perm: reports.view)
     Reads the class-term through the report cards the results
     lifecycle has already compiled (waves 20–22) and proposes a
     placement decision per learner:
       • card.promoted === true            → PROMOTED  (avg ≥ 40)
       • card compiled && promoted false   → PROBATION
       • no card for this class-term       → UNDECIDED (no marks to judge)
     plus the auto destination classroom (grade ladder: KG2 → B1,
     B1→B2…, B9 → exit/graduate) and everything the head teacher
     needs to overrule: the whole class list, each learner's
     average / aggregate / attendance / published state.

   POST /api/results/promotion-board  (perm: reports.publish)
     action:"decide"   — stamp ReportCard.promotion for a set of
                         learners (PROMOTED | PROBATION | REPEAT),
                         the head teacher's override on the ledger.
     action:"rollover" — execute the decisions: every PROMOTED
                         learner's active placement is reassigned to
                         the destination classroom (stamped
                         promoted-from trail) or graduated when they
                         sit in the exit grade / no destination was
                         chosen; PROBATION & REPEAT learners stay put
                         with their card stamped. The school's
                         studentsOnRoll counter is refreshed, exactly
                         like the Students-module promotion wizard.

   Guards: everything is school-scoped through opsAuth + classroom/
   term ownership; a move without a destination and not in the exit
   grade is skipped (reported back) rather than guessed.
   ───────────────────────────────────────────────────────────── */

type Decision = "PROMOTED" | "PROBATION" | "REPEAT";
const DECISIONS: Decision[] = ["PROMOTED", "PROBATION", "REPEAT"];

export async function GET(req: NextRequest) {
  const url = new URL(req.url);
  const q = (k: string) => url.searchParams.get(k) ?? "";
  const schoolId = q("schoolId");
  const termId = q("termId");
  const classId = q("classId");
  if (!schoolId || !termId || !classId) {
    return NextResponse.json({ error: "schoolId, termId and classId are required" }, { status: 400 });
  }

  const auth = await opsAuth(req, schoolId, "GET", { perms: ["reports.view"] });
  if (!auth.ok) return auth.res;

  const [classroom, term] = await Promise.all([
    db.classroom.findFirst({ where: { id: classId, schoolId } }),
    db.term.findFirst({ where: { id: termId, academicYear: { schoolId } }, include: { academicYear: { select: { name: true } } } }),
  ]);
  if (!classroom) return NextResponse.json({ error: "Class not found in this school" }, { status: 404 });
  if (!term) return NextResponse.json({ error: "Term not found in this school" }, { status: 404 });

  const [enrolled, classrooms, cards] = await Promise.all([
    db.enrollment.findMany({
      where: { classroomId: classId, status: "ACTIVE" },
      include: { student: { select: { id: true, firstName: true, lastName: true, otherNames: true, nyansapoIndex: true, gender: true } } },
      orderBy: { rollNo: "asc" },
    }),
    db.classroom.findMany({ where: { schoolId }, select: { id: true, name: true, gradeLevel: true }, orderBy: { name: "asc" } }),
    db.reportCard.findMany({
      where: { termId, student: { enrollments: { some: { schoolId } } } },
      select: {
        id: true, studentId: true, average: true, aggregate: true, attendancePct: true,
        classPosition: true, classSize: true, promoted: true, promotion: true, published: true,
      },
    }),
  ]);

  const cardByStudent = new Map(cards.map((c) => [c.studentId, c]));
  const nextGrade = nextGradeLevel(classroom.gradeLevel);
  const destAuto = nextGrade ? classrooms.find((c) => c.gradeLevel === nextGrade) ?? null : null;

  const rows = enrolled.map((e) => {
    const card = cardByStudent.get(e.studentId) ?? null;
    const proposal: Decision | "UNDECIDED" = !card || card.promoted === null
      ? "UNDECIDED"
      : card.promoted ? "PROMOTED" : "PROBATION";
    return {
      studentId: e.studentId,
      name: [e.student.lastName, e.student.firstName, e.student.otherNames].filter(Boolean).join(" "),
      indexNo: e.student.nyansapoIndex,
      gender: e.student.gender,
      cardId: card?.id ?? null,
      average: card?.average ?? null,
      aggregate: card?.aggregate ?? null,
      attendancePct: card?.attendancePct ?? null,
      classPosition: card?.classPosition ?? null,
      classSize: card?.classSize ?? null,
      cardPromoted: card?.promoted ?? null,
      savedDecision: card?.promotion ?? null,
      cardPublished: card?.published ?? false,
      proposal,
      autoDestinationId: destAuto?.id ?? null,
      graduating: !destAuto, // exit grade, or the next grade has no classroom here
    };
  });

  const withCards = rows.filter((r) => r.cardId);
  const summary = {
    onRoll: rows.length,
    withCards: withCards.length,
    promoted: withCards.filter((r) => r.proposal === "PROMOTED").length,
    probation: withCards.filter((r) => r.proposal === "PROBATION").length,
    undecided: rows.length - withCards.length,
    graduating: rows.filter((r) => r.graduating).length,
  };

  return NextResponse.json({
    school: { id: schoolId },
    term: { id: term.id, name: term.name, yearName: term.academicYear.name },
    classroom: { id: classroom.id, name: classroom.name, gradeLevel: classroom.gradeLevel },
    nextGradeLevel: nextGrade,
    destinationClassroom: destAuto,
    classrooms,
    rows,
    summary,
  });
}

export async function POST(req: NextRequest) {
  const body = await req.json().catch(() => ({}));
  const S = (v: unknown) => (typeof v === "string" ? v.trim() : "");
  const schoolId = S(body.schoolId);
  const termId = S(body.termId);
  const classId = S(body.classId);
  const action = S(body.action);
  if (!schoolId || !termId || !classId || !action) {
    return NextResponse.json({ error: "schoolId, termId, classId and action are required" }, { status: 400 });
  }

  const auth = await opsAuth(req, schoolId, "POST", { perms: ["reports.publish"] });
  if (!auth.ok) return auth.res;

  const classroom = await db.classroom.findFirst({ where: { id: classId, schoolId } });
  if (!classroom) return NextResponse.json({ error: "Class not found in this school" }, { status: 404 });

  const enrolled = await db.enrollment.findMany({
    where: { classroomId: classId, status: "ACTIVE" },
    select: { studentId: true, student: { select: { firstName: true, lastName: true, otherNames: true } } },
  });
  const rosterIds = new Set(enrolled.map((e) => e.studentId));
  const nameOf = (studentId: string) => {
    const e = enrolled.find((x) => x.studentId === studentId);
    return e ? [e.student.lastName, e.student.firstName, e.student.otherNames].filter(Boolean).join(" ") : studentId;
  };

  /* ── action: decide — stamp the head teacher's promotion decision
     onto the class-term report cards (the ledger stays the truth) ── */
  if (action === "decide") {
    const decision = S(body.decision) as Decision;
    const studentIds: string[] = Array.isArray(body.studentIds) ? body.studentIds.filter((s: unknown) => typeof s === "string") : [];
    if (!DECISIONS.includes(decision)) {
      return NextResponse.json({ error: "decision must be PROMOTED, PROBATION or REPEAT" }, { status: 400 });
    }
    if (!studentIds.length) return NextResponse.json({ error: "studentIds[] required" }, { status: 400 });

    const stampable = studentIds.filter((id) => rosterIds.has(id));
    const res = await db.reportCard.updateMany({
      where: { termId, studentId: { in: stampable } },
      data: { promotion: decision },
    });
    return NextResponse.json({ ok: true, decided: res.count, decision });
  }

  /* ── action: rollover — execute the board: PROMOTED learners move
     (or graduate), PROBATION/REPEAT stay with the card stamped ── */
  if (action === "rollover") {
    const moves: { studentId: string; decision: Decision; toClassroomId?: string }[] = Array.isArray(body.moves) ? body.moves : [];
    if (!moves.length) return NextResponse.json({ error: "moves[] required" }, { status: 400 });

    const nextGrade = nextGradeLevel(classroom.gradeLevel);
    const schoolClassrooms = await db.classroom.findMany({ where: { schoolId }, select: { id: true, name: true, gradeLevel: true } });

    const moved: { name: string; to: string }[] = [];
    const graduated: { name: string }[] = [];
    const stamped: { name: string; decision: Decision }[] = [];
    const skipped: { name: string; reason: string }[] = [];

    for (const mv of moves) {
      const studentId = S(mv.studentId);
      const decision = S(mv.decision) as Decision;
      if (!studentId || !rosterIds.has(studentId) || !DECISIONS.includes(decision)) continue;
      const name = nameOf(studentId);

      // stamp the decision on the card first — even stayers keep their verdict
      await db.reportCard.updateMany({ where: { termId, studentId }, data: { promotion: decision } });

      if (decision !== "PROMOTED") {
        stamped.push({ name, decision });
        continue;
      }

      const en = await db.enrollment.findFirst({ where: { studentId, schoolId, status: "ACTIVE" }, include: { classroom: true } });
      if (!en) { skipped.push({ name, reason: "No active placement" }); continue; }

      // explicit destination (school-checked) beats the auto map
      let dest = mv.toClassroomId
        ? schoolClassrooms.find((c) => c.id === S(mv.toClassroomId)) ?? null
        : null;
      if (dest && dest.id === en.classroomId) dest = null; // moving to self is a no-op
      const isExit = nextGrade === null; // top of the ladder (e.g. JHS 3 / B9)

      if (dest) {
        await db.enrollment.update({
          where: { id: en.id },
          data: { classroomId: dest.id, promotedFromName: en.classroom.name, promotedAt: new Date() },
        });
        moved.push({ name, to: dest.name });
      } else if (isExit) {
        await db.enrollment.update({
          where: { id: en.id },
          data: {
            status: "GRADUATED", exitDate: new Date(),
            exitReason: `Graduated from ${en.classroom.name} (promotion cycle)`,
            promotedFromName: en.classroom.name, promotedAt: new Date(),
          },
        });
        graduated.push({ name });
      } else {
        skipped.push({ name, reason: `No destination class — the next grade (${nextGrade}) has no classroom here` });
      }
    }

    const onRoll = await db.enrollment.count({ where: { schoolId, status: "ACTIVE" } });
    await db.school.update({ where: { id: schoolId }, data: { studentsOnRoll: onRoll } });

    return NextResponse.json({
      ok: true,
      moved, graduated, stamped, skipped,
      onRoll,
      classroom: { name: classroom.name },
    });
  }

  return NextResponse.json({ error: "Unknown action — use decide or rollover" }, { status: 400 });
}

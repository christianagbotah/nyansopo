import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { audit } from "@/lib/audit";
import { sendSms } from "@/lib/sms";

export const dynamic = "force-dynamic";

// ── POST /api/school/[id]/ops/homework — wave 65 ─────────────────────────
// The daily teacher workflow (the last core LMS surface the app lacked):
//   GET  ?view=list                       — homework board w/ submission stats
//   GET  ?view=one&id=…                   — one assignment + full submission grid
//   POST homework.create                  — post to a class (seeds one PENDING
//                                           submission per ACTIVE learner) and
//                                           SMS-fans-out to parents instantly
//   POST homework.ai-draft                — AI drafts title/instructions/rubric
//                                           from a topic (deterministic fallback)
//   POST homework.bulk-mark               — tick handed-in / score / feedback
//                                           (late auto-detected against dueDate)
//   POST homework.push-gradebook          — one tap → Assessment(type HOMEWORK)
//                                           + Mark rows (idempotent per title)
//   POST homework.remind                  — SMS parents of learners still PENDING
//   POST homework.close / homework.reopen — freeze / unfreeze the board
// Parents/learners act through /ops/portal (homework.self-submit).

type HwMark = { studentId: string; status?: string; score?: number | null; feedback?: string | null };

const DAY_MS = 86400000;
const hwRef = (h: { title: string; classroom: { name: string }; dueDate: Date }) =>
  `${h.title} — ${h.classroom.name} (due ${h.dueDate.toISOString().slice(0, 10)})`;

/** AI homework draft: topic → { title, instructions, maxScore }. Falls back to
 *  a solid deterministic template when the AI service is unreachable. */
function draftFallback(topic: string, subject: string, klass: string): { title: string; instructions: string; maxScore: number } {
  return {
    title: `${subject}: ${topic}`.slice(0, 90),
    instructions:
      `Answer ALL questions in your ${subject} exercise book. Write your name, class (${klass}) and the date at the top. ` +
      `Show all working — marks are awarded for correct method as well as the final answer. ` +
      `Submit on or before the due date; late work will be recorded as late.`,
    maxScore: 10,
  };
}

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["homework.view"] });
  if (!auth.ok) return auth.res;
  const { searchParams } = new URL(req.url);
  const view = searchParams.get("view") ?? "list";

  if (view === "list") {
    const classroomId = searchParams.get("classroomId");
    const where: any = { schoolId };
    if (classroomId) where.classroomId = classroomId;
    const now = new Date();
    const [items, classrooms, subjects, staffId] = await Promise.all([
      db.homework.findMany({
        where,
        orderBy: [{ dueDate: "desc" }, { createdAt: "desc" }],
        take: 120,
        include: {
          classroom: { select: { name: true } },
          subject: { select: { name: true } },
          staff: { select: { fullName: true } },
          _count: { select: { submissions: true } },
        },
      }),
      db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" }, select: { id: true, name: true } }),
      db.subject.findMany({ where: { schoolId }, orderBy: { name: "asc" }, select: { id: true, name: true } }),
      auth.user.staffId,
    ]);
    // per-item submission counts (graded / submitted / pending) in one query
    const ids = items.map((i) => i.id);
    const groups = ids.length
      ? await db.homeworkSubmission.groupBy({ by: ["homeworkId", "status"], where: { homeworkId: { in: ids } }, _count: { _all: true } })
      : [];
    const stat = (hid: string, st: string) => groups.find((g) => g.homeworkId === hid && g.status === st)?._count._all ?? 0;
    const mine = staffId ? items.filter((i) => i.staffId === staffId).length : 0;

    return NextResponse.json({
      items: items.map((h) => ({
        id: h.id, title: h.title, subject: h.subject?.name ?? h.subjectName,
        classroom: h.classroom.name, classroomId: h.classroomId,
        postedBy: h.staff?.fullName ?? h.createdByName, mine: !!staffId && h.staffId === staffId,
        dueDate: h.dueDate, overdue: h.dueDate < now, status: h.status,
        maxScore: h.maxScore, notifyParents: h.notifyParents,
        createdAt: h.createdAt,
        learners: h._count.submissions,
        pending: stat(h.id, "PENDING"), submitted: stat(h.id, "SUBMITTED") + stat(h.id, "LATE"), graded: stat(h.id, "GRADED"),
        late: stat(h.id, "LATE"),
      })),
      classrooms, subjects, mine,
      stats: {
        open: items.filter((i) => i.status === "OPEN").length,
        dueThisWeek: items.filter((i) => i.status === "OPEN" && i.dueDate > now && i.dueDate.getTime() - now.getTime() < 7 * DAY_MS).length,
        overdue: items.filter((i) => i.status === "OPEN" && i.dueDate < now).length,
      },
    });
  }

  if (view === "one") {
    const id = searchParams.get("id") ?? "";
    const hw = await db.homework.findUnique({
      where: { id },
      include: {
        classroom: { select: { name: true } },
        subject: { select: { name: true } },
        staff: { select: { fullName: true } },
        submissions: { include: { student: { select: { firstName: true, lastName: true, nyansapoIndex: true } } } },
      },
    });
    if (!hw || hw.schoolId !== schoolId) return NextResponse.json({ error: "Assignment not found" }, { status: 404 });
    // class roll order for the marking grid
    const roll = await db.enrollment.findMany({
      where: { classroomId: hw.classroomId, status: "ACTIVE" },
      orderBy: { rollNo: "asc" },
      select: { studentId: true, rollNo: true },
    });
    const orderOf = new Map(roll.map((r) => [r.studentId, r.rollNo ?? 9999]));
    const subs = [...hw.submissions].sort((a, b) => (orderOf.get(a.studentId) ?? 9999) - (orderOf.get(b.studentId) ?? 9999));
    // existing gradebook linkage (pushed assessments share the title+classroom+HOMEWORK)
    const pushed = hw.termId
      ? await db.assessment.findFirst({ where: { classroomId: hw.classroomId, type: "HOMEWORK", title: hw.title, termId: hw.termId }, select: { id: true } })
      : null;
    return NextResponse.json({
      homework: {
        id: hw.id, title: hw.title, instructions: hw.instructions, subject: hw.subject?.name ?? hw.subjectName,
        classroom: hw.classroom.name, classroomId: hw.classroomId, postedBy: hw.staff?.fullName ?? hw.createdByName,
        dueDate: hw.dueDate, maxScore: hw.maxScore, status: hw.status, createdAt: hw.createdAt,
        gradebookAssessmentId: pushed?.id ?? null,
      },
      submissions: subs.map((s) => ({
        id: s.id, studentId: s.studentId, student: `${s.student.firstName} ${s.student.lastName}`,
        index: s.student.nyansapoIndex, status: s.status, note: s.note, submittedAt: s.submittedAt,
        score: s.score, feedback: s.feedback, gradedAt: s.gradedAt, gradedByName: s.gradedByName,
      })),
    });
  }

  return NextResponse.json({ error: "Unknown view" }, { status: 400 });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["homework.manage"] });
  if (!auth.ok) return auth.res;
  const { user } = auth;
  const body = await req.json().catch(() => ({}));
  const actorName = user.fullName;
  const staffId = user.staffId ?? null;

  // ── AI draft ────────────────────────────────────────────────────────────
  if (body.action === "homework.ai-draft") {
    const topic = String(body.topic ?? "").trim().slice(0, 200);
    const subject = String(body.subject ?? "").trim().slice(0, 60) || "the subject";
    const klass = String(body.classroom ?? "").trim() || "the class";
    const level = String(body.level ?? "").trim();
    if (!topic) return NextResponse.json({ error: "Give the AI a topic to work with" }, { status: 400 });
    const dueDays = Math.max(1, Math.min(14, Number(body.dueDays ?? 3)));
    let draft = draftFallback(topic, subject, klass);
    try {
      const { default: ZAI } = await import("z-ai-web-dev-sdk");
      const client = await ZAI.create();
      const completion = await client.chat.completions.create({
        messages: [
          {
            role: "system",
            content:
              `You are a Ghanaian ${level || "basic school"} ${subject} teacher writing homework for ${klass} (Ghana NaCCA SBC style). ` +
              `From the topic, produce: a clear homework title (max 12 words), step-by-step instructions pupils and parents can follow at home ` +
              `(include 3-5 concrete question ideas or tasks appropriate for the class level, mention the exercise book, show-working, and submission), ` +
              `and a fair max score. Simple English, no headings markup, no emoji. Return STRICT JSON only: ` +
              `{"title":"...","instructions":"...","maxScore":10}. Instructions 90-160 words.`,
          },
          { role: "user", content: `Topic: ${topic}. Subject: ${subject}. Class: ${klass}. Due in ${dueDays} day(s).` },
        ],
        thinking: { type: "disabled" },
      });
      const raw = (completion.choices[0]?.message?.content ?? "").replace(/```json|```/gi, "").trim();
      let parsed: any = {};
      try { parsed = JSON.parse(raw); } catch {
        const first = raw.indexOf("{"), last = raw.lastIndexOf("}");
        try { parsed = JSON.parse(raw.slice(first, last + 1)); } catch { /* keep template */ }
      }
      if (typeof parsed?.title === "string" && parsed.title.trim().length > 3 && typeof parsed?.instructions === "string" && parsed.instructions.trim().length > 30) {
        draft = {
          title: parsed.title.trim().slice(0, 90),
          instructions: parsed.instructions.trim().slice(0, 2000),
          maxScore: Math.max(1, Math.min(100, Number(parsed.maxScore) || 10)),
        };
      }
    } catch { /* AI optional — template stands */ }
    return NextResponse.json({ draft });
  }

  // ── post an assignment ──────────────────────────────────────────────────
  if (body.action === "homework.create") {
    const { classroomId, subjectId, subjectName, title, instructions, dueDate, maxScore, notifyParents } = body;
    if (!classroomId || !title || !instructions || !dueDate) {
      return NextResponse.json({ error: "Class, title, instructions and due date are required" }, { status: 400 });
    }
    const klass = await db.classroom.findUnique({ where: { id: classroomId } });
    if (!klass || klass.schoolId !== schoolId) return NextResponse.json({ error: "Class not found" }, { status: 404 });
    const due = new Date(dueDate);
    if (isNaN(due.getTime())) return NextResponse.json({ error: "Due date is not a valid date" }, { status: 400 });
    let subjectLink: { id: string; name: string } | null = null;
    if (subjectId) {
      const s = await db.subject.findUnique({ where: { id: subjectId } });
      if (s && s.schoolId === schoolId) subjectLink = { id: s.id, name: s.name };
    }
    const year = await db.academicYear.findFirst({ where: { schoolId, isCurrent: true }, include: { terms: true } });
    const term = year?.terms.find((t) => t.isCurrent) ?? null;

    const hw = await db.homework.create({
      data: {
        schoolId, classroomId, subjectId: subjectLink?.id ?? null,
        subjectName: subjectLink?.name ?? (typeof subjectName === "string" && subjectName.trim() ? subjectName.trim().slice(0, 60) : "General"),
        staffId, termId: term?.id ?? null,
        title: String(title).trim().slice(0, 120),
        instructions: String(instructions).trim().slice(0, 4000),
        dueDate: due, maxScore: Math.max(1, Math.min(100, Number(maxScore) || 10)),
        notifyParents: notifyParents !== false, createdByName: actorName,
      },
    });

    // seed one PENDING submission per ACTIVE learner (the marking checklist)
    const learners = await db.enrollment.findMany({
      where: { classroomId, status: "ACTIVE" },
      select: { studentId: true, rollNo: true, student: { select: { firstName: true, lastName: true, guardianName: true, guardianPhone: true } } },
      orderBy: { rollNo: "asc" },
    });
    await db.homeworkSubmission.createMany({
      data: learners.map((l) => ({ homeworkId: hw.id, studentId: l.studentId, status: "PENDING" })),
    });

    // instant parent SMS fan-out (the same promise the visitors book made)
    let sent = 0, failed = 0;
    if (hw.notifyParents) {
      const school = await db.school.findUnique({ where: { id: schoolId }, select: { name: true } });
      const dueStr = due.toLocaleDateString("en-GB", { day: "numeric", month: "short" });
      const smsBody =
        `${school?.name ?? "School"}: New homework for ${klass.name} — "${hw.title}" (${hw.subjectName}). ` +
        `Due ${dueStr}. Please ensure your child completes and submits on time. — ${actorName}`.slice(0, 320);
      for (const l of learners) {
        if (!l.student.guardianPhone) continue;
        try {
          const r = await sendSms({
            db, schoolId, studentId: l.studentId,
            recipientName: l.student.guardianName || `${l.student.firstName} ${l.student.lastName}`,
            recipientPhone: l.student.guardianPhone, body: smsBody, category: "HOMEWORK", sentByName: actorName,
          });
          if (r.status === "SENT") sent++; else failed++;
        } catch { failed++; }
      }
    }

    await audit({
      schoolId, actor: { id: user.id, fullName: actorName, role: user.role },
      action: "CREATE", entityType: "Homework", entityId: hw.id,
      entityLabel: hwRef({ ...hw, classroom: klass }),
      summary: `Posted homework "${hw.title}" for ${klass.name} — ${learners.length} learners, due ${due.toISOString().slice(0, 10)}${hw.notifyParents ? `, parents SMS'd (${sent} sent / ${failed} failed)` : ", parent SMS skipped"}`,
      req,
    });
    return NextResponse.json({ homework: { id: hw.id }, learners: learners.length, sms: { sent, failed, skipped: hw.notifyParents ? 0 : learners.length } });
  }

  // ── marking: bulk status/score/feedback updates ─────────────────────────
  if (body.action === "homework.bulk-mark") {
    const homeworkId = String(body.homeworkId ?? "");
    const marks: HwMark[] = Array.isArray(body.marks) ? body.marks : [];
    if (!homeworkId || !marks.length) return NextResponse.json({ error: "homeworkId and marks[] required" }, { status: 400 });
    const hw = await db.homework.findUnique({ where: { id: homeworkId }, include: { classroom: true } });
    if (!hw || hw.schoolId !== schoolId) return NextResponse.json({ error: "Assignment not found" }, { status: 404 });
    const now = new Date();
    const late = now > hw.dueDate;
    let updated = 0;
    for (const m of marks) {
      const data: any = {};
      if (typeof m.score === "number" && !isNaN(m.score)) {
        data.score = Math.max(0, Math.min(hw.maxScore, m.score));
        data.status = "GRADED"; data.gradedAt = now; data.gradedByName = actorName;
      } else if (m.status && m.status !== "PENDING") {
        // handing in after the due date → auto-flag LATE
        data.status = m.status === "SUBMITTED" && late ? "LATE" : m.status;
        if (data.status === "SUBMITTED" || data.status === "LATE") data.submittedAt = now;
      }
      if (m.feedback != null && String(m.feedback).trim()) data.feedback = String(m.feedback).slice(0, 500);
      if (!Object.keys(data).length) continue;
      const r = await db.homeworkSubmission.updateMany({ where: { homeworkId, studentId: m.studentId }, data });
      updated += r.count;
    }
    await audit({
      schoolId, actor: { id: user.id, fullName: actorName, role: user.role },
      action: "UPDATE", entityType: "Homework", entityId: hw.id, entityLabel: hwRef(hw),
      summary: `Marked ${updated} submission(s) on "${hw.title}"`, req,
    });
    return NextResponse.json({ updated });
  }

  // ── push graded scores into the Gradebook (one tap) ─────────────────────
  if (body.action === "homework.push-gradebook") {
    const homeworkId = String(body.homeworkId ?? "");
    const hw = await db.homework.findUnique({ where: { id: homeworkId }, include: { classroom: true } });
    if (!hw || hw.schoolId !== schoolId) return NextResponse.json({ error: "Assignment not found" }, { status: 404 });
    const graded = await db.homeworkSubmission.findMany({ where: { homeworkId, score: { not: null } } });
    if (!graded.length) return NextResponse.json({ error: "No scored submissions yet — mark some scores first." }, { status: 400 });
    // idempotent: reuse an assessment created from this homework (same title+class+type+term)
    let assessment = hw.termId
      ? await db.assessment.findFirst({ where: { classroomId: hw.classroomId, type: "HOMEWORK", title: hw.title, termId: hw.termId } })
      : null;
    if (!assessment) {
      if (!hw.termId) return NextResponse.json({ error: "No current term — cannot file into the gradebook." }, { status: 400 });
      if (!hw.subjectId) return NextResponse.json({ error: "This homework has no catalog subject — gradebook entries need one." }, { status: 400 });
      assessment = await db.assessment.create({
        data: {
          classroomId: hw.classroomId, schoolId, termId: hw.termId, subjectId: hw.subjectId,
          title: hw.title, type: "HOMEWORK", maxScore: hw.maxScore, date: hw.dueDate,
        },
      });
    } else {
      await db.mark.deleteMany({ where: { assessmentId: assessment.id } }); // re-push overwrites
    }
    await db.mark.createMany({ data: graded.map((g) => ({ assessmentId: assessment!.id, studentId: g.studentId, score: g.score! })) });
    await audit({
      schoolId, actor: { id: user.id, fullName: actorName, role: user.role },
      action: "CREATE", entityType: "Assessment", entityId: assessment.id, entityLabel: `${hw.title} (homework → gradebook)`,
      summary: `Pushed ${graded.length} homework score(s) from "${hw.title}" into the Gradebook (assessment ${assessment.id})`, req,
    });
    return NextResponse.json({ assessmentId: assessment.id, marks: graded.length });
  }

  // ── remind parents of learners still pending ────────────────────────────
  if (body.action === "homework.remind") {
    const homeworkId = String(body.homeworkId ?? "");
    const hw = await db.homework.findUnique({ where: { id: homeworkId }, include: { classroom: true } });
    if (!hw || hw.schoolId !== schoolId) return NextResponse.json({ error: "Assignment not found" }, { status: 404 });
    const pending = await db.homeworkSubmission.findMany({
      where: { homeworkId, status: "PENDING" },
      include: { student: { select: { firstName: true, lastName: true, guardianName: true, guardianPhone: true } } },
    });
    const school = await db.school.findUnique({ where: { id: schoolId }, select: { name: true } });
    const dueStr = hw.dueDate.toLocaleDateString("en-GB", { day: "numeric", month: "short" });
    let sent = 0, failed = 0, noPhone = 0;
    for (const p of pending) {
      if (!p.student.guardianPhone) { noPhone++; continue; }
      try {
        const r = await sendSms({
          db, schoolId, studentId: p.studentId,
          recipientName: p.student.guardianName || `${p.student.firstName} ${p.student.lastName}`,
          recipientPhone: p.student.guardianPhone,
          body: `${school?.name ?? "School"} reminder: "${hw.title}" (${hw.subjectName}, due ${dueStr}) is still outstanding for ${p.student.firstName}. Kindly follow up tonight. — ${actorName}`.slice(0, 320),
          category: "HOMEWORK", sentByName: actorName,
        });
        if (r.status === "SENT") sent++; else failed++;
      } catch { failed++; }
    }
    await audit({
      schoolId, actor: { id: user.id, fullName: actorName, role: user.role },
      action: "UPDATE", entityType: "Homework", entityId: hw.id, entityLabel: hwRef(hw),
      summary: `Homework reminder SMS — ${sent} sent / ${failed} failed / ${noPhone} no phone, for "${hw.title}"`, req,
    });
    return NextResponse.json({ sent, failed, noPhone, pending: pending.length });
  }

  // ── close / reopen ──────────────────────────────────────────────────────
  if (body.action === "homework.close" || body.action === "homework.reopen") {
    const homeworkId = String(body.homeworkId ?? "");
    const hw = await db.homework.findUnique({ where: { id: homeworkId }, include: { classroom: true } });
    if (!hw || hw.schoolId !== schoolId) return NextResponse.json({ error: "Assignment not found" }, { status: 404 });
    const status = body.action === "homework.close" ? "CLOSED" : "OPEN";
    await db.homework.update({ where: { id: homeworkId }, data: { status } });
    await audit({
      schoolId, actor: { id: user.id, fullName: actorName, role: user.role },
      action: "UPDATE", entityType: "Homework", entityId: hw.id, entityLabel: hwRef(hw),
      summary: `${status === "CLOSED" ? "Closed" : "Reopened"} homework "${hw.title}"`, req,
    });
    return NextResponse.json({ status });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

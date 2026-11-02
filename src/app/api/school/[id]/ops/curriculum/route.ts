import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

// ── Curriculum & Lesson Notes (iEduPro lesson-note lifecycle parity)
//    GET  ?tab=overview (default) — catalogues, notes, templates, review queue, completion
//         ?tab=syllabus&classroomId=&termId=&subjectId= — scheme-of-learning topics
//         ?tab=materials — shared study material
//    POST note.save | note.delete | note.copy | note.review |
//         template.save | template.delete | syllabus.save | syllabus.delete |
//         material.save | material.delete ──

const WEEK_MIN = 1, WEEK_MAX = 15;

async function currentTerm(schoolId: string) {
  return (await db.term.findFirst({
    where: { academicYear: { schoolId }, isCurrent: true },
    include: { academicYear: true },
    orderBy: { sequence: "desc" },
  })) ?? (await db.term.findFirst({
    where: { academicYear: { schoolId } },
    orderBy: [{ academicYear: { startsAt: "desc" } }, { sequence: "desc" }],
    include: { academicYear: true },
  }));
}

/** Weeks elapsed in a term (1-indexed), capped at the term length. */
function weeksElapsed(term: { startsAt: Date; endsAt: Date } | null): number {
  if (!term) return 0;
  const now = Date.now();
  if (now <= term.startsAt.getTime()) return 1;
  const total = Math.max(1, Math.round((term.endsAt.getTime() - term.startsAt.getTime()) / (7 * 864e5)));
  const gone = Math.floor((now - term.startsAt.getTime()) / (7 * 864e5)) + 1;
  return Math.min(Math.max(gone, 1), total);
}

const noteSelect = {
  id: true, staffId: true, classroomId: true, subjectId: true, termId: true, templateId: true,
  title: true, description: true, weekNumber: true, lessonDate: true, objectives: true,
  activities: true, content: true, status: true, feedback: true, version: true, sourceNoteId: true,
  submittedAt: true, reviewedAt: true, reviewedByName: true, createdAt: true, updatedAt: true,
  staff: { select: { fullName: true } },
  classroom: { select: { name: true, gradeLevel: true } },
  subject: { select: { name: true } },
  reviews: { orderBy: { createdAt: "asc" as const }, select: { id: true, action: true, actorName: true, comment: true, createdAt: true } },
};

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["curriculum.view"] });
  if (!auth.ok) return auth.res;
  const url = new URL(req.url);
  const tab = url.searchParams.get("tab") ?? "overview";
  const canReview = auth.user.permissions.includes("curriculum.review");

  if (tab === "syllabus") {
    const term = await currentTerm(schoolId);
    const classroomId = url.searchParams.get("classroomId") ?? "";
    const subjectId = url.searchParams.get("subjectId") ?? "";
    if (!term || !classroomId) return NextResponse.json({ topics: [], coverage: null });
    const topics = await db.syllabusTopic.findMany({
      where: { schoolId, classroomId, termId: term.id, ...(subjectId ? { subjectId } : {}) },
      include: { subject: { select: { name: true } } },
      orderBy: [{ subject: { name: "asc" } }, { weekNumber: "asc" }, { topic: "asc" }],
    });
    const done = topics.filter((t) => t.completed).length;
    return NextResponse.json({
      term: { id: term.id, name: term.name },
      topics: topics.map((t) => ({
        id: t.id, subjectId: t.subjectId, subject: t.subject.name, weekNumber: t.weekNumber,
        strand: t.strand, subStrand: t.subStrand, contentStandard: t.contentStandard,
        indicator: t.indicator, topic: t.topic, completed: t.completed, completedAt: t.completedAt,
      })),
      coverage: { total: topics.length, completed: done, pct: topics.length ? Math.round((done / topics.length) * 100) : 0 },
    });
  }

  if (tab === "materials") {
    const materials = await db.studyMaterial.findMany({
      where: { schoolId },
      include: {
        classroom: { select: { name: true } },
        subject: { select: { name: true } },
        author: { select: { fullName: true } },
      },
      orderBy: { createdAt: "desc" },
      take: 200,
    });
    return NextResponse.json({
      materials: materials.map((m) => ({
        id: m.id, classroomId: m.classroomId, classroom: m.classroom.name,
        subjectId: m.subjectId, subject: m.subject?.name ?? null,
        title: m.title, description: m.description, linkUrl: m.linkUrl, body: m.body,
        authorStaffId: m.authorStaffId, author: m.author.fullName, createdAt: m.createdAt,
      })),
    });
  }

  // ── overview: everything the module needs on load ──
  const [classrooms, subjects, terms, term] = await Promise.all([
    db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" }, select: { id: true, name: true, gradeLevel: true, classTeacherId: true } }),
    db.subject.findMany({ where: { schoolId }, orderBy: { name: "asc" }, select: { id: true, name: true, code: true } }),
    db.term.findMany({
      where: { academicYear: { schoolId } },
      orderBy: [{ academicYear: { startsAt: "desc" } }, { sequence: "desc" }],
      select: { id: true, name: true, isCurrent: true, startsAt: true, endsAt: true, academicYear: { select: { name: true } } },
    }),
    currentTerm(schoolId),
  ]);

  const myStaffId = auth.user.staffId ?? null;
  // reviewers & admins see every note; plain teachers only their own
  const seeAll = canReview || ["SUPER_ADMIN", "GROUP_OWNER", "SCHOOL_ADMIN"].includes(auth.user.role);
  const notes = await db.lessonNote.findMany({
    where: { schoolId, ...(seeAll ? {} : { staffId: myStaffId ?? "none" }) },
    select: noteSelect,
    orderBy: [{ lessonDate: "desc" }, { createdAt: "desc" }],
    take: 400,
  });
  const pendingReview = seeAll ? notes.filter((n) => n.status === "PENDING") : [];

  const templates = await db.lessonNoteTemplate.findMany({
    where: { schoolId },
    include: { subject: { select: { name: true } }, classroom: { select: { name: true } } },
    orderBy: { name: "asc" },
  });

  // ── teacher completion dashboard (iEduPro Teacher_completion parity) ──
  //    per staff: classes taught, syllabus coverage, note compliance this term.
  const weeks = weeksElapsed(term);
  const [topics, notesAll, teachingStaff] = await Promise.all([
    db.syllabusTopic.findMany({ where: { schoolId, ...(term ? { termId: term.id } : {}) }, select: { classroomId: true, subjectId: true, completed: true } }),
    term ? db.lessonNote.findMany({ where: { schoolId, termId: term.id }, select: { staffId: true, classroomId: true, subjectId: true, weekNumber: true, status: true } }) : Promise.resolve([] as { staffId: string; classroomId: string; subjectId: string; weekNumber: number; status: string }[]),
    db.staff.findMany({ where: { schoolId, category: "TEACHING", active: true }, select: { id: true, fullName: true, staffCode: true } }),
  ]);
  const staffStats = teachingStaff.map((s) => {
    const myNotes = notesAll.filter((n) => n.staffId === s.id);
    const pairs = new Set(myNotes.map((n) => `${n.classroomId}:${n.subjectId}`));
    const pairTopics = topics.filter((t) => pairs.has(`${t.classroomId}:${t.subjectId}`));
    const covered = pairTopics.filter((t) => t.completed).length;
    const endorsed = myNotes.filter((n) => n.status === "ENDORSED").length;
    const pending = myNotes.filter((n) => n.status === "PENDING").length;
    const revisions = myNotes.filter((n) => n.status === "REVISION_REQUIRED").length;
    const distinctWeeks = new Set(myNotes.filter((n) => n.status !== "DRAFT").map((n) => n.weekNumber)).size;
    return {
      staffId: s.id, name: s.fullName, staffCode: s.staffCode,
      classSubjectPairs: pairs.size, notes: myNotes.length, endorsed, pending, revisions,
      weeksCovered: distinctWeeks, weeksElapsed: weeks,
      compliancePct: weeks ? Math.min(100, Math.round((distinctWeeks / weeks) * 100)) : 0,
      syllabusTotal: pairTopics.length, syllabusCompleted: covered,
      syllabusPct: pairTopics.length ? Math.round((covered / pairTopics.length) * 100) : null,
    };
  }).filter((s) => s.classSubjectPairs > 0 || s.notes > 0);

  const statusCounts = {
    DRAFT: notes.filter((n) => n.status === "DRAFT").length,
    PENDING: notes.filter((n) => n.status === "PENDING").length,
    ENDORSED: notes.filter((n) => n.status === "ENDORSED").length,
    REVISION_REQUIRED: notes.filter((n) => n.status === "REVISION_REQUIRED").length,
  };

  return NextResponse.json({
    canReview: seeAll,
    myStaffId,
    term: term ? { id: term.id, name: term.name, startsAt: term.startsAt, endsAt: term.endsAt, weeksElapsed: weeks } : null,
    classrooms, subjects,
    terms: terms.map((t) => ({ id: t.id, name: `${t.name} — ${t.academicYear.name}`, isCurrent: t.isCurrent })),
    notes,
    templates: templates.map((t) => ({
      id: t.id, name: t.name, subjectId: t.subjectId, subject: t.subject?.name ?? null,
      classroomId: t.classroomId, classroom: t.classroom?.name ?? null,
      strand: t.strand, subStrand: t.subStrand, contentStandard: t.contentStandard,
      description: t.description, objectives: t.objectives, activities: t.activities, content: t.content,
    })),
    pendingReview: pendingReview.length,
    statusCounts,
    staffStats,
  });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  const auth = await opsAuth(req, schoolId, "POST", {
    perms: action === "note.review" ? ["curriculum.review"] : ["curriculum.manage"],
  });
  if (!auth.ok) return auth.res;
  const user = auth.user;
  const actorName = user.fullName;

  if (action === "note.save") {
    const { id, classroomId, subjectId, termId, title, description, weekNumber, lessonDate, objectives, activities, content, status, templateId } = body;
    if (!classroomId || !subjectId || !termId || !title) return NextResponse.json({ error: "Class, subject, term and title are required" }, { status: 400 });
    const wk = Number(weekNumber);
    if (!Number.isInteger(wk) || wk < WEEK_MIN || wk > WEEK_MAX) return NextResponse.json({ error: `Week must be between ${WEEK_MIN} and ${WEEK_MAX}` }, { status: 400 });
    const when = lessonDate ? new Date(lessonDate) : new Date();
    if (isNaN(when.getTime())) return NextResponse.json({ error: "Invalid lesson date" }, { status: 400 });
    const [cls, subj, term] = await Promise.all([
      db.classroom.findUnique({ where: { id: classroomId } }),
      db.subject.findUnique({ where: { id: subjectId } }),
      db.term.findUnique({ where: { id: termId }, include: { academicYear: true } }),
    ]);
    if (!cls || cls.schoolId !== schoolId) return NextResponse.json({ error: "Class not found" }, { status: 404 });
    if (!subj || subj.schoolId !== schoolId) return NextResponse.json({ error: "Subject not found" }, { status: 404 });
    if (!term || term.academicYear.schoolId !== schoolId) return NextResponse.json({ error: "Term not found" }, { status: 404 });
    if (!user.staffId) return NextResponse.json({ error: "This account is not linked to a staff record" }, { status: 403 });

    if (id) {
      const existing = await db.lessonNote.findUnique({ where: { id } });
      if (!existing || existing.schoolId !== schoolId) return NextResponse.json({ error: "Lesson note not found" }, { status: 404 });
      if (existing.staffId !== user.staffId) return NextResponse.json({ error: "You can only edit your own lesson notes" }, { status: 403 });
      if (existing.status === "ENDORSED") return NextResponse.json({ error: "Endorsed notes are immutable — copy it to a new week instead" }, { status: 409 });
      // a returned note being reworked goes back to the review queue
      const nextStatus = existing.status === "REVISION_REQUIRED" ? "PENDING" : existing.status;
      const note = await db.$transaction(async (tx) => {
        const note = await tx.lessonNote.update({
          where: { id },
          data: {
            classroomId, subjectId, termId, title, description: description ?? null,
            weekNumber: wk, lessonDate: when,
            objectives: objectives ?? "", activities: activities ?? "", content: content ?? "",
            status: nextStatus,
            version: { increment: 1 },
            submittedAt: nextStatus === "PENDING" ? new Date() : existing.submittedAt,
          },
        });
        await tx.lessonNoteReview.create({
          data: {
            noteId: note.id,
            action: existing.status === "REVISION_REQUIRED" ? "RESUBMIT" : "UPDATE",
            actorName,
            comment: existing.status === "REVISION_REQUIRED" ? "Revised after reviewer feedback" : null,
          },
        });
        return note;
      });
      return NextResponse.json({ note });
    }

    const initialStatus = status === "DRAFT" ? "DRAFT" : "PENDING";
    try {
      const note = await db.$transaction(async (tx) => {
        const note = await tx.lessonNote.create({
          data: {
            schoolId, staffId: user.staffId!, classroomId, subjectId, termId,
            templateId: templateId ?? null, title, description: description ?? null,
            weekNumber: wk, lessonDate: when,
            objectives: objectives ?? "", activities: activities ?? "", content: content ?? "",
            status: initialStatus,
            submittedAt: initialStatus === "PENDING" ? new Date() : null,
          },
        });
        await tx.lessonNoteReview.create({
          data: { noteId: note.id, action: initialStatus === "DRAFT" ? "CREATE" : "SUBMIT", actorName, comment: null },
        });
        return note;
      });
      return NextResponse.json({ note });
    } catch (e: any) {
      if (String(e?.code) === "P2002") {
        return NextResponse.json({ error: "You already have a note for this class, subject and week — edit it instead" }, { status: 409 });
      }
      throw e;
    }
  }

  if (action === "note.delete") {
    const note = await db.lessonNote.findUnique({ where: { id: body.id } });
    if (!note || note.schoolId !== schoolId) return NextResponse.json({ error: "Lesson note not found" }, { status: 404 });
    const isOwner = note.staffId === user.staffId;
    const canReview = user.permissions.includes("curriculum.review");
    if (!isOwner && !canReview) return NextResponse.json({ error: "You can only delete your own lesson notes" }, { status: 403 });
    if (note.status === "ENDORSED" && !canReview) return NextResponse.json({ error: "Endorsed notes cannot be deleted" }, { status: 409 });
    await db.lessonNote.delete({ where: { id: note.id } }); // reviews cascade
    return NextResponse.json({ ok: true });
  }

  if (action === "note.copy") {
    const src = await db.lessonNote.findUnique({ where: { id: body.id } });
    if (!src || src.schoolId !== schoolId) return NextResponse.json({ error: "Lesson note not found" }, { status: 404 });
    const wk = Number(body.weekNumber ?? src.weekNumber);
    if (!Number.isInteger(wk) || wk < WEEK_MIN || wk > WEEK_MAX) return NextResponse.json({ error: `Week must be between ${WEEK_MIN} and ${WEEK_MAX}` }, { status: 400 });
    if (!user.staffId) return NextResponse.json({ error: "This account is not linked to a staff record" }, { status: 403 });
    try {
      const note = await db.lessonNote.create({
        data: {
          schoolId, staffId: user.staffId, classroomId: body.classroomId ?? src.classroomId,
          subjectId: body.subjectId ?? src.subjectId, termId: body.termId ?? src.termId,
          title: body.title ?? `${src.title} (copy)`, description: src.description,
          weekNumber: wk, lessonDate: new Date(), objectives: src.objectives,
          activities: src.activities, content: src.content,
          status: "DRAFT", sourceNoteId: src.id,
        },
      });
      await db.lessonNoteReview.create({ data: { noteId: note.id, action: "CREATE", actorName, comment: `Copied from week ${src.weekNumber} note` } });
      return NextResponse.json({ note });
    } catch (e: any) {
      if (String(e?.code) === "P2002") return NextResponse.json({ error: "A note already exists for that class, subject and week" }, { status: 409 });
      throw e;
    }
  }

  // HOD / head-teacher review: endorse or return with feedback
  if (action === "note.review") {
    const note = await db.lessonNote.findUnique({ where: { id: body.id } });
    if (!note || note.schoolId !== schoolId) return NextResponse.json({ error: "Lesson note not found" }, { status: 404 });
    if (note.status !== "PENDING") return NextResponse.json({ error: "Only notes awaiting review can be actioned" }, { status: 400 });
    const decision = body.decision === "ENDORSE" ? "ENDORSE" : body.decision === "REQUEST_REVISION" ? "REQUEST_REVISION" : null;
    if (!decision) return NextResponse.json({ error: "decision must be ENDORSE or REQUEST_REVISION" }, { status: 400 });
    const comment: string | null = typeof body.comment === "string" && body.comment.trim() ? body.comment.trim() : null;
    if (decision === "REQUEST_REVISION" && !comment) return NextResponse.json({ error: "A comment is required when returning a note" }, { status: 400 });
    const updated = await db.$transaction(async (tx) => {
      const updated = await tx.lessonNote.update({
        where: { id: note.id },
        data: {
          status: decision === "ENDORSE" ? "ENDORSED" : "REVISION_REQUIRED",
          feedback: decision === "REQUEST_REVISION" ? comment : note.feedback,
          reviewedAt: new Date(), reviewedByName: actorName,
        },
      });
      await tx.lessonNoteReview.create({ data: { noteId: note.id, action: decision, actorName, comment } });
      return updated;
    });
    return NextResponse.json({ note: updated });
  }

  // ── templates ──
  if (action === "template.save") {
    const { id, name, subjectId, classroomId, strand, subStrand, contentStandard, description, objectives, activities, content } = body;
    if (!name) return NextResponse.json({ error: "Template name is required" }, { status: 400 });
    if (subjectId) {
      const subj = await db.subject.findUnique({ where: { id: subjectId } });
      if (!subj || subj.schoolId !== schoolId) return NextResponse.json({ error: "Subject not found" }, { status: 404 });
    }
    if (classroomId) {
      const cls = await db.classroom.findUnique({ where: { id: classroomId } });
      if (!cls || cls.schoolId !== schoolId) return NextResponse.json({ error: "Class not found" }, { status: 404 });
    }
    const data = {
      name, subjectId: subjectId ?? null, classroomId: classroomId ?? null,
      strand: strand ?? null, subStrand: subStrand ?? null, contentStandard: contentStandard ?? null,
      description: description ?? null, objectives: objectives ?? "", activities: activities ?? "", content: content ?? "",
    };
    if (id) {
      const existing = await db.lessonNoteTemplate.findUnique({ where: { id } });
      if (!existing || existing.schoolId !== schoolId) return NextResponse.json({ error: "Template not found" }, { status: 404 });
      const template = await db.lessonNoteTemplate.update({ where: { id }, data });
      return NextResponse.json({ template });
    }
    const template = await db.lessonNoteTemplate.create({ data: { schoolId, ...data } });
    return NextResponse.json({ template });
  }

  if (action === "template.delete") {
    const t = await db.lessonNoteTemplate.findUnique({ where: { id: body.id } });
    if (!t || t.schoolId !== schoolId) return NextResponse.json({ error: "Template not found" }, { status: 404 });
    await db.lessonNoteTemplate.delete({ where: { id: t.id } });
    return NextResponse.json({ ok: true });
  }

  // ── scheme of learning (syllabus topics) ──
  if (action === "syllabus.save") {
    const { id, classroomId, subjectId, termId, weekNumber, topic, strand, subStrand, contentStandard, indicator, completed } = body;
    const wk = Number(weekNumber);
    if (!Number.isInteger(wk) || wk < WEEK_MIN || wk > WEEK_MAX) return NextResponse.json({ error: `Week must be between ${WEEK_MIN} and ${WEEK_MAX}` }, { status: 400 });
    if (!topic || !topic.trim()) return NextResponse.json({ error: "Topic is required" }, { status: 400 });
    if (!id && (!classroomId || !subjectId || !termId)) return NextResponse.json({ error: "Class, subject and term are required" }, { status: 400 });
    if (classroomId) {
      const cls = await db.classroom.findUnique({ where: { id: classroomId } });
      if (!cls || cls.schoolId !== schoolId) return NextResponse.json({ error: "Class not found" }, { status: 404 });
    }
    try {
      let saved;
      if (id) {
        // toggle / edit an existing topic — plain update (create branch would
        // need termId which the toggle payload deliberately omits)
        const existing = await db.syllabusTopic.findUnique({ where: { id } });
        if (!existing || existing.schoolId !== schoolId) return NextResponse.json({ error: "Topic not found" }, { status: 404 });
        saved = await db.syllabusTopic.update({
          where: { id },
          data: {
            topic: topic.trim(), strand: strand ?? null, subStrand: subStrand ?? null,
            contentStandard: contentStandard ?? null, indicator: indicator ?? null,
            ...(typeof completed === "boolean" ? { completed, completedAt: completed ? new Date() : null } : {}),
          },
        });
      } else {
        saved = await db.syllabusTopic.upsert({
          where: { classroomId_subjectId_termId_weekNumber_topic: { classroomId, subjectId, termId, weekNumber, topic: topic.trim() } },
          update: {
            topic: topic.trim(), strand: strand ?? null, subStrand: subStrand ?? null,
            contentStandard: contentStandard ?? null, indicator: indicator ?? null,
            ...(typeof completed === "boolean" ? { completed, completedAt: completed ? new Date() : null } : {}),
          },
          create: {
            schoolId, classroomId, subjectId, termId, weekNumber: wk, topic: topic.trim(),
            strand: strand ?? null, subStrand: subStrand ?? null, contentStandard: contentStandard ?? null,
            indicator: indicator ?? null, completed: completed === true, completedAt: completed === true ? new Date() : null,
          },
        });
      }
      return NextResponse.json({ topic: saved });
    } catch (e: any) {
      if (String(e?.code) === "P2002") return NextResponse.json({ error: "That topic already exists for this class, subject and week" }, { status: 409 });
      throw e;
    }
  }

  if (action === "syllabus.delete") {
    const t = await db.syllabusTopic.findUnique({ where: { id: body.id } });
    if (!t || t.schoolId !== schoolId) return NextResponse.json({ error: "Topic not found" }, { status: 404 });
    await db.syllabusTopic.delete({ where: { id: t.id } });
    return NextResponse.json({ ok: true });
  }

  // ── study material ──
  if (action === "material.save") {
    const { id, classroomId, subjectId, title, description, linkUrl, body: bodyText } = body;
    if (!title || !title.trim()) return NextResponse.json({ error: "Title is required" }, { status: 400 });
    if (!classroomId) return NextResponse.json({ error: "Choose a class to share with" }, { status: 400 });
    if (!linkUrl && !bodyText) return NextResponse.json({ error: "Provide a link or some notes for the class" }, { status: 400 });
    const cls = await db.classroom.findUnique({ where: { id: classroomId } });
    if (!cls || cls.schoolId !== schoolId) return NextResponse.json({ error: "Class not found" }, { status: 404 });
    if (subjectId) {
      const subj = await db.subject.findUnique({ where: { id: subjectId } });
      if (!subj || subj.schoolId !== schoolId) return NextResponse.json({ error: "Subject not found" }, { status: 404 });
    }
    if (id) {
      const existing = await db.studyMaterial.findUnique({ where: { id } });
      if (!existing || existing.schoolId !== schoolId) return NextResponse.json({ error: "Material not found" }, { status: 404 });
      if (existing.authorStaffId !== user.staffId && !user.permissions.includes("curriculum.review"))
        return NextResponse.json({ error: "You can only edit your own material" }, { status: 403 });
      const material = await db.studyMaterial.update({
        where: { id },
        data: { classroomId, subjectId: subjectId ?? null, title: title.trim(), description: description ?? null, linkUrl: linkUrl ?? null, body: bodyText ?? null },
      });
      return NextResponse.json({ material });
    }
    if (!user.staffId) return NextResponse.json({ error: "This account is not linked to a staff record" }, { status: 403 });
    const material = await db.studyMaterial.create({
      data: { schoolId, classroomId, subjectId: subjectId ?? null, title: title.trim(), description: description ?? null, linkUrl: linkUrl ?? null, body: bodyText ?? null, authorStaffId: user.staffId },
    });
    return NextResponse.json({ material });
  }

  if (action === "material.delete") {
    const m = await db.studyMaterial.findUnique({ where: { id: body.id } });
    if (!m || m.schoolId !== schoolId) return NextResponse.json({ error: "Material not found" }, { status: 404 });
    if (m.authorStaffId !== user.staffId && !user.permissions.includes("curriculum.review"))
      return NextResponse.json({ error: "You can only delete your own material" }, { status: 403 });
    await db.studyMaterial.delete({ where: { id: m.id } });
    return NextResponse.json({ ok: true });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getUser, canOperateSchool } from "@/lib/session";
import { CURRICULUM_META } from "@/lib/grading";
import { schoolCurricula, frameworkGrade } from "@/lib/curricula";

async function zai() {
  const { default: ZAI } = await import("z-ai-web-dev-sdk");
  return ZAI.create();
}

async function schoolSnapshot(schoolId: string) {
  const school = await db.school.findUnique({ where: { id: schoolId } });
  if (!school) return null;
  const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
  const invoices = await db.invoice.findMany({ where: { schoolId, termId: term?.id } });
  const billed = invoices.reduce((s, i) => s + i.totalCents - i.discountCents, 0);
  const collected = invoices.reduce((s, i) => s + i.paidCents, 0);
  const activeFlags = await db.studentFlag.count({ where: { schoolId, status: "ACTIVE" } });
  const pendingReqs = await db.recordRequest.count({ where: { targetSchoolId: schoolId, status: "PENDING" } });
  const risks = await db.riskScore.findMany({ where: { schoolId, level: { in: ["HIGH", "CRITICAL"] } }, include: { student: true } });
  return {
    school: { name: school.name, curriculum: school.curriculum, region: school.region, town: school.town, studentsOnRoll: school.studentsOnRoll },
    term: term?.name,
    fees: { billedGHS: +(billed / 100).toFixed(2), collectedGHS: +(collected / 100).toFixed(2), arrearsGHS: +((billed - collected) / 100).toFixed(2), collectionRate: billed ? Math.round((collected / billed) * 100) : 0 },
    activeFlags, pendingReqs,
    highRisk: risks.slice(0, 5).map((r) => `${r.student.firstName} ${r.student.lastName} (${r.level}, score ${r.score})`),
  };
}

// ── lesson-note AI context (Ghanaian GES NaCCA SBC/CCP register style) ──

/** first non-empty trimmed string among the candidates */
const pickStr = (...vals: unknown[]): string | undefined =>
  vals.find((v) => typeof v === "string" && v.trim()) as string | undefined;

/** Compact strand › sub-strand › content-standard › indicator tree for a
 *  class + subject across the school's enabled curricula, trimmed to a
 *  prompt-sized payload (same grade-matching as the curriculum-framework route). */
async function lessonFramework(schoolId: string, gradeLevel: string, subjectName: string) {
  const school = await db.school.findUnique({ where: { id: schoolId }, select: { curriculum: true, curricula: true } });
  const enabled = schoolCurricula(school ?? { curriculum: "GES", curricula: null });
  const grades = new Set(enabled.map((c) => frameworkGrade(c, gradeLevel).trim().toUpperCase()));
  // subject-name matching is case-insensitive → canonical library spelling
  const subjects = await db.subject.findMany({ where: { schoolId }, select: { name: true } });
  const wanted = subjectName.trim().toLowerCase();
  const canonical = subjects.find((s) => s.name.trim().toLowerCase() === wanted)?.name ?? subjectName.trim();
  const strands = await db.curriculumStrand.findMany({
    where: { curriculum: { in: enabled }, subjectName: canonical },
    orderBy: { sortOrder: "asc" },
    include: {
      subStrands: {
        orderBy: { sortOrder: "asc" },
        include: { standards: { orderBy: { code: "asc" }, include: { indicators: { orderBy: { sortOrder: "asc" } } } } },
      },
    },
  });
  return strands
    .filter((s) => grades.has(s.gradeLevel.trim().toUpperCase()))
    .slice(0, 8)
    .map((s) => ({
      code: s.code, name: s.name,
      subStrands: s.subStrands.slice(0, 6).map((b) => ({
        code: b.code, name: b.name,
        standards: b.standards.slice(0, 4).map((st) => ({
          code: st.code, statement: st.statement,
          indicators: st.indicators.slice(0, 8).map((i) => ({ code: i.code, statement: i.statement })),
        })),
      })),
    }));
}

/** Recent notes for a class+subject ("you taught X last week" continuity),
 *  preferring the saved strand register text and falling back to framework links. */
async function recentNotes(schoolId: string, classroomId: string, subjectId: string, excludeId?: string) {
  const notes = await db.lessonNote.findMany({
    where: { schoolId, classroomId, subjectId, ...(excludeId ? { id: { not: excludeId } } : {}) },
    orderBy: [{ weekNumber: "desc" }, { createdAt: "desc" }],
    take: 5,
    select: {
      weekNumber: true, title: true, objectives: true,
      strandCode: true, strandName: true, subStrandCode: true, subStrandName: true,
      strand: { select: { code: true, name: true } },
      subStrand: { select: { code: true, name: true } },
    },
  });
  return notes.map((n) => {
    const strand = [pickStr(n.strandCode, n.strand?.code), pickStr(n.strandName, n.strand?.name)].filter(Boolean).join(" ");
    const subStrand = [pickStr(n.subStrandCode, n.subStrand?.code), pickStr(n.subStrandName, n.subStrand?.name)].filter(Boolean).join(" ");
    return {
      week: n.weekNumber,
      strand: strand || null,
      subStrand: subStrand || null,
      title: n.title,
      objectives: n.objectives ? n.objectives.slice(0, 300) : "",
    };
  });
}

/** LLMs occasionally wrap JSON in fences or prose — grab the first {...} block. */
function parseJsonBlock(text: string): any {
  const t = (text ?? "").replace(/```json|```/gi, "").trim();
  try { return JSON.parse(t); } catch { /* fall through */ }
  const first = t.indexOf("{"), last = t.lastIndexOf("}");
  if (first >= 0 && last > first) {
    try { return JSON.parse(t.slice(first, last + 1)); } catch { /* fall through */ }
  }
  return null;
}

/** Typed-string sanitizer for the AI lesson-note field shape. */
function noteFieldsFromAI(parsed: any) {
  const s = (v: unknown, cap = 20000) => (typeof v === "string" ? v.slice(0, cap) : undefined);
  return {
    title: s(parsed.title, 300),
    description: s(parsed.description, 1000),
    objectives: s(parsed.objectives),
    activities: s(parsed.activities),
    content: s(parsed.content),
    tlm: s(parsed.tlm, 2000),
    assessment: s(parsed.assessment, 2000),
    coreCompetencies: s(parsed.coreCompetencies, 2000),
  };
}

/** Resolve the indicator statements ticked on a saved note (indicatorIds JSON). */
async function noteIndicatorStatements(indicatorIds: string | null): Promise<string[]> {
  if (!indicatorIds) return [];
  try {
    const ids = JSON.parse(indicatorIds) as string[];
    if (!Array.isArray(ids) || !ids.length) return [];
    const inds = await db.curriculumIndicator.findMany({ where: { id: { in: ids } } });
    return inds.map((i) => `${i.code ? `${i.code} — ` : ""}${i.statement}`);
  } catch { return []; }
}

// POST /api/ai/[task]  — chat | remark | insights |
//                         lesson-note-generate | lesson-note-refine | curriculum-suggest
export async function POST(req: NextRequest, { params }: { params: Promise<{ task: string }> }) {
  const { task } = await params;
  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });
  const body = await req.json();

  try {
    if (task === "chat") {
      const schoolId = body.schoolId;
      const snap = schoolId && canOperateSchool(user, schoolId) ? await schoolSnapshot(schoolId) : null;
      const sys = `You are the Nyansapo AI Assistant — an expert education-management advisor for GHANAIAN PRIVATE SCHOOLS (GNACOPS ecosystem). You help headteachers, bursars, proprietors and association officers (national → regional → district → zonal) with fees (term-based billing in Ghana Cedis, MoMo payments), defaulters, transfers between schools, the GES Standards-Based/Common-Core curriculum, BECE/WASSCE, Cambridge CAIE and IB pathways, attendance and report cards.
${snap ? `Live school snapshot: ${JSON.stringify(snap)}.` : "The user has not selected a school."}
User: ${user.fullName} — ${user.title ?? user.role}.
Be concise, practical and warm. Use ₵ for money. Ground answers in the snapshot when relevant. Maximum ~180 words unless asked for more.`;
      const client = await zai();
      const completion = await client.chat.completions.create({
        messages: [{ role: "system", content: sys }, ...(body.messages ?? []).slice(-10)],
        thinking: { type: "disabled" },
      });
      const reply = completion.choices[0]?.message?.content ?? "I could not generate a response.";
      const sessionKey = `chat:${user.id}`;
      for (const m of [...(body.messages ?? []).slice(-2), { role: "assistant", content: reply }]) {
        await db.aiChatMessage.create({ data: { sessionKey, role: m.role, content: m.content } });
      }
      return NextResponse.json({ reply });
    }

    if (task === "remark") {
      const rc = await db.reportCard.findUnique({ where: { id: body.reportCardId }, include: { student: true, results: true, term: { include: { academicYear: true } } } });
      if (!rc) return NextResponse.json({ error: "Report card not found" }, { status: 404 });
      const best = [...rc.results].sort((a, b) => (b.total ?? 0) - (a.total ?? 0))[0];
      const worst = [...rc.results].sort((a, b) => (a.total ?? 0) - (b.total ?? 0))[0];
      const prompt = `Write terminal report remarks for a Ghanaian basic-school report card.
Student: ${rc.student.firstName} ${rc.student.lastName} (${rc.student.gender === "M" ? "boy" : "girl"}).
Class: ${rc.classroomName}, ${rc.term.name} ${rc.term.academicYear.name}.
Average: ${rc.average}%, position ${rc.classPosition} of ${rc.classSize}. Conduct: ${rc.conduct ?? "n/a"}.
Strongest subject: ${best?.subjectName} (${best?.total}%). Needs support: ${worst?.subjectName} (${worst?.total}%).
Return STRICT JSON only: {"teacherRemark": "2-3 warm, specific sentences from the class teacher mentioning strengths and one improvement area", "headRemark": "1 encouraging sentence from the head of school"}. No markdown fences.`;
      const client = await zai();
      const completion = await client.chat.completions.create({ messages: [{ role: "user", content: prompt }], thinking: { type: "disabled" } });
      let text = completion.choices[0]?.message?.content ?? "{}";
      text = text.replace(/```json|```/g, "").trim();
      let parsed: any = {};
      try { parsed = JSON.parse(text); } catch { parsed = { teacherRemark: text.slice(0, 400), headRemark: "" }; }
      await db.reportCard.update({ where: { id: rc.id }, data: { teacherRemark: parsed.teacherRemark, headRemark: parsed.headRemark, aiGenerated: true } });
      return NextResponse.json({ teacherRemark: parsed.teacherRemark, headRemark: parsed.headRemark });
    }

    if (task === "insights") {
      const schoolId = body.schoolId;
      if (!schoolId || !canOperateSchool(user, schoolId)) return NextResponse.json({ error: "Forbidden" }, { status: 403 });
      const snap = await schoolSnapshot(schoolId);
      const insights: { severity: string; title: string; detail: string }[] = [];
      if (snap) {
        if (snap.fees.arrearsGHS > 0) insights.push({ severity: snap.fees.collectionRate < 60 ? "high" : "medium", title: `Collection rate at ${snap.fees.collectionRate}%`, detail: `₵${snap.fees.arrearsGHS.toLocaleString()} is still outstanding this term. Schools below 65% by mid-term typically end the term above 15% bad debt — consider a reminder campaign via SMS and a payment-plan window this week.` });
        if (snap.activeFlags > 0) insights.push({ severity: "medium", title: `${snap.activeFlags} active flag${snap.activeFlags > 1 ? "s" : ""} on the network`, detail: "Unresolved defaulter flags appear in the association registry. Resolve flags promptly so transferring learners are not blocked unnecessarily." });
        if (snap.pendingReqs > 0) insights.push({ severity: "medium", title: `${snap.pendingReqs} record request${snap.pendingReqs > 1 ? "s" : ""} awaiting your response`, detail: "GNACOPS etiquette: respond within 3 working days. Slow responses hold up admission decisions across the zone." });
        for (const name of snap.highRisk) insights.push({ severity: "high", title: `High risk: ${name}`, detail: "Fee-default risk model flagged this learner (arrears + attendance + academics). Invite the guardian for a conference before the mid-term break." });
        if (!insights.length) insights.push({ severity: "low", title: "All clear", detail: "No anomalies detected in the current snapshot. Keep up the good work!" });
      }
      // AI narrative
      let narrative = "";
      try {
        const client = await zai();
        const completion = await client.chat.completions.create({
          messages: [
            { role: "system", content: "You are a Ghanaian school-management advisor. Given a school snapshot and rule-based insights, write a 3-sentence advisory note for the headteacher. Be specific and actionable. No preamble." },
            { role: "user", content: JSON.stringify({ snapshot: snap, insights }) },
          ],
          thinking: { type: "disabled" },
        });
        narrative = completion.choices[0]?.message?.content ?? "";
      } catch { narrative = ""; }
      return NextResponse.json({ insights, narrative, snapshot: snap });
    }

    /* ── Draft a full GES-register lesson note. Body { schoolId, lessonNoteId } to
       regenerate an existing note (saved back), or { schoolId, draft: { classroomId,
       subjectId, weekNumber, strandCode, strandName, subStrandCode, subStrandName,
       indicators?, topicHint? } } to fill an open form without saving. ── */
    if (task === "lesson-note-generate") {
      const schoolId = body.schoolId;
      if (!schoolId || !canOperateSchool(user, schoolId)) return NextResponse.json({ error: "Forbidden" }, { status: 403 });
      const draft = body.draft ?? {};
      const note = body.lessonNoteId
        ? await db.lessonNote.findUnique({
            where: { id: body.lessonNoteId },
            include: { classroom: true, subject: true, strand: true, subStrand: true },
          })
        : null;
      if (body.lessonNoteId && (!note || note.schoolId !== schoolId))
        return NextResponse.json({ error: "Lesson note not found" }, { status: 404 });
      if (note?.status === "ENDORSED")
        return NextResponse.json({ error: "Endorsed notes are immutable — copy it to a new week instead" }, { status: 409 });

      const classroomId = note?.classroomId ?? draft.classroomId;
      const subjectId = note?.subjectId ?? draft.subjectId;
      if (!classroomId || !subjectId)
        return NextResponse.json({ error: "A class and subject are required to draft a lesson note" }, { status: 400 });
      const [cls, subj] = await Promise.all([
        db.classroom.findUnique({ where: { id: classroomId } }),
        db.subject.findUnique({ where: { id: subjectId } }),
      ]);
      if (!cls || cls.schoolId !== schoolId) return NextResponse.json({ error: "Class not found" }, { status: 404 });
      if (!subj || subj.schoolId !== schoolId) return NextResponse.json({ error: "Subject not found" }, { status: 404 });

      const week = Number(note?.weekNumber ?? draft.weekNumber ?? 1) || 1;
      const framework = await lessonFramework(schoolId, cls.gradeLevel, subj.name);
      const taught = await recentNotes(schoolId, cls.id, subj.id, note?.id);
      const noteIndicators = await noteIndicatorStatements(note?.indicatorIds ?? null);
      const draftIndicators: string[] = Array.isArray(draft.indicators)
        ? draft.indicators.filter((v: unknown) => typeof v === "string").slice(0, 8)
        : [];
      const focus = {
        strandCode: pickStr(note?.strandCode, note?.strand?.code, draft.strandCode) ?? "",
        strandName: pickStr(note?.strandName, note?.strand?.name, draft.strandName) ?? "",
        subStrandCode: pickStr(note?.subStrandCode, note?.subStrand?.code, draft.subStrandCode) ?? "",
        subStrandName: pickStr(note?.subStrandName, note?.subStrand?.name, draft.subStrandName) ?? "",
        indicators: noteIndicators.length ? noteIndicators : draftIndicators,
        topicHint: pickStr(draft.topicHint, note?.title, note?.description) ?? "",
      };

      const prompt = `Draft a weekly lesson note in the GHANAIAN GES Standards-Based / Common-Core Programme (NaCCA SBC/CCP) lesson-note register style.
Class: ${cls.name} (grade ${cls.gradeLevel}). Subject: ${subj.name}. Week ${week} of the term.
Curriculum framework for this class and subject (strand › sub-strand › content standard › indicators):
${JSON.stringify(framework)}
Recently taught in this class and subject (most recent first): ${JSON.stringify(taught)}
Requested focus: strand ${focus.strandCode || "(choose the logical next)"} ${focus.strandName}; sub-strand ${focus.subStrandCode || "(choose the logical next)"} ${focus.subStrandName}.
Indicators to cover: ${focus.indicators.length ? focus.indicators.join(" | ") : "choose the most logical next indicators from the framework"}
${focus.topicHint ? `Teacher's topic hint: ${focus.topicHint}` : ""}
${note ? `Regenerate this existing note (a fresh, complete draft — not a light edit). Current: title "${note.title}"; objectives: ${note.objectives.slice(0, 400)}; activities: ${note.activities.slice(0, 400)}; content: ${note.content.slice(0, 600)}.` : "No existing note — draft from scratch."}

Write like an experienced Ghanaian class teacher: learner-centred activities (starter → main → plenary), TLMs an ordinary Ghanaian classroom can actually source, core competencies from the NaCCA list, language pitched at the learners' grade level.
Return STRICT JSON only — no markdown fences, no commentary. Exact shape:
{"title":"short lesson title","description":"one-line summary","objectives":"By the end of the lesson, learners will be able to:\n• …\n• …\n• …","activities":"Starter:\n…\nMain activities:\n…\nPlenary / consolidation:\n…","content":"paragraphs of core points and teacher notes","tlm":"teaching & learning materials","assessment":"how learning will be checked","coreCompetencies":"one competency per line","strandCode":"the strand code you planned for (empty if none)","strandName":"…","subStrandCode":"…","subStrandName":"…"}`;

      const client = await zai();
      const completion = await client.chat.completions.create({ messages: [{ role: "user", content: prompt }], thinking: { type: "disabled" } });
      const parsed = parseJsonBlock(completion.choices[0]?.message?.content ?? "");
      if (!parsed || typeof parsed !== "object")
        return NextResponse.json({ error: "The AI returned an unreadable draft — please try again" }, { status: 502 });

      const fields = noteFieldsFromAI(parsed);
      // strand register echo — superset so the form can fill code/name too;
      // fall back to the caller's explicit focus so the register line stays filled
      const strandEcho: Record<string, string> = {};
      const echo = (key: string, aiVal: unknown, fallback: string) => {
        const v = typeof aiVal === "string" ? aiVal.trim().slice(0, 200) : "";
        if (v || fallback) strandEcho[key] = v || fallback;
      };
      echo("strandCode", parsed.strandCode, focus.strandCode);
      echo("strandName", parsed.strandName, focus.strandName);
      echo("subStrandCode", parsed.subStrandCode, focus.subStrandCode);
      echo("subStrandName", parsed.subStrandName, focus.subStrandName);

      if (note) {
        // save the generated fields onto the note — status/submittedAt untouched
        const data: any = { ...strandEcho, version: { increment: 1 } };
        for (const [k, v] of Object.entries(fields)) if (v != null) data[k] = v;
        await db.lessonNote.update({ where: { id: note.id }, data });
      }
      return NextResponse.json({ saved: !!note, ...fields, ...strandEcho });
    }

    /* ── Refine an existing note with a natural-language instruction.
       Body { schoolId, lessonNoteId, instruction } — result saved to the note. ── */
    if (task === "lesson-note-refine") {
      const schoolId = body.schoolId;
      if (!schoolId || !canOperateSchool(user, schoolId)) return NextResponse.json({ error: "Forbidden" }, { status: 403 });
      const instruction = typeof body.instruction === "string" ? body.instruction.trim() : "";
      if (!instruction) return NextResponse.json({ error: "Describe how the note should be refined" }, { status: 400 });
      if (!body.lessonNoteId) return NextResponse.json({ error: "lessonNoteId is required" }, { status: 400 });
      const note = await db.lessonNote.findUnique({
        where: { id: body.lessonNoteId },
        include: { classroom: true, subject: true, strand: true, subStrand: true },
      });
      if (!note || note.schoolId !== schoolId) return NextResponse.json({ error: "Lesson note not found" }, { status: 404 });
      if (note.status === "ENDORSED")
        return NextResponse.json({ error: "Endorsed notes are immutable — copy it to a new week instead" }, { status: 409 });

      const framework = await lessonFramework(schoolId, note.classroom.gradeLevel, note.subject.name);
      const indicators = await noteIndicatorStatements(note.indicatorIds);
      const strandCode = pickStr(note.strandCode, note.strand?.code) ?? "";
      const strandName = pickStr(note.strandName, note.strand?.name) ?? "";
      const subStrandCode = pickStr(note.subStrandCode, note.subStrand?.code) ?? "";
      const subStrandName = pickStr(note.subStrandName, note.subStrand?.name) ?? "";

      const prompt = `Refine an existing weekly lesson note for a Ghanaian basic school, keeping the GES NaCCA SBC/CCP lesson-note register style.
Class: ${note.classroom.name} (grade ${note.classroom.gradeLevel}). Subject: ${note.subject.name}. Week ${note.weekNumber}.
Strand: ${strandCode} ${strandName}; sub-strand: ${subStrandCode} ${subStrandName}. Indicators: ${indicators.join(" | ") || "n/a"}.
Curriculum framework for grounding: ${JSON.stringify(framework)}
Current note — title: ${note.title}; description: ${note.description ?? ""}; objectives: ${note.objectives}; activities: ${note.activities}; content: ${note.content}; TLM: ${note.tlm ?? ""}; assessment: ${note.assessment ?? ""}; core competencies: ${note.coreCompetencies ?? ""}.
Teacher's instruction: ${instruction}
Apply the instruction faithfully; keep every other section in register style and preserve the strand focus.
Return STRICT JSON only — no markdown fences, no commentary. Exact shape (same sections as the current note):
{"title":"…","description":"one line","objectives":"newline-separated","activities":"newline-separated starter → main → plenary","content":"paragraphs","tlm":"…","assessment":"…","coreCompetencies":"one per line"}`;

      const client = await zai();
      const completion = await client.chat.completions.create({ messages: [{ role: "user", content: prompt }], thinking: { type: "disabled" } });
      const parsed = parseJsonBlock(completion.choices[0]?.message?.content ?? "");
      if (!parsed || typeof parsed !== "object")
        return NextResponse.json({ error: "The AI returned an unreadable refinement — please try again" }, { status: 502 });

      const fields = noteFieldsFromAI(parsed);
      const data: any = { version: { increment: 1 } };
      for (const [k, v] of Object.entries(fields)) if (v != null) data[k] = v;
      await db.lessonNote.update({ where: { id: note.id }, data });
      return NextResponse.json({ saved: true, instruction, ...fields });
    }

    /* ── Suggest the next logical strand / sub-strand / indicators for a
       class+subject, grounded in the framework tree and recent notes.
       Body { schoolId, classroomId, subjectId } — nothing is saved. ── */
    if (task === "curriculum-suggest") {
      const schoolId = body.schoolId;
      if (!schoolId || !canOperateSchool(user, schoolId)) return NextResponse.json({ error: "Forbidden" }, { status: 403 });
      if (!body.classroomId || !body.subjectId)
        return NextResponse.json({ error: "classroomId and subjectId are required" }, { status: 400 });
      const [cls, subj] = await Promise.all([
        db.classroom.findUnique({ where: { id: body.classroomId } }),
        db.subject.findUnique({ where: { id: body.subjectId } }),
      ]);
      if (!cls || cls.schoolId !== schoolId) return NextResponse.json({ error: "Class not found" }, { status: 404 });
      if (!subj || subj.schoolId !== schoolId) return NextResponse.json({ error: "Subject not found" }, { status: 404 });

      const framework = await lessonFramework(schoolId, cls.gradeLevel, subj.name);
      const taught = await recentNotes(schoolId, cls.id, subj.id);
      const prompt = `You are a Ghanaian curriculum planning officer for the GES NaCCA Standards-Based / Common-Core Programme.
Class: ${cls.name} (grade ${cls.gradeLevel}). Subject: ${subj.name}.
Curriculum framework (strand › sub-strand › content standard › indicators): ${JSON.stringify(framework)}
Lesson notes already taught in this class and subject (most recent first): ${JSON.stringify(taught)}
Suggest the NEXT logical strand / sub-strand to teach: continue from what was taught — do not repeat a completed sub-strand unless the sequence requires revisiting; move to the next sub-strand within the current strand, or to the next strand when the current one is exhausted. Prefer codes and names copied exactly from the framework.
Return STRICT JSON only — no markdown fences, no commentary. Exact shape:
{"strandCode":"e.g. B4.1","strandName":"e.g. Number","subStrandCode":"e.g. B4.1.2","subStrandName":"…","indicators":["2-4 indicator statements copied from the framework where possible"],"rationale":"1-2 sentences: what came before and why this is next"}`;

      const client = await zai();
      const completion = await client.chat.completions.create({ messages: [{ role: "user", content: prompt }], thinking: { type: "disabled" } });
      const parsed = parseJsonBlock(completion.choices[0]?.message?.content ?? "");
      if (!parsed || typeof parsed !== "object")
        return NextResponse.json({ error: "The AI returned an unreadable suggestion — please try again" }, { status: 502 });
      const clean = (v: unknown, cap = 300) => (typeof v === "string" ? v.trim().slice(0, cap) : "");
      const indicators = Array.isArray(parsed.indicators)
        ? parsed.indicators.filter((v: unknown) => typeof v === "string").slice(0, 6)
        : [];
      return NextResponse.json({
        strandCode: clean(parsed.strandCode),
        strandName: clean(parsed.strandName),
        subStrandCode: clean(parsed.subStrandCode),
        subStrandName: clean(parsed.subStrandName),
        indicators,
        rationale: clean(parsed.rationale, 600),
      });
    }

    return NextResponse.json({ error: "Unknown AI task" }, { status: 404 });
  } catch (e: any) {
    return NextResponse.json({ error: `AI service error: ${e?.message ?? "unknown"}` }, { status: 500 });
  }
}

import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { audit } from "@/lib/audit";

// ── AI Exam Paper Studio (wave 66) ────────────────────────────
// The teacher types what the exam should cover; the AI drafts a COMPLETE
// Ghanaian paper — Section A objectives (A–D options + answer key) and
// Section B theory with mark allocations and answer guides — NaCCA SBC
// style, Ghanaian context. The teacher reviews the draft and then either:
//   • save-paper        → a QuestionPaper in the exam-paper bank (print for
//                         the exam hall from Exams & Reports → Question papers)
//   • save-exam (existing /ops/exams action) → an Online Exam DRAFT the
//                         class sits on devices, auto-marked
// The AI is required for generation (a template exam would be worthless) —
// when the service is unreachable the teacher gets a clear retry message.

const bad = (error: string, status = 400) => NextResponse.json({ error }, { status });

type McqDraft = { prompt: string; A: string; B: string; C: string; D: string; answer: string; marks: number };
type TheoryDraft = { prompt: string; marks: number; guide: string };
type PaperDraft = { title: string; sectionA: McqDraft[]; sectionB: TheoryDraft[] };

const PAPER_TYPES: Record<string, string> = {
  EXERCISE: "a short class exercise",
  CLASS_TEST: "a class test",
  END_OF_TERM_PRACTICE: "an end-of-term practice examination",
  MOCK_BECE: "a BECE-standard mock examination",
};

function validDraft(d: unknown): d is PaperDraft {
  if (!d || typeof d !== "object") return false;
  const x = d as PaperDraft;
  if (typeof x.title !== "string" || x.title.trim().length < 4) return false;
  if (!Array.isArray(x.sectionA) || x.sectionA.length < 1) return false;
  for (const q of x.sectionA) {
    if (typeof q.prompt !== "string" || !q.prompt.trim()) return false;
    for (const o of ["A", "B", "C", "D"] as const) {
      if (typeof q[o] !== "string" || !q[o].trim()) return false;
    }
    if (!["A", "B", "C", "D"].includes(String(q.answer))) return false;
    if (!(Number(q.marks) > 0)) q.marks = 1;
  }
  if (!Array.isArray(x.sectionB)) x.sectionB = [];
  for (const q of x.sectionB) {
    if (typeof q.prompt !== "string" || !q.prompt.trim()) return false;
    if (!(Number(q.marks) > 0)) q.marks = 5;
    if (typeof q.guide !== "string") q.guide = "";
  }
  return true;
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

  /* ── generate: AI drafts the paper ── */
  if (action === "exam-studio.generate") {
    const subject = optStr(body.subjectName);
    const classroomId = optStr(body.classroomId);
    const klassName = optStr(body.classroomName);
    const paperType = String(body.paperType ?? "CLASS_TEST");
    const topics = optStr(body.topics);
    if (!subject || !topics) return bad("Subject and topics are required");
    const numObjective = Math.max(3, Math.min(40, Number(body.numObjective) || 10));
    const numTheory = Math.max(0, Math.min(5, Number(body.numTheory) ?? 2));
    const durationMins = Math.max(10, Math.min(180, Number(body.durationMins) || 45));
    if (!PAPER_TYPES[paperType]) return bad("Unknown paper type");

    const klass = classroomId ? await db.classroom.findFirst({ where: { id: classroomId, schoolId } }) : null;
    const className = klass?.name ?? klassName ?? "the class";

    let draft: PaperDraft | null = null;
    let aiNote = "AI service unreachable — no paper drafted. Try again shortly.";
    try {
      const { default: ZAI } = await import("z-ai-web-dev-sdk");
      const client = await ZAI.create();
      const completion = await client.chat.completions.create({
        messages: [
          {
            role: "system",
            content:
              `You are an experienced Ghanaian ${subject} teacher and exam setter writing ${PAPER_TYPES[paperType]} for ${className} (Ghana NaCCA SBC style). ` +
              `Draft a COMPLETE exam paper on the given topics. ` +
              `SECTION A: exactly ${numObjective} multiple-choice questions, each with options A-D (one clearly correct, three plausible distractors — common misconceptions make the best distractors), the correct answer letter, and marks (1 or 2). ` +
              `SECTION B: exactly ${numTheory} theory/structured questions with mark allocations (${numTheory ? `2-15 marks each, totalling a sensible weight` : "NONE — leave the array empty"}) and a short answer guide for the marking teacher. ` +
              `Use Ghanaian names, places, cedi amounts and contexts where natural. Language level must suit ${className}. ` +
              `No markdown, no headings, no emoji. Return STRICT JSON only: ` +
              `{"title":"...","sectionA":[{"prompt":"...","A":"...","B":"...","C":"...","D":"...","answer":"A","marks":1}],"sectionB":[{"prompt":"...","marks":5,"guide":"..."}]}. ` +
              `The title names the paper (e.g. "${subject} — ${PAPER_TYPES[paperType]}").`,
          },
          { role: "user", content: `Subject: ${subject}. Topics to cover: ${topics}. Duration: ${durationMins} minutes. Paper type: ${PAPER_TYPES[paperType]}.` },
        ],
        thinking: { type: "disabled" },
      });
      const raw = (completion.choices[0]?.message?.content ?? "").replace(/```json|```/gi, "").trim();
      let parsed: unknown = null;
      try { parsed = JSON.parse(raw); } catch {
        const first = raw.indexOf("{"), last = raw.lastIndexOf("}");
        if (first >= 0 && last > first) { try { parsed = JSON.parse(raw.slice(first, last + 1)); } catch { /* below */ } }
      }
      if (validDraft(parsed)) {
        draft = parsed as PaperDraft;
      } else {
        aiNote = "The AI returned an unusable draft — adjust the topic wording and try again.";
      }
    } catch {
      // AI optional — surfaced below as a clear retry message
    }
    if (!draft) return NextResponse.json({ error: aiNote }, { status: 503 });

    const totalMarks = draft.sectionA.reduce((s, q) => s + q.marks, 0) + draft.sectionB.reduce((s, q) => s + q.marks, 0);
    return NextResponse.json({
      draft: { ...draft, totalMarks, durationMins, className, subject },
    });
  }

  /* ── save-paper: render the draft into the exam-paper bank ── */
  if (action === "exam-studio.save-paper") {
    const draft = body.draft as PaperDraft | undefined;
    const classroomId = optStr(body.classroomId);
    const examName = optStr(body.examName);
    const durationMins = Math.max(10, Math.min(180, Number(body.durationMins) || 45));
    const includeScheme = body.includeScheme !== false;
    if (!draft || !validDraft(draft)) return bad("No valid draft to save");
    const cls = classroomId ? await db.classroom.findFirst({ where: { id: classroomId, schoolId } }) : null;
    if (classroomId && !cls) return bad("Classroom not found", 404);

    const school = await db.school.findUnique({ where: { id: schoolId }, select: { name: true } });
    const esc = (s: string) => s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    const subject = optStr(body.subjectName) ?? "Examination";
    const objMarks = draft.sectionA.reduce((s, q) => s + q.marks, 0);
    const thMarks = draft.sectionB.reduce((s, q) => s + q.marks, 0);

    const html = `
<h3>PAPER TITLE — ${esc(draft.title)}</h3>
<p><b>${esc(school?.name ?? "School")}${examName ? ` · ${esc(examName)}` : ""}</b> — ${esc(subject)} · ${esc(cls?.name ?? "")} · Time allowed: ${durationMins} minutes</p>
<p>Answer ALL questions in Section A on the answer sheet provided. ${draft.sectionB.length ? `Answer ${draft.sectionB.length === 1 ? "the question" : `ANY ${Math.max(1, draft.sectionB.length - 1)} of the ${draft.sectionB.length} questions`}: in Section B in your answer booklet.` : ""}</p>
<h3>SECTION A — OBJECTIVE (${objMarks} marks)</h3>
<p>Choose the correct answer from the options A–D.</p>
<ol>
${draft.sectionA.map((q) => `<li>${esc(q.prompt)}<br/>A. ${esc(q.A)} &nbsp;&nbsp; B. ${esc(q.B)} &nbsp;&nbsp; C. ${esc(q.C)} &nbsp;&nbsp; D. ${esc(q.D)} <b>(${q.marks} mark${q.marks === 1 ? "" : "s"})</b></li>`).join("\n")}
</ol>
${draft.sectionB.length ? `
<h3>SECTION B — THEORY (${thMarks} marks)</h3>
<ol>
${draft.sectionB.map((q) => `<li>${esc(q.prompt)} <b>(${q.marks} marks)</b></li>`).join("\n")}
</ol>` : ""}
${includeScheme ? `
<h3>MARKING SCHEME (for the examiner)</h3>
<p><b>Section A answers:</b> ${draft.sectionA.map((q, i) => `${i + 1}. ${esc(q.answer)}`).join(" &nbsp; ")}</p>
${draft.sectionB.length ? `<b>Section B answer guides:</b><ol>${draft.sectionB.map((q) => `<li>${esc(q.guide || "Accept any reasonable answer covering the main points.")} <b>(${q.marks})</b></li>`).join("")}</ol>` : ""}` : ""}
<p><i>— End of paper —</i></p>`.trim();

    const paper = await db.questionPaper.create({
      data: {
        schoolId,
        classroomId: cls?.id ?? (await db.classroom.findFirst({ where: { schoolId } }))!.id,
        title: draft.title.trim().slice(0, 140),
        body: html,
        examName: examName ?? null,
        authorName: user.fullName,
      },
    });
    await audit({
      schoolId, actor: { id: user.id, fullName: user.fullName, role: user.role },
      action: "CREATE", entityType: "QuestionPaper", entityId: paper.id, entityLabel: draft.title,
      summary: `AI Exam Studio drafted "${draft.title}" (${draft.sectionA.length} objectives + ${draft.sectionB.length} theory, ${objMarks + thMarks} marks) into the paper bank`, req,
    });
    return NextResponse.json({ ok: true, paper: { id: paper.id, title: paper.title } });
  }

  return bad("Unknown action");
}

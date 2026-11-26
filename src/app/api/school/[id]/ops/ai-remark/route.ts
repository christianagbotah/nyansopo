import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

// ── AI report-card remark writer (wave 68) ────────────────────
// The class teacher's most human chore — 126 individual remarks a term —
// drafted by the AI from VERIFIED numbers only (average, position, best and
// weakest subjects, attendance, conduct picks). The remark is grounded: the
// model may not invent facts, may only use the figures it is handed, and the
// deterministic fallback composes from the same numbers when the AI service
// is down, so the button never dead-ends.
//   POST remark.draft — { kind: TEACHER | HEAD, student, classroom, numbers… }

const bad = (error: string, status = 400) => NextResponse.json({ error }, { status });
const esc = (s: string) => s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");

type DraftInput = {
  kind?: string;
  studentName?: string;
  gender?: string;
  classroom?: string;
  average?: number | null;
  position?: number | null;
  classSize?: number | null;
  attendancePct?: number | null;
  bestSubjects?: { name: string; grade: string | null }[];
  weakSubjects?: { name: string; grade: string | null }[];
  conductNames?: string[];
  interestName?: string | null;
};

const fmt1 = (n: number) => (Math.round(n * 10) / 10).toFixed(1).replace(/\.0$/, "");

/** Deterministic compose from the same verified numbers — never throws. */
function fallbackRemark(i: DraftInput): string {
  const avg = typeof i.average === "number" ? fmt1(i.average) : null;
  const pos = typeof i.position === "number" && typeof i.classSize === "number" ? `${i.position} of ${i.classSize}` : null;
  const best = i.bestSubjects?.[0]?.name;
  const weak = i.weakSubjects?.[0]?.name;
  const att = typeof i.attendancePct === "number" ? `${Math.round(i.attendancePct)}% attendance` : null;
  const first = i.studentName?.split(" ")[0] ?? "The pupil";
  const isGirl = i.gender === "F";

  if (i.kind === "HEAD") {
    const band = avg === null ? "Satisfactory" : Number(i.average) >= 75 ? "Very good result" : Number(i.average) >= 65 ? "Good result" : Number(i.average) >= 50 ? "Fair result — room to improve" : "A weak term — more effort is needed";
    return `${first} scored ${avg ?? "—"}%${pos ? `, placing ${Number(i.position) === 1 ? "first" : `${i.position}${i.position === 1 ? "st" : i.position === 2 ? "nd" : i.position === 3 ? "rd" : "th"}`} in a class of ${i.classSize}` : ""}. ${band}${att ? `; attendance ${att}` : ""}. Keep supporting the effort at home.`;
  }
  const parts: string[] = [];
  parts.push(avg !== null ? `${first} obtained ${avg}% this term` : `${first} has completed this term's work`);
  if (pos) parts.push(`and placed ${pos}`);
  if (best) parts.push(`Best work showed in ${best}${weak && weak !== best ? `; ${weak} needs more attention` : ""}`);
  if (att) parts.push(`Attendance: ${att}`);
  if (i.conductNames?.length) parts.push(`Conduct: ${i.conductNames.slice(0, 3).join(", ").toLowerCase()}`);
  const closing = Number(i.average ?? 0) >= 65 ? "A pleasing term — keep it up." : Number(i.average ?? 0) >= 50 ? "Work harder next term for a better showing." : `${isGirl ? "She" : "He"} must sit up and make good use of the next term.`;
  return `${parts.join(", ")}. ${closing}`;
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["reports.view"] });
  if (!auth.ok) return auth.res;
  const body = (await req.json().catch(() => ({}))) as Record<string, unknown>;
  if (String(body.action ?? "") !== "remark.draft") return bad("Unknown action");

  const i: DraftInput = {
    kind: String(body.kind ?? "TEACHER") === "HEAD" ? "HEAD" : "TEACHER",
    studentName: typeof body.studentName === "string" ? body.studentName.slice(0, 120) : undefined,
    gender: typeof body.gender === "string" ? body.gender.slice(0, 1) : undefined,
    classroom: typeof body.classroom === "string" ? body.classroom.slice(0, 80) : undefined,
    average: typeof body.average === "number" ? body.average : null,
    position: typeof body.position === "number" ? body.position : null,
    classSize: typeof body.classSize === "number" ? body.classSize : null,
    attendancePct: typeof body.attendancePct === "number" ? body.attendancePct : null,
    bestSubjects: Array.isArray(body.bestSubjects) ? (body.bestSubjects as { name: string; grade: string | null }[]).filter((s) => s?.name).slice(0, 3) : [],
    weakSubjects: Array.isArray(body.weakSubjects) ? (body.weakSubjects as { name: string; grade: string | null }[]).filter((s) => s?.name).slice(0, 3) : [],
    conductNames: Array.isArray(body.conductNames) ? (body.conductNames as string[]).filter((s) => typeof s === "string").slice(0, 5) : [],
    interestName: typeof body.interestName === "string" ? body.interestName.slice(0, 60) : null,
  };
  if (!i.studentName) return bad("studentName is required");

  // numbers snapshot the prompt is grounded on (single source of truth)
  const facts = {
    average: i.average,
    position: i.position,
    classSize: i.classSize,
    attendancePct: i.attendancePct,
    best: i.bestSubjects?.map((s) => `${s.name}${s.grade ? ` (${s.grade})` : ""}`) ?? [],
    weak: i.weakSubjects?.map((s) => `${s.name}${s.grade ? ` (${s.grade})` : ""}`) ?? [],
    conduct: i.conductNames ?? [],
    interest: i.interestName,
  };

  let remark = fallbackRemark(i);
  try {
    const { default: ZAI } = await import("z-ai-web-dev-sdk");
    const client = await ZAI.create();
    const completion = await client.chat.completions.create({
      messages: [
        {
          role: "system",
          content:
            `You are a Ghanaian ${i.classroom ?? "basic school"} ${i.kind === "HEAD" ? "head teacher" : "class teacher"} writing ONE report-card remark for a pupil's terminal report. ` +
            `Ghanaian school-report English — plain, warm, honest; 1-2 sentences (max 45 words); no headings, no quotes, no emoji. ` +
            `Use ONLY the verified facts you are given — never invent marks, positions, subjects or behaviour. ` +
            (i.kind === "HEAD"
              ? `The head teacher's remark judges the overall term and often nudges the home to keep supporting.`
              : `The class teacher's remark speaks about the pupil's work, best and weak subjects, attendance and conduct, and ends with encouragement or a clear push.`),
        },
        { role: "user", content: `Pupil: ${i.studentName}. Verified facts: ${JSON.stringify(facts)}` },
      ],
      thinking: { type: "disabled" },
    });
    const raw = (completion.choices[0]?.message?.content ?? "").replace(/```json|```/gi, "").trim();
    const cleaned = raw.replace(/^["'\s]+|["'\s]+$/g, "").slice(0, 600);
    if (cleaned.length > 15) remark = cleaned;
  } catch {
    // AI down — the deterministic compose stands (same numbers, same honesty)
  }

  return NextResponse.json({ remark, source: remark === fallbackRemark(i) ? "template" : "ai" });
}

import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { audit } from "@/lib/audit";

// ── Exam Hall & Seating Planner (wave 67) ─────────────────────
// The exam-hall logistics that sits beside the AI Paper Studio: pick the
// candidate classes, the hall grid (rows × columns) and a distribution
// strategy — then generate, save and print the seating chart.
//   GET                        — saved plans + classrooms with candidate counts
//   POST hall.generate         — distribute the candidates into the grid
//                                (interleave = round-robin by class so
//                                neighbours are never classmates — the
//                                anti-copying layout Ghanaian halls run on;
//                                or alphabetical in class order)
//   POST hall.save             — persist the plan (audit-trail logged)
//   POST hall.delete           — remove a plan
// Printing is client-side (the plan sheet renders full-page with the school
// header; the Print button prints only the sheet, like the paper bank).

const bad = (error: string, status = 400) => NextResponse.json({ error }, { status });

type SeatEntry = { seat: number; studentId: string; name: string; indexNo: string; className: string };

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["exams.online"] });
  if (!auth.ok) return auth.res;

  const [plans, classrooms] = await Promise.all([
    db.examSeatingPlan.findMany({
      where: { schoolId },
      orderBy: { updatedAt: "desc" },
      take: 60,
      select: { id: true, title: true, examName: true, rows: true, cols: true, interleave: true, classroomNames: true, candidateCount: true, createdBy: true, createdAt: true, updatedAt: true, entriesJson: true },
    }),
    db.classroom.findMany({
      where: { schoolId },
      orderBy: { name: "asc" },
      select: {
        id: true, name: true,
        _count: { select: { enrollments: { where: { status: "ACTIVE" } } } },
      },
    }),
  ]);

  return NextResponse.json({
    plans: plans.map((p) => ({
      id: p.id, title: p.title, examName: p.examName, rows: p.rows, cols: p.cols,
      interleave: p.interleave, classroomNames: p.classroomNames, candidateCount: p.candidateCount,
      createdBy: p.createdBy, createdAt: p.createdAt, updatedAt: p.updatedAt,
      entries: JSON.parse(p.entriesJson) as SeatEntry[],
    })),
    classrooms: classrooms.map((c) => ({ id: c.id, name: c.name, candidates: c._count.enrollments })),
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

  /* ── generate: distribute candidates into the hall grid ── */
  if (action === "hall.generate") {
    const classroomIds = Array.isArray(body.classroomIds) ? (body.classroomIds as string[]).filter((x) => typeof x === "string" && x) : [];
    if (!classroomIds.length) return bad("Pick at least one candidate class");
    const rows = Math.max(1, Math.min(20, Number(body.rows) || 6));
    const cols = Math.max(1, Math.min(20, Number(body.cols) || 6));
    const interleave = body.interleave !== false;

    const enrollments = await db.enrollment.findMany({
      where: { schoolId, status: "ACTIVE", classroomId: { in: classroomIds } },
      include: { student: { select: { id: true, firstName: true, lastName: true, otherNames: true, nyansapoIndex: true } }, classroom: { select: { name: true } } },
      orderBy: { rollNo: "asc" },
    });
    if (!enrollments.length) return bad("The selected classes have no active learners");

    // name rows: last name first (Ghanaian register style), ties by first name
    type Cand = { studentId: string; name: string; indexNo: string; className: string; sort: string };
    const byClass = new Map<string, Cand[]>();
    for (const e of enrollments) {
      const s = e.student;
      const name = `${s.lastName} ${s.firstName}${s.otherNames ? ` ${s.otherNames}` : ""}`.trim();
      const cand: Cand = { studentId: s.id, name, indexNo: s.nyansapoIndex, className: e.classroom.name, sort: `${s.lastName} ${s.firstName}`.toLowerCase() };
      const arr = byClass.get(e.classroom.name) ?? [];
      arr.push(cand);
      byClass.set(e.classroom.name, arr);
    }
    for (const arr of byClass.values()) arr.sort((a, b) => a.sort.localeCompare(b.sort));

    // distribute: interleave = round-robin one candidate per class in turn
    // (neighbours differ); sequential = alphabetical through class by class
    const ordered: Cand[] = [];
    if (interleave) {
      const queues = [...byClass.keys()].sort().map((k) => byClass.get(k)!);
      for (let i = 0; ; i++) {
        let any = false;
        for (const q of queues) {
          if (q[i]) { ordered.push(q[i]); any = true; }
        }
        if (!any) break;
      }
    } else {
      for (const k of [...byClass.keys()].sort()) ordered.push(...byClass.get(k)!);
    }

    const capacity = rows * cols;
    if (ordered.length > capacity) return bad(`${ordered.length} candidates need ${Math.ceil(ordered.length / cols)} rows at ${cols} seats per row — raise the row count`);
    const entries: SeatEntry[] = ordered.slice(0, capacity).map((c, i) => ({
      seat: i + 1, studentId: c.studentId, name: c.name, indexNo: c.indexNo, className: c.className,
    }));

    return NextResponse.json({
      draft: {
        rows, cols, interleave, entries,
        candidateCount: entries.length,
        classroomNames: [...byClass.keys()].sort().join(", "),
        blankSeats: capacity - entries.length,
        byClass: Object.fromEntries([...byClass.entries()].map(([k, v]) => [k, v.length])),
      },
    });
  }

  /* ── save: persist the plan ── */
  if (action === "hall.save") {
    const draft = body.draft as { rows: number; cols: number; interleave: boolean; entries: SeatEntry[]; candidateCount: number; classroomNames?: string } | undefined;
    const title = optStr(body.title);
    const examName = optStr(body.examName);
    if (!draft || !Array.isArray(draft.entries) || !draft.entries.length) return bad("Generate a seating draft first");
    if (!title) return bad("Give the plan a title (e.g. End of Term 1 — Main Hall)");
    const plan = await db.examSeatingPlan.create({
      data: {
        schoolId,
        title: title.slice(0, 140),
        examName: examName,
        rows: Math.max(1, Math.min(20, Number(draft.rows) || 6)),
        cols: Math.max(1, Math.min(20, Number(draft.cols) || 6)),
        interleave: draft.interleave !== false,
        classroomNames: (draft.classroomNames ?? "").slice(0, 300),
        entriesJson: JSON.stringify(draft.entries),
        candidateCount: draft.entries.length,
        createdBy: user.fullName,
      },
    });
    await audit({
      schoolId, actor: { id: user.id, fullName: user.fullName, role: user.role },
      action: "CREATE", entityType: "ExamSeatingPlan", entityId: plan.id, entityLabel: plan.title,
      summary: `Seating plan "${plan.title}" — ${draft.entries.length} candidates, ${plan.rows}×${plan.cols}, ${plan.interleave ? "interleaved (anti-copy)" : "alphabetical"} · ${plan.classroomNames}`, req,
    });
    return NextResponse.json({ ok: true, plan: { id: plan.id, title: plan.title } });
  }

  /* ── delete ── */
  if (action === "hall.delete") {
    const id = optStr(body.id);
    if (!id) return bad("id is required");
    const plan = await db.examSeatingPlan.findFirst({ where: { id, schoolId } });
    if (!plan) return bad("Plan not found", 404);
    await db.examSeatingPlan.delete({ where: { id } });
    await audit({
      schoolId, actor: { id: user.id, fullName: user.fullName, role: user.role },
      action: "DELETE", entityType: "ExamSeatingPlan", entityId: id, entityLabel: plan.title,
      summary: `Deleted seating plan "${plan.title}" (${plan.candidateCount} candidates)`, req,
    });
    return NextResponse.json({ ok: true });
  }

  return bad("Unknown action");
}

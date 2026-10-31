import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getUser, canOperateSchool } from "@/lib/session";
import { CURRICULUM_META } from "@/lib/grading";

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

// POST /api/ai/[task]  — chat | remark | insights
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
Be concise, practical and warm. Use GH₵ for money. Ground answers in the snapshot when relevant. Maximum ~180 words unless asked for more.`;
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
        if (snap.fees.arrearsGHS > 0) insights.push({ severity: snap.fees.collectionRate < 60 ? "high" : "medium", title: `Collection rate at ${snap.fees.collectionRate}%`, detail: `GH₵${snap.fees.arrearsGHS.toLocaleString()} is still outstanding this term. Schools below 65% by mid-term typically end the term above 15% bad debt — consider a reminder campaign via SMS and a payment-plan window this week.` });
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

    return NextResponse.json({ error: "Unknown AI task" }, { status: 404 });
  } catch (e: any) {
    return NextResponse.json({ error: `AI service error: ${e?.message ?? "unknown"}` }, { status: 500 });
  }
}

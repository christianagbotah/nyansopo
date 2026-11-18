/* Wave 25 — discipline & conduct demo register for Golden Excellence Academy.

   Lays down ~90 incidents and ~55 commendations across the school over the
   last 90 school days: a severity ladder, an OPEN → RESOLVED / ESCALATED
   workflow with realistic resolution notes, a handful of guardian-notified
   cases, four repeat offenders (3–4 incidents each) and a merit trail for
   the commendations wall + leaderboard.

   Idempotent: skips when the register already holds rows.
   Run: DATABASE_URL=... bun prisma/seed-discipline.ts */
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

let _s = 20261113;
function rnd() {
  _s |= 0; _s = (_s + 0x6d2b79f5) | 0;
  let t = Math.imul(_s ^ (_s >>> 15), 1 | _s);
  t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
  return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
}
const pick = <T,>(a: T[]) => a[Math.floor(rnd() * a.length)];
const int = (min: number, max: number) => min + Math.floor(rnd() * (max - min + 1));

/** weighted pick: [[item, weight], …] */
function wpick<T>(pairs: [T, number][]): T {
  const total = pairs.reduce((s, [, w]) => s + w, 0);
  let r = rnd() * total;
  for (const [item, w] of pairs) { r -= w; if (r <= 0) return item; }
  return pairs[pairs.length - 1][0];
}

const OFFENCES: [string, number, string][] = [
  // category, weight, severity pool hint
  ["Late coming", 26, "MINOR"],
  ["Classroom disruption", 18, "MINOR"],
  ["Improper uniform", 14, "MINOR"],
  ["Truancy", 10, "MODERATE"],
  ["Phone / gadget", 8, "MODERATE"],
  ["Disrespect to staff", 7, "MODERATE"],
  ["Bullying / fighting", 5, "MAJOR"],
  ["Property damage", 4, "MODERATE"],
  ["Exam malpractice", 3, "MAJOR"],
  ["Theft", 2, "MAJOR"],
];
const DESCRIPTIONS: Record<string, string[]> = {
  "Late coming": [
    "Arrived 20 minutes after the second bell — warned at the gate, third time this month.",
    "Reported after assembly with no note from home; gate register stamped late.",
    "Missed the morning devotion queue; says the bus was full.",
  ],
  "Classroom disruption": [
    "Persistent noise-making during the Maths lesson despite two warnings.",
    "Throwing paper pellets during English comprehension; class had to be paused.",
    "Singing during a quiet reading period and encouraging others to join.",
  ],
  "Improper uniform": [
    "Came without the school belt and in bathroom slippers.",
    "House colours missing at Monday assembly; hair also unkept.",
    "Torn sleeve on the uniform and no PE kit for the double period.",
  ],
  "Truancy": [
    "Skipped the last two periods on Wednesday; found at the nearby pitch.",
    "Absent from the ICT practical without permission; guardian unaware.",
    "Left the compound during break and returned after the closing bell.",
  ],
  "Phone / gadget": [
    "Mobile phone used during class hours; device confiscated and logged.",
    "Earpieces in both ears during the RME lesson; confiscated till term end.",
    "Charging a power bank at the library socket against the school rule.",
  ],
  "Disrespect to staff": [
    "Answered the teacher back rudely when asked to sit down.",
    "Refused to hand over the note to the prefect and muttered insults.",
    "Walked out while the class teacher was addressing the row.",
  ],
  "Bullying / fighting": [
    "Scuffle at the dining queue over a space; both parties separated by the prefect.",
    "Took a JHS 1 learner's pocket money by threat; reported by the classmate.",
    "Fought during break over a football foul — minor bruise on the other learner.",
  ],
  "Property damage": [
    "Broke a laboratory beaker while playing with the rack; admitted fault.",
    "Kicked a classroom door off its stopper; hinge loosened.",
    "Tore a library notice board while hanging on it.",
  ],
  "Exam malpractice": [
    "Notes found under the answer sheet during the class test; paper annulled.",
    "Copied answers from the neighbour's booklet in the mock exam.",
    "Passed a chit with formulas during the objective paper.",
  ],
  "Theft": [
    "Took a classmate's exercise books from the locker; returned after inquiry.",
    "Missing lunch money traced to the learner after the CCTV check at the tuck shop.",
    "Picked an unattended school jersey from the dressing room.",
  ],
};
const RESOLUTIONS = [
  "Counselled by the head; guardian called in and matter closed.",
  "Sanction completed — compound weeding on Friday afternoon.",
  "Apology rendered to the affected party; file closed.",
  "Guardian visited; learner signed the good-conduct undertaking.",
  "Restitution paid in full; case closed with a written warning on file.",
  "Referred to the school counsellor; two sessions completed, improvement noted.",
  "Closed after the class teacher confirmed two weeks of good behaviour.",
];
const COMMENDATIONS: [string, number][] = [
  ["Academic excellence", 20],
  ["Good deed", 16],
  ["Leadership", 12],
  ["Neatness", 12],
  ["Sportsmanship", 10],
  ["Service to school", 10],
  ["Academic improvement", 12],
  ["Perfect score", 8],
];
const COMMEND_DESCS: Record<string, string[]> = {
  "Academic excellence": [
    "Topped the class in the end-of-month test — 94% in Mathematics.",
    "Best Integrated Science project at the science fair; selected for the district round.",
    "Highest cumulative score in the class for the term so far.",
  ],
  "Good deed": [
    "Returned a lost wallet with the full amount to the office and waited for the owner.",
    "Helped a JHS 1 learner who fell sick at the gate to the sick bay.",
    "Covered a sick classmate's class duties for the whole week without being asked.",
  ],
  "Leadership": [
    "Led the class clean-up campaign; the compound won the weekly flag.",
    "As class prefect, restored calm during the teacher's absence without incident.",
    "Organised the study group before the mock examinations.",
  ],
  "Neatness": [
    "Neatest uniform and desk at the weekly inspection, four weeks running.",
    "Recognised at assembly for the tidiest exercise books in the class.",
  ],
  "Sportsmanship": [
    "Captain of the inter-house relay winning team; carried the baton home calmly.",
    "Helped an opponent who tripped during the football final before playing on.",
  ],
  "Service to school": [
    "Volunteered at the open day; guided parents around the science block.",
    "Assisted the librarian with re-shelving every Friday this month.",
  ],
  "Academic improvement": [
    "Moved from the bottom third to the top half of the class in one term.",
    "English composition improved from weak to strong after extra coaching.",
  ],
  "Perfect score": [
    "Scored a perfect 30/30 in the SBA class-work assessment.",
    "Full marks in the objective paper of the class test.",
  ],
};

/** weekdays in the last n days (school days only) */
function schoolDays(n: number): Date[] {
  const out: Date[] = [];
  const d = new Date();
  while (out.length < n) {
    const day = d.getDay();
    if (day !== 0 && day !== 6) out.push(new Date(d));
    d.setDate(d.getDate() - 1);
  }
  return out;
}

async function main() {
  const school = await db.school.findFirst({ where: { name: { contains: "Golden Excellence" } } });
  if (!school) throw new Error("GEA school not found — run the base seed first");

  const existing = await db.disciplineCase.count({ where: { schoolId: school.id } });
  if (existing > 0) {
    console.log(`✅ Discipline seed skipped — register already holds ${existing} case(s)`);
    return;
  }

  const classrooms = await db.classroom.findMany({
    where: { schoolId: school.id },
    select: { id: true, name: true, enrollments: { where: { status: "ACTIVE" }, select: { student: { select: { id: true } } } } },
    orderBy: { name: "asc" },
  });
  const learners = classrooms.flatMap((c) => c.enrollments.map((e) => ({ studentId: e.student.id, classroomId: c.id, classroomName: c.name })));
  if (!learners.length) throw new Error("No active learners found");

  const teachers = await db.staff.findMany({
    where: { schoolId: school.id, category: "TEACHING", active: true },
    select: { fullName: true }, take: 12,
  });
  const recorderNames = teachers.length ? teachers.map((t) => t.fullName) : ["Class Teacher"];

  const days = schoolDays(90); // weekdays, newest first

  /* pick repeat offenders first (4 learners × 3–4 incidents) */
  const offenderIds = new Set<string>();
  while (offenderIds.size < 4) offenderIds.add(pick(learners).studentId);

  const creates: any[] = [];

  /* ── incidents ── */
  const incidentCount = 88;
  for (let i = 0; i < incidentCount; i++) {
    const isRepeat = i < 13; // the repeat offenders get their trail first
    const learner = isRepeat ? learners.find((l) => l.studentId === [...offenderIds][i % 4]) : pick(learners);
    const [category, weight, hint] = pick(OFFENCES);
    void weight;
    const severity = wpick([
      ["MINOR", hint === "MINOR" ? 70 : 30],
      ["MODERATE", hint === "MINOR" ? 26 : 48],
      ["MAJOR", hint === "MAJOR" ? 34 : 8],
    ] as [string, number][]);
    const day = days[Math.min(days.length - 1, isRepeat ? int(2, 60) : int(0, 89))];
    const ageDays = Math.round((Date.now() - day.getTime()) / 86400000);
    // older cases tend to be resolved; fresh ones open; majors escalate
    let status = "OPEN";
    let resolvedAt: Date | null = null;
    let resolutionNote: string | null = null;
    let resolvedByName: string | null = null;
    const resolvedP = ageDays > 21 ? 0.88 : ageDays > 7 ? 0.6 : 0.15;
    if (rnd() < resolvedP) {
      status = "RESOLVED";
      resolvedAt = new Date(day.getTime() + int(1, 5) * 86400000);
      resolutionNote = pick(RESOLUTIONS);
      resolvedByName = pick(recorderNames);
    } else if (severity === "MAJOR" && rnd() < 0.5) {
      status = "ESCALATED";
    }
    const parentNotified = status !== "OPEN" && rnd() < 0.3 && severity !== "MINOR";
    creates.push({
      schoolId: school.id, studentId: learner.studentId,
      kind: "INCIDENT", date: day,
      category, severity,
      description: pick(DESCRIPTIONS[category]),
      /* action taken scales with severity — a minor case never carries a suspension */
      actionTaken: rnd() < 0.85 ? pick(
        severity === "MINOR" ? ["Verbal warning", "Counselled by head", "Sanctioned (supervised task)"]
        : severity === "MODERATE" ? ["Written warning", "Parent called", "Restitution", "Referral to counsellor"]
        : ["Parent called", "Restitution", "Suspension"]
      ) : null,
      status, resolutionNote, resolvedAt, resolvedByName,
      recordedByName: pick(recorderNames),
      parentNotifiedAt: parentNotified ? new Date(day.getTime() + 86400000) : null,
      createdAt: day,
    });
  }

  /* ── commendations ── */
  const commendCount = 56;
  for (let i = 0; i < commendCount; i++) {
    const learner = pick(learners);
    const [category] = wpick(COMMENDATIONS.map((c) => [c, c[1]]) as [string, number][][]);
    const day = days[int(0, 89)];
    creates.push({
      schoolId: school.id, studentId: learner.studentId,
      kind: "COMMENDATION", date: day,
      category,
      description: pick(COMMEND_DESCS[category]),
      actionTaken: rnd() < 0.4 ? "Announced at Monday assembly" : null,
      status: "RESOLVED",
      resolutionNote: null,
      resolvedAt: null,
      resolvedByName: null,
      recordedByName: pick(recorderNames),
      parentNotifiedAt: null,
      createdAt: day,
    });
  }

  /* guarantee at least 3 escalated cases on the head's desk — convert the
     newest unresolved moderate/major incidents if the random rolls came up short */
  let escalatedNow = creates.filter((c) => c.status === "ESCALATED").length;
  if (escalatedNow < 3) {
    for (const c of creates.filter((c) => c.kind === "INCIDENT" && c.status === "OPEN" && c.severity !== "MINOR")) {
      if (escalatedNow >= 3) break;
      c.status = "ESCALATED";
      escalatedNow++;
    }
  }

  await db.disciplineCase.createMany({ data: creates });

  const byKind = creates.reduce((acc, c) => { acc[c.kind] = (acc[c.kind] ?? 0) + 1; return acc; }, {} as Record<string, number>);
  const openCount = creates.filter((c) => c.status === "OPEN").length;
  const escalated = creates.filter((c) => c.status === "ESCALATED").length;
  const notified = creates.filter((c) => c.parentNotifiedAt).length;
  console.log(`✅ Discipline seed complete — ${byKind.INCIDENT} incidents (${openCount} open, ${escalated} escalated, ${notified} guardian-notified) + ${byKind.COMMENDATION} commendations across ${classrooms.length} classes for ${school.name}`);
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

/* Wave 26 — health & wellness clinic demo data for Golden Excellence Academy.

   Lays down the sick-bay register every Ghanaian school nurse keeps:
   ~110 clinic visits across the last 90 school days with a realistic
   complaint mix (malaria/fever dominant in the wet season), triage
   temperatures, treatments, medications, dispositions and the OPEN → CLOSED
   follow-up workflow; guardian SMS-notified sent-home / referral cases; plus
   two SHEP screening events — a completed term screening with height/weight/
   BMI/vision/hearing for most of the school (8 referrals) and an open
   mid-term growth check mid-recording.

   Also enriches ~60 learners with medical background (blood group,
   allergies, conditions, emergency contact) where the Student row is blank.

   Idempotent: skips when the register already holds rows.
   Run: DATABASE_URL=... bun prisma/seed-health.ts */
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

let _s = 20261114;
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

/* category, weight, severity pool, complaint lines, treatment, medication */
type Cat = [string, number, string, string[], string, string];
const CATALOGUE: Cat[] = [
  ["Fever / malaria suspicion", 24, "MODERATE", [
    "Presented hot and weak after morning class; RDT requested from the guardian.",
    "Complained of chills and body aches since yesterday evening.",
    "Temperature 38.6 °C at the gate; guardian asked to pick up for testing.",
    "Recurring fever — third episode this term; guardian advised on full treatment.",
  ], "Cooled down, rested 30 minutes under observation; guardian advised to run an RDT.", "Paracetamol 500mg × 1"],
  ["Headache", 14, "MINOR", [
    "Dull headache after the double Maths lesson.",
    "Woke up with a headache; no fever recorded.",
    "Headache blamed on skipping breakfast at home.",
    "Tension headache before the class test; calmed after rest.",
  ], "Rested 15 minutes in the bay; water given.", "Paracetamol 250mg × 1"],
  ["Stomach ache / upset", 12, "MINOR", [
    "Stomach upset after break; suspect the roadside kulikuli.",
    "Cramping since assembly; did not eat breakfast.",
    "Loose stool once; ORS given and monitored.",
    "Complained of stomach pain during the ICT lesson.",
  ], "ORS sips, rested, monitored for 40 minutes.", "ORS sachet"],
  ["Cough / cold / catarrh", 11, "MINOR", [
    "Persistent catarrh with a mild cough; no fever.",
    "Coughing through the morning lesson; sent with a note for the guardian.",
    "Blocked nose and sneezing since the harmattan started.",
  ], "Steam inhalation explained to the learner; sent back to class.", null as unknown as string],
  ["Injury (fall / bruise)", 9, "MODERATE", [
    "Slipped on the wet corridor after break; bruised left knee.",
    "Fell during PE warm-up; scraped elbow cleaned and dressed.",
    "Knocked the desk edge on the forehead; small bump, no cut.",
  ], "Wound cleaned with antiseptic and dressed; cold compress applied.", "Antiseptic + plaster"],
  ["Sports injury", 7, "MODERATE", [
    "Twisted right ankle during the inter-house football match.",
    "Ball hit the nose during volleyball; nosebleed controlled.",
    "Sprained wrist falling in the relay; strapped with crepe bandage.",
  ], "R.I.C.E applied; ankle strapped; guardian advised on the swelling.", "Crepe bandage + cold pack"],
  ["Skin rash / ringworm", 6, "MINOR", [
    "Ringworm patch on the left forearm; guardian note issued for clinic cream.",
    "Heat rash on the neck and back; advised on hygiene.",
    "Itchy patches on both legs; suspected tinea from the shared mats.",
  ], "Area cleaned; guardian note for antifungal cream issued.", null as unknown as string],
  ["Toothache", 5, "MINOR", [
    "Lower molar ache for two days; guardian referral note issued.",
    "Toothache with a visible cavity; warm saline rinse given.",
  ], "Saline rinse; dental referral note to the guardian.", null as unknown as string],
  ["Eye irritation", 4, "MINOR", [
    "Red itchy eyes — conjunctivitis suspected; guardian note issued.",
    "Dust irritation during the compound clean-up; eye bath given.",
  ], "Clean water eye bath; guardian advised on the discharge.", null as unknown as string],
  ["Asthma / breathing", 3, "MAJOR", [
    "Wheezing after the morning run; inhaler from the bag used under supervision.",
    "Asthma flare during harmattan dust; kept for observation until calm.",
    "Tight chest reported by the learner with a known asthma history.",
  ], "Inhaler administered; sat upright, monitored 45 minutes until breathing calmed.", "Salbutamol inhaler (learner's own)"],
  ["Fainting / dizziness", 3, "MAJOR", [
    "Fainted during the assembly queue; revived with glucose water.",
    "Dizzy under the midday sun; hydration and shade rest.",
    "Collapsed briefly after standing long at the lab; recovered quickly.",
  ], "Laid flat, glucose water sips; guardian called; monitored an hour.", "Glucose water"],
  ["Menstrual cramps", 4, "MINOR", [
    "Severe cramps during the pre-lesson; rested with a hot water bottle.",
    "Period pain mid-day; pad and rest provided from the bay stock.",
  ], "Hot water bottle, rest, pad provided from the bay stock.", "Paracetamol 500mg × 1"],
  ["Wound dressing", 5, "MINOR", [
    "Routine dressing change for last week's knee graze.",
    "Old dressing soaked through after the water spill; changed afresh.",
  ], "Wound cleaned and re-dressed; healing well.", "Antiseptic + plaster"],
  ["Ear ache", 3, "MINOR", [
    "Right ear ache with mild fever; guardian referral note issued.",
    "Ear pain after the swimming trip last weekend.",
  ], "Referral note to the guardian for the clinic; paracetamol for the pain.", "Paracetamol 250mg × 1"],
  ["Nose bleed", 2, "MINOR", [
    "Spontaneous nosebleed during the afternoon lesson; controlled in 10 minutes.",
    "Nosebleed after picking following the harmattan dryness.",
  ], "Pinched soft part, lean forward, cold compress; stopped in 10 minutes.", null as unknown as string],
];

const OUTCOMES = [
  "Recovered fully under observation; back in class the next day.",
  "Guardian completed the clinic treatment; follow-up clear.",
  "Follow-up check done — temperature normal, learner active again.",
  "Referred case closed after the hospital discharge note was sighted.",
  "Ankle healed after the strap; resumed PE with caution.",
  "Condition stable; guardian managing medication at home.",
];

const NURSES = ["Sister Akosua Mensah", "Mr. Kwame Boateng"];

const BLOOD_GROUPS = ["O+", "A+", "B+", "O-", "AB+", "A-"];

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

  const existing = await db.healthVisit.count({ where: { schoolId: school.id } });
  if (existing > 0) {
    console.log(`✅ Health seed skipped — sick bay already holds ${existing} visit(s)`);
    return;
  }

  const classrooms = await db.classroom.findMany({
    where: { schoolId: school.id },
    select: { id: true, name: true, enrollments: { where: { status: "ACTIVE" }, select: { student: { select: { id: true } } } } },
    orderBy: { name: "asc" },
  });
  const learners = classrooms.flatMap((c) => c.enrollments.map((e) => ({ studentId: e.student.id, classroomId: c.id, classroomName: c.name })));
  if (!learners.length) throw new Error("No active learners found");

  /* ── 1. medical background for blank learners (blood group, allergies…) ── */
  let enriched = 0;
  const sample = learners.filter(() => rnd() < 0.18);
  for (const l of sample.slice(0, 80)) {
    const allergies = rnd() < 0.22 ? pick(["Peanuts", "Pineapple", "Dust mites", "Penicillin", "Seafood"]) : null;
    const conditions = rnd() < 0.14 ? pick([
      "Mild asthma — carries salbutamol inhaler",
      "Sickle cell trait — avoid strenuous heat exposure",
      "Recurrent malaria — on seasonal prophylaxis",
      "Mild myopia — sits front bench",
    ]) : null;
    await db.student.update({
      where: { id: l.studentId },
      data: {
        bloodGroup: pick(BLOOD_GROUPS),
        allergies,
        medicalConditions: conditions,
        emergencyContact: rnd() < 0.3 ? "+233 20 445 1122" : null,
        dietNote: rnd() < 0.1 ? "No groundnut products (allergy watch)" : null,
      },
    });
    enriched++;
  }

  /* ── 2. sick-bay visits (~110 over 90 school days) ── */
  const days = schoolDays(90);
  const frequentVisitors = new Set<string>();
  while (frequentVisitors.size < 5) frequentVisitors.add(pick(learners).studentId);

  const creates: any[] = [];
  const visitCount = 112;
  for (let i = 0; i < visitCount; i++) {
    const isFrequent = i < 16;
    const learner = isFrequent ? learners.find((l) => l.studentId === [...frequentVisitors][i % 5]) : pick(learners);
    const [category, weight, sevHint, complaintLines, treatment, medication] = pick(CATALOGUE);
    void weight;
    const severity = wpick([
      ["MINOR", sevHint === "MINOR" ? 74 : 38],
      ["MODERATE", sevHint === "MODERATE" ? 44 : 18],
      ["MAJOR", sevHint === "MAJOR" ? 42 : 4],
    ] as [string, number][]);
    const day = days[Math.min(days.length - 1, isFrequent ? int(1, 70) : int(0, 89))];
    const ageDays = Math.round((Date.now() - day.getTime()) / 86400000);

    // temperature: fever-ish for MODERATE/MAJOR, normal range otherwise
    const temperature = severity === "MINOR"
      ? Math.round((36.2 + rnd() * 0.8) * 10) / 10
      : Math.round((37.2 + rnd() * 2.0) * 10) / 10;

    const disposition = wpick([
      ["RETURNED_CLASS", severity === "MAJOR" ? 20 : 64],
      ["SENT_HOME", severity === "MINOR" ? 10 : 26],
      ["REFERRED", severity === "MAJOR" ? 30 : 6],
      ["FOLLOWUP", 14],
    ] as [string, number][]);

    const staysOpen = disposition === "FOLLOWUP" || disposition === "REFERRED";
    let status: string;
    let closedAt: Date | null = null;
    let outcomeNote: string | null = null;
    let closedByName: string | null = null;
    if (staysOpen) {
      const closedP = ageDays > 10 ? 0.85 : ageDays > 3 ? 0.5 : 0.12;
      if (rnd() < closedP) {
        status = "CLOSED";
        closedAt = new Date(day.getTime() + int(1, 6) * 86400000);
        outcomeNote = pick(OUTCOMES);
        closedByName = pick(NURSES);
      } else {
        status = "OPEN";
      }
    } else {
      status = "CLOSED";
      closedAt = new Date(day.getTime() + 1800000); // closed same visit
      closedByName = pick(NURSES);
    }
    // a handful of fresh minor same-day cases left open by the busy morning
    if (!staysOpen && ageDays === 0 && rnd() < 0.3) {
      status = "OPEN";
      closedAt = null;
      closedByName = null;
    }
    const parentNotified = (disposition === "SENT_HOME" || disposition === "REFERRED") && rnd() < 0.82;

    creates.push({
      schoolId: school.id, studentId: learner.studentId,
      date: new Date(day.getTime() + int(8, 15) * 3600000 / 15), // within the school day
      category, severity,
      complaint: pick(complaintLines),
      temperature,
      treatment: rnd() < 0.9 ? treatment : null,
      medication: rnd() < 0.75 ? (medication ?? "None given") : null,
      disposition, status, outcomeNote, closedAt, closedByName,
      recordedByName: pick(NURSES),
      parentNotifiedAt: parentNotified ? new Date(day.getTime() + 3600000) : null,
      createdAt: day,
    });
  }
  await db.healthVisit.createMany({ data: creates });

  /* ── 3. screening events ── */
  /* 3a. completed term screening ~10 weeks ago — most of the school covered */
  const termDate = new Date();
  termDate.setDate(termDate.getDate() - 70);
  const termScreening = await db.healthScreening.create({
    data: {
      schoolId: school.id,
      title: "Term 1 Whole-School Health Screening",
      screenDate: termDate,
      status: "COMPLETED",
      notes: "Ghana Education Service School Health Education Programme (SHEP) screening — height, weight, vision and hearing checks. Conducted with the district health directorate team.",
      createdByName: "Sister Akosua Mensah",
      createdAt: termDate,
    },
  });

  const covered = learners.filter(() => rnd() < 0.86); // 86% coverage
  const recordCreates = covered.map((l) => {
    const h = int(118, 176);
    const w = Math.round((18 + rnd() * 16) * (h / 150) * 10) / 10;
    const bmi = Math.round((w / Math.pow(h / 100, 2)) * 10) / 10;
    const visionRefer = rnd() < 0.09;
    const hearingRefer = rnd() < 0.03;
    const referred = visionRefer || hearingRefer || rnd() < 0.02;
    return {
      screeningId: termScreening.id, studentId: l.studentId,
      heightCm: h, weightKg: w, bmi,
      vision: visionRefer ? "REFER" : "PASS",
      hearing: hearingRefer ? "REFER" : "PASS",
      finding: visionRefer ? "Squinting at the chart — myopia suspected; guardian referral issued."
        : hearingRefer ? "Reduced response on the right ear; referral for audiometry."
        : rnd() < 0.2 ? "Healthy; within expected growth band." : null,
      referred,
      note: referred ? "Referral note sent home with the learner." : null,
      recordedByName: "Sister Akosua Mensah",
      createdAt: termDate,
    };
  });
  await db.healthScreeningRecord.createMany({ data: recordCreates });

  /* 3b. open mid-term growth check — mid-recording */
  const midDate = new Date();
  midDate.setDate(midDate.getDate() - 6);
  const midScreening = await db.healthScreening.create({
    data: {
      schoolId: school.id,
      title: "Mid-Term Growth & Nutrition Check",
      screenDate: midDate,
      status: "OPEN",
      notes: "Height/weight re-check for the growth monitoring chart after the term screening. Vision spot-checks for referred learners.",
      createdByName: "Sister Akosua Mensah",
      createdAt: midDate,
    },
  });
  const midLearners = learners.filter(() => rnd() < 0.08);
  await db.healthScreeningRecord.createMany({
    data: midLearners.map((l) => {
      const h = int(120, 175);
      const w = Math.round((19 + rnd() * 15) * (h / 150) * 10) / 10;
      return {
        screeningId: midScreening.id, studentId: l.studentId,
        heightCm: h, weightKg: w,
        bmi: Math.round((w / Math.pow(h / 100, 2)) * 10) / 10,
        vision: rnd() < 0.3 ? "REFER" : "PASS",
        hearing: "PASS",
        finding: rnd() < 0.35 ? "Steady growth since the term screening." : null,
        referred: false,
        recordedByName: "Sister Akosua Mensah",
        createdAt: midDate,
      };
    }),
  });

  const openNow = creates.filter((c) => c.status === "OPEN").length;
  const sentHome = creates.filter((c) => c.disposition === "SENT_HOME").length;
  const referred = creates.filter((c) => c.disposition === "REFERRED").length;
  console.log(`✅ Health seed complete — ${visitCount} sick-bay visits (${openNow} open follow-ups, ${sentHome} sent home, ${referred} referrals), ${enriched} learners medically profiled, ${covered.length} + ${midLearners.length} screening records for ${school.name}`);
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

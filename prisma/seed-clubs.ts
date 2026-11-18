/* Wave 27 — clubs & societies demo data for Golden Excellence Academy.

   Lays down the co-curricular register: 9 clubs across the Ghanaian
   spectrum (Science & Math, Writers & Debaters, ICT & Robotics,
   Scripture Union, Cadet Corps, Girls' Education Movement, Cultural
   Troupe, Drama Club, Wildlife & Environment) each with a patron, a
   weekly meeting day and venue; membership rosters drawn from Basic 4
   through JHS 3 learners with executive offices (one holder per
   office); the meeting register over the last ~9 weeks on the club's
   own weekday with realistic attendance (PRESENT 80–95%, a few EXCUSED
   with notes, the rest ABSENT) plus one upcoming meeting whose
   guardians have been SMS-notified; and 6 events & competitions —
   inter-schools quiz (2nd place), debate finals (1st), Adinkra cultural
   night (1st), a completed pass-out parade, and upcoming regional
   qualifier + GEM regional camp.

   Idempotent: skips when the register already holds rows.
   Run: DATABASE_URL=... bun prisma/seed-clubs.ts */
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

let _s = 20261115;
function rnd() {
  _s |= 0; _s = (_s + 0x6d2b79f5) | 0;
  let t = Math.imul(_s ^ (_s >>> 15), 1 | _s);
  t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
  return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
}
const pick = <T,>(a: T[]) => a[Math.floor(rnd() * a.length)];
const int = (min: number, max: number) => min + Math.floor(rnd() * (max - min + 1));

type ClubSpec = {
  name: string;
  category: string;
  description: string;
  patron: string;
  day: string; // weekday the club meets
  venue: string;
  size: number; // target membership
  founded: string;
  topics: string[];
};

const CLUBS: ClubSpec[] = [
  {
    name: "Science & Math Club", category: "ACADEMIC", patron: "Mr. Kwabena Osei",
    day: "Wednesday", venue: "Science Lab 1", size: 28, founded: "2018-09-14",
    description: "Experiment demonstrations, BECE science clinics and the zonal quiz team.",
    topics: ["Electrochemistry basics — voltaic cell demo", "Past BECE maths marathon (algebra set)", "Preparing the zonal quiz team", "Acids, bases and household indicators", "Geometry clinic — circle theorems", "Building the rain gauge for the weather project"],
  },
  {
    name: "Writers & Debaters Club", category: "ACADEMIC", patron: "Ms. Adjoa Mensah",
    day: "Tuesday", venue: "Library", size: 24, founded: "2017-01-20",
    description: "Public speaking, essay competitions and the inter-schools debate team.",
    topics: ["Motion: boarding enriches learning more than day school", "Essay clinic — WAEC marking rubric", "Motion: the uniform builds discipline", "Reviewing the district finals performance", "Impromptu speaking drills", "Editing this term's literary magazine"],
  },
  {
    name: "ICT & Robotics Club", category: "ACADEMIC", patron: "Mr. Samuel Tetteh",
    day: "Thursday", venue: "ICT Lab", size: 22, founded: "2021-10-08",
    description: "Coding with Scratch, robot builds and the school's computer-lab duty roster.",
    topics: ["Scratch game jam — maze builders", "Intro to Python turtle graphics", "Assembling the line-follower bot", "Typing speed ladder", "Internet safety refresher", "Debugging the traffic-light Arduino sketch"],
  },
  {
    name: "Scripture Union", category: "FAITH", patron: "Mr. Daniel Asare",
    day: "Friday", venue: "Hall B", size: 32, founded: "2015-09-11",
    description: "Fellowship, worship and character-building discussions on Fridays after class.",
    topics: ["Fellowship — love your neighbour", "Praise night rehearsal", "Character study — integrity in exams", "Testimony and thanksgiving service", "Outreach planning — visiting the aged", "Bible quiz — book of Proverbs"],
  },
  {
    name: "Cadet Corps", category: "SERVICE", patron: "Mr. Michael Owusu",
    day: "Saturday", venue: "Parade Ground", size: 20, founded: "2016-05-30",
    description: "Drill, discipline and ceremonial duty — the school's marching cadet corps.",
    topics: ["Foot drill — forming threes", "Rifle drill (wooden)", "Route march to the water works", "Pass-out parade rehearsal", "First aid basics with the sick bay", "Ceremonial escort duty — Founder's Day"],
  },
  {
    name: "Girls' Education Movement", category: "SERVICE", patron: "Ms. Comfort Dede",
    day: "Wednesday", venue: "Hall A", size: 18, founded: "2019-02-13",
    description: "GEM — mentorship, leadership and girls' welfare activities (GES movement).",
    topics: ["Mentor circle — women in STEM", "Sanitary-health talk with the sick bay nurse", "Leadership ladder — chairing meetings", "Preparing the regional camp kits", "Library clean-up community service", "Savings and basic budgeting session"],
  },
  {
    name: "Cultural Troupe", category: "CULTURAL", patron: "Ms. Akosua Boatemaa",
    day: "Monday", venue: "Arts Room", size: 26, founded: "2014-09-15",
    description: "Adowa, kpanlogo and drama — the troupe behind every school ceremony.",
    topics: ["Adowa steps — flat-foot and rhythm", "Kpanlogo drum patterns", "Adinkra night choreography", "Costume inventory and repairs", "Drama sketch — the honest trader", "Drum tuning and maintenance"],
  },
  {
    name: "Drama Club", category: "CULTURAL", patron: "Ms. Efua Grant",
    day: "Thursday", venue: "Hall A", size: 16, founded: "2020-01-15",
    description: "Stage plays, mime and speech-day performances.",
    topics: ["Read-through: The Trials of Obaapanin", "Improv games — character walks", "Blocking Act II of the speech-day play", "Props building — market stall", "Voice projection clinic", "Full dress rehearsal"],
  },
  {
    name: "Wildlife & Environment Club", category: "SERVICE", patron: "Mr. Yaw Frimpong",
    day: "Friday", venue: "School Courtyard", size: 14, founded: "2022-09-20",
    description: "Tree nursery, compound clean-ups and the school's green corner.",
    topics: ["Seed nursery — cashew and acacia", "Compound clean-up and waste sorting", "Mulching the green corner", "Bird watch log — seasonal visitors", "Plastic bottle collection drive", "Tree planting along the eastern fence"],
  },
];

const OFFICE_KEYS = ["PRESIDENT", "VICE_PRESIDENT", "SECRETARY", "TREASURER", "ORGANISER"] as const;
const EXCUSES = ["Away at a family function", "Sick-bay excuse slip", "Inter-schools quiz fixture", "On errand for the head's office", "Transport delay — late arrival"];
const ATT_TOPICS_NOTES = ["Brought materials for the project", "Late arrival — joined mid-session"];

/** Most recent n dates that fall on the given weekday (0 Sun … 6 Sat), strictly before today. */
function recentWeekdays(count: number, weekday: number): Date[] {
  const out: Date[] = [];
  const d = new Date();
  d.setHours(15, 30, 0, 0);
  while (out.length < count) {
    d.setDate(d.getDate() - 1);
    if (d.getDay() === weekday) out.push(new Date(d));
  }
  return out.reverse();
}
/** Next date on the given weekday after today. */
function nextWeekday(weekday: number, weeksAhead = 0): Date {
  const d = new Date();
  d.setHours(15, 30, 0, 0);
  const diff = (weekday - d.getDay() + 7) % 7 || 7;
  d.setDate(d.getDate() + diff + weeksAhead * 7);
  return d;
}

async function main() {
  const school = await db.school.findFirst({ where: { name: { contains: "Golden Excellence" } } });
  if (!school) { console.error("school not found"); process.exit(1); }
  const existing = await db.club.count({ where: { schoolId: school.id } });
  if (existing) { console.log(`clubs already seeded (${existing}) — skipping`); return; }

  // eligible roster: Basic 4 … JHS 3 (upper primary & JHS learners)
  const ens = await db.enrollment.findMany({
    where: { schoolId: school.id, status: "ACTIVE" },
    include: { classroom: { select: { name: true } }, student: { select: { id: true, firstName: true, lastName: true, guardianPhone: true } } },
    take: 800,
  });
  const eligible = ens.filter((e) => /B(4|5|6|7|8|9)|Basic [4-6]|JHS/i.test(e.classroom?.name ?? ""));
  if (!eligible.length) { console.error("no eligible learners found — run seed-classes first"); process.exit(1); }

  const createdByName = "Mr. Kofi Boateng";
  let totalMembers = 0, totalMeetings = 0, totalAttendance = 0;

  for (const spec of CLUBS) {
    const club = await db.club.create({
      data: {
        schoolId: school.id, name: spec.name, category: spec.category,
        description: spec.description, patronName: spec.patron,
        meetingDay: spec.day, venue: spec.venue, status: "ACTIVE",
        foundedOn: new Date(spec.founded), createdByName,
      },
    });

    // membership roster — weighted unique draw
    const shuffled = [...eligible].sort(() => rnd() - 0.5);
    const take = shuffled.slice(0, spec.size);
    const joinedBase = new Date();
    joinedBase.setMonth(joinedBase.getMonth() - int(4, 12));
    const memberships = await db.clubMembership.createMany({
      data: take.map((e, i) => ({
        schoolId: school.id, clubId: club.id, studentId: e.student.id,
        office: i < 5 ? OFFICE_KEYS[i] : null,
        joinedOn: new Date(joinedBase.getTime() + i * 86400000 * 3),
        status: "ACTIVE",
        createdByName,
      })),
    });
    totalMembers += memberships.count;

    // meeting register: last 6 meetings on the club's weekday + one upcoming
    const weekday = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"].indexOf(spec.day);
    const dates = recentWeekdays(6, weekday);
    const upcoming = nextWeekday(weekday);
    const allDates = [...dates, upcoming];

    for (let mi = 0; mi < allDates.length; mi++) {
      const when = allDates[mi];
      const isUpcoming = when.getTime() > Date.now();
      const meeting = await db.clubMeeting.create({
        data: {
          schoolId: school.id, clubId: club.id, date: when,
          topic: spec.topics[mi % spec.topics.length],
          minutes: isUpcoming ? null : `Discussed ${spec.topics[mi % spec.topics.length].toLowerCase()}; tasks assigned to members; next session confirmed for ${spec.day}.`,
          takenByName: spec.patron,
          guardiansNotifiedAt: isUpcoming && spec.day === "Wednesday" ? new Date(Date.now() - 86400000) : null,
        },
      });
      totalMeetings++;

      // attendance rows — fetch the real membership ids for this club
      const clubMems = await db.clubMembership.findMany({ where: { clubId: club.id, status: "ACTIVE" }, select: { id: true } });
      const rows: { meetingId: string; membershipId: string; status: string; note: string | null }[] = [];
      for (const mem of clubMems) {
        const r = rnd();
        let status = "PRESENT";
        if (r > 0.97) status = "EXCUSED";
        else if (r > (mi === 0 ? 0.88 : 0.93)) status = "ABSENT";
        const note = isUpcoming ? null : status === "PRESENT" && rnd() > 0.9 ? pick(ATT_TOPICS_NOTES) : status === "EXCUSED" ? pick(EXCUSES) : null;
        rows.push({ meetingId: meeting.id, membershipId: mem.id, status, note });
      }
      await db.clubAttendance.createMany({
        // the upcoming meeting's register is seeded ABSENT (unmarked)
        data: isUpcoming ? rows.map((r) => ({ ...r, status: "ABSENT", note: null })) : rows,
      });
      totalAttendance += rows.length;
    }
  }

  // membership trail: one ICT club member transferred out mid-term
  const ict = await db.club.findFirst({ where: { schoolId: school.id, name: "ICT & Robotics Club" } });
  if (ict) {
    const leaver = await db.clubMembership.findFirst({
      where: { clubId: ict.id, status: "ACTIVE", office: null },
      orderBy: { joinedOn: "asc" },
    });
    if (leaver) {
      const leftOn = new Date();
      leftOn.setDate(leftOn.getDate() - 25);
      await db.clubMembership.update({
        where: { id: leaver.id },
        data: { status: "LEFT", leftOn, leftReason: "Transferred to Adenta Presby JHS" },
      });
    }
  }

  // events & competitions
  const science = await db.club.findFirst({ where: { schoolId: school.id, name: "Science & Math Club" } });
  const debate = await db.club.findFirst({ where: { schoolId: school.id, name: "Writers & Debaters Club" } });
  const cultural = await db.club.findFirst({ where: { schoolId: school.id, name: "Cultural Troupe" } });
  const cadet = await db.club.findFirst({ where: { schoolId: school.id, name: "Cadet Corps" } });
  const gem = await db.club.findFirst({ where: { schoolId: school.id, name: "Girls' Education Movement" } });

  const now = Date.now();
  const events: {
    clubId: string | null; title: string; daysAgo: number; level: string;
    outcome: string; placement: string | null; participants: string; note: string;
  }[] = [
    {
      clubId: science?.id ?? null, title: "Zonal Science & Maths Quiz", daysAgo: 21, level: "ZONAL", outcome: "COMPLETED",
      placement: "2nd", participants: "Aisha Mohammed, Kojo Asante, Emelia Tetteh",
      note: "Lost narrowly to Adenta Presby in the final round on the chemistry tie-breaker.",
    },
    {
      clubId: debate?.id ?? null, title: "Inter-Schools Debate Finals — Adentan District", daysAgo: 12, level: "DISTRICT", outcome: "COMPLETED",
      placement: "1st", participants: "Naa Adjeley Quaye, Francis Owusu, Priscilla Mensah",
      note: "Best speaker award to Naa Adjeley; motion on renewable energy.",
    },
    {
      clubId: cultural?.id ?? null, title: "Adinkra Cultural Night (Speech Day)", daysAgo: 35, level: "SCHOOL", outcome: "COMPLETED",
      placement: "1st", participants: "Full troupe — 24 performers",
      note: "Adowa and kpanlogo medley opened the speech day; guests from the GES district office.",
    },
    {
      clubId: cadet?.id ?? null, title: "Founders' Day Ceremonial Escort", daysAgo: 50, level: "SCHOOL", outcome: "COMPLETED",
      placement: null, participants: "Corps of 20 cadets",
      note: "Mounted the quarter-guard for the directorate's visit.",
    },
    {
      clubId: science?.id ?? null, title: "National Science & Maths Quiz — Regional Qualifier", daysAgo: -9, level: "REGIONAL", outcome: "UPCOMING",
      placement: null, participants: "Aisha Mohammed, Kojo Asante, standby: Emelia Tetteh",
      note: "Prep clinics every Wednesday; transport requisition submitted.",
    },
    {
      clubId: gem?.id ?? null, title: "GEM Regional Camp — Koforidua", daysAgo: -28, level: "REGIONAL", outcome: "UPCOMING",
      placement: null, participants: "12 members + 2 patrons",
      note: "Kits and consent letters being prepared by the patrons.",
    },
  ];
  for (const ev of events) {
    await db.clubEvent.create({
      data: {
        schoolId: school.id, clubId: ev.clubId, title: ev.title,
        date: new Date(now - ev.daysAgo * 86400000), level: ev.level, outcome: ev.outcome,
        placement: ev.placement, participants: ev.participants, note: ev.note, recordedByName: createdByName,
      },
    });
  }

  console.log(`clubs seeded: ${CLUBS.length} clubs · ${totalMembers} memberships · ${totalMeetings} meetings · ${totalAttendance} attendance rows · ${events.length} events`);
}

main()
  .catch((e) => { console.error(e); process.exit(1); })
  .finally(() => db.$disconnect());

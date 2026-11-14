/* Wave 29 — front office demo data for Golden Excellence Academy.

   Lays down the desk books every Ghanaian school front desk keeps:
   the Visitors Book (a 30-day traffic tail plus two visitors ON SITE
   right now with pass numbers), the Appointments Book (past meetings
   with outcomes, today's schedule, upcoming PTA/fees meetings), the
   Phone Call Log (incoming/outgoing with a few open follow-ups), the
   Postal Register (incoming parcels pending collection, dispatched
   outgoing mail) and the School Events Calendar (PTA meeting, open
   day, sports & prize-giving tied to the current term).

   Idempotent: skips when the visitors book already holds rows.
   Run: DATABASE_URL=... bun prisma/seed-frontoffice.ts */
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

let _s = 20291114;
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

const VISITORS: [string, number][] = [
  ["Mrs. Akosua Mensah", 26], ["Mr. Kwame Osei", 22], ["Madam Abena Serwaa", 18],
  ["Mr. Yaw Boateng", 14], ["Mrs. Efua Danso", 12], ["Nana Kojo Appiah", 8],
  ["Mr. Adjei (Glory Bookshop)", 8], ["Rev. Father Owusu", 6], ["Officer Mensah (Ghana Police)", 5],
  ["Madam Vida Nyarko", 5], ["Mr. Tetteh (City Parcel)", 5], ["Mrs. Gifty Asamoah", 5],
  ["Dr. Ofori (Ghana Health Service)", 4], ["Mr. Baffour (Nutrifoods)", 4], ["Miss Pokuaa", 3],
];
const ORGS: [string | null, number][] = [
  [null, 55], ["GES Circuit Office", 10], ["Glory Bookshop", 8], ["Nutrifoods Catering", 7],
  ["Ghana Health Service", 5], ["City Parcel Services", 5], ["Ghana Police Service", 3], ["Old Students Association", 4],
];
const PURPOSES = [
  "Dropped ward's forgotten lunch", "Fee payment enquiry at the desk", "Ward pick-up for clinic appointment",
  "Delivered textbooks order", "Meeting the Headmistress", "Asked about admission forms",
  "Circuit officer — routine supervision visit", "Delivered WAEC registration documents",
  "PTA executive discussion", "Collected ward for the day", "Discussion about bus route change",
  "Health screening team from the polyclinic", "Water delivery inspection", "Scout troop leader visit",
];
const PERSONS = [null, null, "The Headmistress", "The Headmistress", "Assistant Head (Academic)", "The Bursar", "Class teacher — JHS 2", "The Secretary"];

async function main() {
  const school = await db.school.findFirst({ where: { name: { contains: "Golden Excellence" } } });
  if (!school) throw new Error("GEA school not found — run the base seed first");

  // These four desks books are owned exclusively by this seed — wipe and
  // recreate so the seed stays re-runnable after partial failures.
  const [oldVisitors, oldAppts, oldCalls, oldPostal, oldEvents] = await Promise.all([
    db.visitor.count({ where: { schoolId: school.id } }),
    db.appointment.count({ where: { schoolId: school.id } }),
    db.phoneCall.count({ where: { schoolId: school.id } }),
    db.postalItem.count({ where: { schoolId: school.id } }),
    db.schoolEvent.count({ where: { schoolId: school.id } }),
  ]);
  if (oldVisitors + oldAppts + oldCalls + oldPostal + oldEvents > 0) {
    await Promise.all([
      db.visitor.deleteMany({ where: { schoolId: school.id } }),
      db.appointment.deleteMany({ where: { schoolId: school.id } }),
      db.phoneCall.deleteMany({ where: { schoolId: school.id } }),
      db.postalItem.deleteMany({ where: { schoolId: school.id } }),
      db.schoolEvent.deleteMany({ where: { schoolId: school.id } }),
    ]);
    console.log(`↻ cleared previous front-office rows (${oldVisitors} visitors, ${oldAppts} appointments, ${oldCalls} calls, ${oldPostal} mail, ${oldEvents} events)`);
  }

  const term = await db.term.findFirst({
    where: { academicYear: { schoolId: school.id }, isCurrent: true },
    select: { id: true, name: true, startsAt: true, endsAt: true },
  });

  const staff = await db.staff.findMany({
    where: { schoolId: school.id, active: true },
    select: { fullName: true, position: true },
    orderBy: { fullName: "asc" },
  });
  const deskOfficer = staff.find((s) => /secret|admin|front/i.test(s.position ?? ""))?.fullName ?? staff[0]?.fullName ?? "Front desk";
  const head = staff.find((s) => /head/i.test(s.position ?? ""))?.fullName ?? "The Headmistress";
  const bursar = staff.find((s) => /bursar|account/i.test(s.position ?? ""))?.fullName ?? "The Bursar";

  const now = new Date();
  const todayZero = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate()));

  /* ── 1. Visitors Book — ~85 visitors across the last 30 days ── */
  type VCreate = { schoolId: string; name: string; phone: string | null; organisation: string | null; category: string; purpose: string; personToSee: string | null; passNo: string; timeIn: Date; timeOut: Date | null; status: string; recordedByName: string };
  const visitorCreates: VCreate[] = [];
  let pass = 0;
  const nextPass = () => `P-${String(++pass).padStart(3, "0")}`;
  const phone = () => `0${pick(["24", "20", "54", "55", "27"])}${int(3000000, 9999999)}`;

  for (let d = 30; d >= 1; d--) {
    const weekday = new Date(todayZero);
    weekday.setUTCDate(weekday.getUTCDate() - d);
    if (weekday.getUTCDay() === 0) continue; // Sundays the gate is quiet
    const n = int(1, 5);
    for (let i = 0; i < n; i++) {
      const name = pick(VISITORS)[0];
      const category = wpick([["PARENT", 52], ["VENDOR", 18], ["GOVT", 10], ["ALUMNI", 6], ["OTHER", 14]] as [string, number][]);
      const hour = wpick([[7, 16], [8, 22], [9, 18], [10, 12], [11, 8], [12, 10], [13, 6], [14, 5], [15, 3]] as [number, number][]);
      const timeIn = new Date(weekday);
      timeIn.setUTCHours(hour, int(0, 59), 0, 0);
      const stayMin = pick([10, 15, 20, 30, 45, 60, 90]);
      // historical rows are always closed out — only today's hand-added pair stays on site
      const timeOut = new Date(timeIn.getTime() + stayMin * 60_000);
      visitorCreates.push({
        schoolId: school.id,
        name, phone: rnd() < 0.7 ? phone() : null,
        organisation: wpick(ORGS),
        category,
        purpose: pick(PURPOSES),
        personToSee: pick(PERSONS),
        passNo: nextPass(),
        timeIn,
        timeOut,
        status: "DEPARTED",
        recordedByName: deskOfficer,
      });
    }
  }

  // two visitors on site right now (this morning, still in)
  const nowHour = now.getUTCHours();
  for (const [i, who] of [["Mrs. Akosua Mensah", "Dropped ward's forgotten lunch"], ["Mr. Adjei (Glory Bookshop)", "Delivered textbooks order"]].entries()) {
    const timeIn = new Date();
    timeIn.setUTCHours(Math.max(7, nowHour - 1 - i), int(5, 50), 0, 0);
    if (timeIn > now) timeIn.setTime(now.getTime() - 25 * 60_000);
    visitorCreates.push({
      schoolId: school.id,
      name: who[0], phone: phone(), organisation: i === 1 ? "Glory Bookshop" : null,
      category: i === 1 ? "VENDOR" : "PARENT",
      purpose: who[1],
      personToSee: i === 1 ? "The Bursar" : pick(PERSONS),
      passNo: nextPass(),
      timeIn, timeOut: null, status: "ON_SITE",
      recordedByName: deskOfficer,
    });
  }

  await db.visitor.createMany({ data: visitorCreates });

  /* ── 2. Appointments Book — past with outcomes, today, upcoming ── */
  type ACreate = { schoolId: string; title: string; withName: string; withRole: string; personToSee: string | null; scheduledAt: Date; durationMin: number; location: string | null; purpose: string | null; status: string; outcome: string | null; createdByName: string };
  const apptCreates: ACreate[] = [];
  const LOCATIONS = ["Head's office", "Meeting room", "The Bursar's office", "Front desk", "Classroom block"];

  // past two weeks — mostly completed with outcomes
  for (let d = 14; d >= 1; d--) {
    const day = new Date(todayZero);
    day.setUTCDate(day.getUTCDate() - d);
    if (day.getUTCDay() === 0 || rnd() < 0.35) continue;
    const hour = int(9, 15);
    const at = new Date(day); at.setUTCHours(hour, pick([0, 30]), 0, 0);
    const status = wpick([["COMPLETED", 78], ["NO_SHOW", 10], ["CANCELLED", 12]] as [string, number][]);
    apptCreates.push({
      schoolId: school.id,
      title: pick(["Discuss ward's fees balance", "Admission interview — JHS 1 intake", "Discuss repeated lateness", "Bus route change consultation", "Academic progress review", "Sponsorship & bursary discussion"]),
      withName: pick(VISITORS)[0],
      withRole: wpick([["PARENT", 70], ["VENDOR", 15], ["GOVT", 10], ["OTHER", 5]] as [string, number][]),
      personToSee: pick([head, bursar, deskOfficer]),
      scheduledAt: at, durationMin: pick([20, 30, 45]),
      location: pick(LOCATIONS),
      purpose: pick(["Fees arrangement", "Behaviour review", "Admissions", "Transport logistics"]),
      status,
      outcome: status === "COMPLETED" ? pick([
        "Agreed part-payment plan; receipt issued at the cashier.",
        "Guardian advised; ward to report to the counsellor weekly.",
        "Admission offered — forms issued, placement test Friday.",
        "Route change approved from next week; conductor informed.",
      ]) : null,
      createdByName: deskOfficer,
    });
  }

  // today's schedule
  const todayHour = now.getUTCHours();
  const todaySlots = [
    { h: Math.max(9, todayHour), title: "Fees arrangement meeting", withName: "Mr. Kwame Osei", role: "PARENT", see: bursar },
    { h: Math.min(15, todayHour + 1), title: "Water supply contract review", withName: "Mr. Baffour (Nutrifoods)", role: "VENDOR", see: head },
  ].filter((s) => s.h >= 8 && s.h <= 16);
  for (const s of todaySlots) {
    const at = new Date(); at.setUTCHours(s.h, 30, 0, 0);
    apptCreates.push({
      schoolId: school.id,
      title: s.title, withName: s.withName, withRole: s.role, personToSee: s.see,
      scheduledAt: at, durationMin: 30, location: pick(LOCATIONS),
      purpose: s.role === "VENDOR" ? "Suppliers" : "Fees arrangement",
      status: "SCHEDULED", outcome: null, createdByName: deskOfficer,
    });
  }

  // upcoming two weeks — PTA & admissions flavour
  for (let d = 1; d <= 12; d++) {
    const day = new Date(todayZero);
    day.setUTCDate(day.getUTCDate() + d);
    if (day.getUTCDay() === 0 || rnd() < 0.55) continue;
    apptCreates.push({
      schoolId: school.id,
      title: pick(["Admission interview — new intake", "Mid-term academic check-in with guardian", "Discuss boarding transfer request", "Fee arrears settlement plan"]),
      withName: pick(VISITORS)[0],
      withRole: wpick([["PARENT", 80], ["VENDOR", 10], ["GOVT", 10]] as [string, number][]),
      personToSee: pick([head, bursar, deskOfficer]),
      scheduledAt: new Date(new Date(day).setUTCHours(int(9, 15), pick([0, 30]), 0, 0)),
      durationMin: pick([20, 30, 45]), location: pick(LOCATIONS),
      purpose: pick(["Admissions", "Fees arrangement", "Academic review"]),
      status: wpick([["SCHEDULED", 70], ["CONFIRMED", 30]] as [string, number][]),
      outcome: null, createdByName: deskOfficer,
    });
  }

  await db.appointment.createMany({ data: apptCreates });

  /* ── 3. Phone Call Log — ~45 calls, several open follow-ups ── */
  type CCreate = { schoolId: string; direction: string; contactName: string; phone: string | null; regarding: string; handledByName: string; followUp: boolean; followUpDone: boolean; calledAt: Date; notes: string | null };
  const REGARDS_IN = [
    "Asked about mid-term break dates", "Requested fee balance over the phone", "Confirmed ward's absence due to fever",
    "WAEC registration queries from the district office", "Bookshop called about the textbook order",
    "Parent complained about the bus running late", "Polyclinic confirming Friday's screening visit",
    "Alumnus asked about the speech & prize-giving day",
  ];
  const REGARDS_OUT = [
    "Invited parent for a fees meeting", "Reminder: outstanding term fees", "Called guardian about sick-bay pick-up",
    "Confirmed textbook delivery with the bookshop", "Followed up on PTA dues", "Told route parents about the schedule change",
  ];
  const callCreates: CCreate[] = [];
  for (let d = 21; d >= 0; d--) {
    const day = new Date(todayZero);
    day.setUTCDate(day.getUTCDate() - d);
    if (day.getUTCDay() === 0) continue;
    const n = int(1, 3);
    for (let i = 0; i < n; i++) {
      const direction = wpick([["INCOMING", 55], ["OUTGOING", 45]] as [string, number][]);
      const followUp = rnd() < 0.28;
      const followUpDone = followUp && rnd() < 0.45;
      const at = new Date(day); at.setUTCHours(int(7, 16), int(0, 59), 0, 0);
      callCreates.push({
        schoolId: school.id,
        direction,
        contactName: pick(VISITORS)[0],
        phone: phone(),
        regarding: direction === "INCOMING" ? pick(REGARDS_IN) : pick(REGARDS_OUT),
        handledByName: pick([deskOfficer, bursar, head]),
        followUp, followUpDone,
        calledAt: at,
        notes: rnd() < 0.3 ? pick(["Promised to call back after work.", "Passed to the bursar to handle.", "Guardian will come in on Friday.", "Told them to check the noticeboard."]) : null,
      });
    }
  }
  await db.phoneCall.createMany({ data: callCreates });

  /* ── 4. Postal Register — incoming pending/some collected, outgoing dispatched ── */
  type PCreate = { schoolId: string; direction: string; type: string; refNo: string | null; fromParty: string; toParty: string; receivedAt: Date; dispatchedAt: Date | null; collectedBy: string | null; status: string; notes: string | null; recordedByName: string };
  const postalCreates: PCreate[] = [];
  const incoming: [string, string, string][] = [
    ["Ghana Education Service — Accra", "The Headmistress", "DOCUMENT"],
    ["West African Examinations Council", "Exams Officer", "DOCUMENT"],
    ["Old Students Association (GBA)", "The Headmistress", "LETTER"],
    ["Ghana Health Service — Polyclinic", "School Nurse", "LETTER"],
    ["Mr. Kwame Osei (parent)", "The Bursar", "LETTER"],
    ["Glory Bookshop", "The Storekeeper", "PARCEL"],
    ["Nutrifoods Catering", "Dining Hall", "DOCUMENT"],
    ["Registrar-General's Department", "The Proprietor", "DOCUMENT"],
  ];
  incoming.forEach(([from, to, type], i) => {
    const dAgo = int(1, 25);
    const receivedAt = new Date(todayZero); receivedAt.setUTCDate(receivedAt.getUTCDate() - dAgo);
    const collected = rnd() < 0.55;
    postalCreates.push({
      schoolId: school.id,
      direction: "INCOMING", type, refNo: type === "PARCEL" ? `GPL-${int(10000, 99999)}` : null,
      fromParty: from, toParty: to, receivedAt,
      dispatchedAt: collected ? new Date(receivedAt.getTime() + int(4, 72) * 3_600_000) : null,
      collectedBy: collected ? pick([head, "School Nurse", "Exams Officer", "The Storekeeper"]) : null,
      status: collected ? "COLLECTED" : "PENDING",
      notes: null, recordedByName: deskOfficer,
    });
    void i;
  });
  const outgoing: [string, string, string][] = [
    ["The Headmistress", "GES Circuit Office — Annual census return", "DOCUMENT"],
    ["The Bursar", "GRA — PAYE monthly return", "DOCUMENT"],
    ["The Headmistress", "WAEC — BECE registration forms", "DOCUMENT"],
    ["The Secretary", "PTA executive — meeting invitation", "LETTER"],
    ["The Proprietor", "Bank — standing order instruction", "LETTER"],
  ];
  outgoing.forEach(([from, to, type]) => {
    const dAgo = int(2, 20);
    const receivedAt = new Date(todayZero); receivedAt.setUTCDate(receivedAt.getUTCDate() - dAgo);
    const dispatched = rnd() < 0.7;
    postalCreates.push({
      schoolId: school.id,
      direction: "OUTGOING", type, refNo: rnd() < 0.5 ? `REG-${int(1000, 9999)}` : null,
      fromParty: from, toParty: to, receivedAt,
      dispatchedAt: dispatched ? new Date(receivedAt.getTime() + int(4, 48) * 3_600_000) : null,
      collectedBy: null,
      status: dispatched ? "DISPATCHED" : "PENDING",
      notes: null, recordedByName: deskOfficer,
    });
  });
  await db.postalItem.createMany({ data: postalCreates });

  /* ── 5. School Events Calendar — the term's noticeboard dates ── */
  const eventCreates: any[] = [];
  const day = (offset: number) => {
    const d = new Date(todayZero); d.setUTCDate(d.getUTCDate() + offset); return d;
  };
  if (term) {
    eventCreates.push(
      { schoolId: school.id, termId: term.id, title: `${term.name} PTA general meeting`, category: "PTA_MEETING", startsAt: day(9), location: "School assembly grounds", audience: "PARENTS", description: "Term fees review, WASSCE/BECE preparation briefing and the PTA dues resolution. All class reps expected.", createdByName: deskOfficer },
      { schoolId: school.id, termId: term.id, title: "Open day — meet the class teachers", category: "OPEN_DAY", startsAt: day(16), location: "Classroom block", audience: "PARENTS", description: "Parents walk through their ward's exercise books and term scores with class teachers.", createdByName: deskOfficer },
      { schoolId: school.id, termId: term.id, title: "Inter-houses sports & athletics", category: "SPORTS", startsAt: day(23), location: "School park", audience: "ALL", description: "House competition — marching, athletics and the cheering contest. Concession stands by Form 3.", createdByName: deskOfficer },
      { schoolId: school.id, termId: term.id, title: "Speech & prize-giving day", category: "PRIZE_GIVING", startsAt: day(38), location: "Assembly hall", audience: "ALL", description: "Awards for academic excellence, best-conduct learners and the long-serving staff recognition.", createdByName: head },
      { schoolId: school.id, termId: term.id, title: "End-of-term examinations begin", category: "EXAMINATION", startsAt: day(30), endsAt: day(36), location: "Classroom block", audience: "STUDENTS", description: "Terminal exams for the whole school; timetable on the exams noticeboard.", createdByName: head },
      { schoolId: school.id, termId: term.id, title: "Vacation — end of term", category: "HOLIDAY", startsAt: day(45), location: null, audience: "ALL", description: "Reports and vacation date issued; school re-opens per the academic calendar.", createdByName: head },
    );
  } else {
    eventCreates.push(
      { schoolId: school.id, termId: null, title: "PTA general meeting", category: "PTA_MEETING", startsAt: day(9), location: "School assembly grounds", audience: "PARENTS", description: "Term fees review and PTA dues resolution.", createdByName: deskOfficer },
      { schoolId: school.id, termId: null, title: "Speech & prize-giving day", category: "PRIZE_GIVING", startsAt: day(38), location: "Assembly hall", audience: "ALL", description: "Awards for academic excellence and best conduct.", createdByName: head },
    );
  }
  await db.schoolEvent.createMany({ data: eventCreates });

  const onSite = visitorCreates.filter((v) => v.status === "ON_SITE").length;
  const openFu = callCreates.filter((c) => c.followUp && !c.followUpDone).length;
  const pendingMail = postalCreates.filter((p) => p.status === "PENDING").length;
  console.log(`✅ Front office seed complete — ${visitorCreates.length} visitors (${onSite} on site), ${apptCreates.length} appointments, ${callCreates.length} calls (${openFu} follow-ups open), ${postalCreates.length} mail items (${pendingMail} pending), ${eventCreates.length} calendar events for ${school.name}`);
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

/* Nyansapo seed — rich Ghanaian private-school demo dataset.
   Run: bun prisma/seed.ts
*/
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

// ── deterministic PRNG ────────────────────────────────────────
let _s = 20261031;
function rnd() {
  _s |= 0; _s = (_s + 0x6d2b79f5) | 0;
  let t = Math.imul(_s ^ (_s >>> 15), 1 | _s);
  t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
  return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
}
const pick = <T,>(a: T[]) => a[Math.floor(rnd() * a.length)];
const int = (min: number, max: number) => min + Math.floor(rnd() * (max - min + 1));

// ── name pools (Ghanaian) ─────────────────────────────────────
const M = ["Kwame","Kofi","Kwabena","Kwaku","Yaw","Kojo","Fiifi","Elikem","Nana","Kwesi","Yao","Selorm","Etornam","Prince","Emmanuel","Samuel","Jeremiah","Jude","Kelvin","Jeffery","Eugene","Bernard","Clement","Daniel","Isaac","Joshua","Mawuli","Kodjo","Nii","Kwei","Ade","Tetteh","Ebo"];
const F = ["Ama","Akosua","Adwoa","Abena","Akua","Yaa","Afia","Esi","Efua","Dede","Serwaa","Adiza","Zenabu","Linda","Gifty","Blessing","Promise","Emmanuella","Ophelia","Comfort","Grace","Josephine","Vida","Gina","Naa","Akorfa","Selina","Dzifa","Mawuenam","Praise","Perpetual","Ruth"];
const SURN = ["Mensah","Owusu","Asante","Boateng","Osei","Agyapong","Darko","Addo","Quaye","Tetteh","Nartey","Amuzu","Agbeko","Adu-Gyamfi","Nyarko","Antwi","Baffour","Prempeh","Sarpong","Gyamfi","Danso","Appiah","Amoah","Lawson","Amarteifio","Ashong","Clottey","Nortey","Sowah","Tetteh-Addo","Okai","Bediako","Asamoah","Boadu","Fosu","Donkor","Acheampong","Owusu-Ansah","Baidoo","Tandoh","Aryee","Lamptey","Mould","Kpodar"];
const TOWNS_STREETS = ["House No. 12, Ogbojo Road","P.O. Box 214, Madina Market St.","Hse 4, Ogbojo","Adenta Community 25 St.","La Bawaleshie Rd.","East Legon Hills","Hse 8, Ritz Junction","Ashiyie Bond St.","Frafraha Main St.","Oyarifa Kwabenya Rd."];
const OCCUP = ["Trader","Teacher","Nurse","Civil Servant","Tailor","Driver","Business Owner","Banker","Farmer","Carpenter","Seamstress","Police Officer","Fisherman","Accountant","Hairdresser"];

const male = () => `${pick(M)} ${pick(SURN)}`;
const female = () => `${pick(F)} ${pick(SURN)}`;
function phone() {
  const p = ["024","020","054","055","027","026","050","053"][int(0, 7)];
  return `+233 ${p.slice(1)} ${int(100, 999)} ${int(1000, 9999)}`;
}
function ghanaianName(gender: string) {
  const first = gender === "M" ? pick(M) : pick(F);
  const other = gender === "M" ? pick(M) : pick(F);
  return { first, other, last: pick(SURN) };
}

const CENTS = (cedis: number) => Math.round(cedis * 100);
const fmt = (cents: number) => `GH₵${(cents / 100).toLocaleString("en-GH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const D = (iso: string) => new Date(iso);

// last N school days (weekdays) before a date
function lastSchoolDays(n: number, before: Date): Date[] {
  const out: Date[] = [];
  const d = new Date(before);
  while (out.length < n) {
    const day = d.getDay();
    if (day !== 0 && day !== 6) out.push(new Date(d));
    d.setDate(d.getDate() - 1);
  }
  return out.reverse();
}

async function main() {
  console.log("⏬ Resetting database…");
  await db.aiChatMessage.deleteMany();
  await db.announcement.deleteMany();
  await db.riskScore.deleteMany();
  await db.admissionInquiry.deleteMany();
  await db.requestMessage.deleteMany();
  await db.recordRequest.deleteMany();
  await db.transferRecord.deleteMany();
  await db.flagVerification.deleteMany();
  await db.studentFlag.deleteMany();
  await db.reportCardResult.deleteMany();
  await db.reportCard.deleteMany();
  await db.mark.deleteMany();
  await db.assessment.deleteMany();
  await db.attendanceRecord.deleteMany();
  await db.attendanceSession.deleteMany();
  await db.payment.deleteMany();
  await db.invoiceItem.deleteMany();
  await db.invoice.deleteMany();
  await db.feeItem.deleteMany();
  await db.feeStructure.deleteMany();
  await db.enrollment.deleteMany();
  await db.student.deleteMany();
  await db.mark.deleteMany();
  await db.staff.deleteMany();
  await db.subject.deleteMany();
  await db.classroom.deleteMany();
  await db.term.deleteMany();
  await db.academicYear.deleteMany();
  await db.user.deleteMany();
  await db.school.deleteMany();
  await db.schoolGroup.deleteMany();
  await db.associationNode.deleteMany();

  // ─────────────────────────────────────────────
  // 1. ASSOCIATION HIERARCHY (GNACOPS-style)
  // ─────────────────────────────────────────────
  console.log("🏛  Association hierarchy…");
  const NAT = await db.associationNode.create({ data: { code: "GH-NAT", name: "GNACOPS National Secretariat", level: "NATIONAL", leadTitle: "National Executive Director", leadName: "Obenfo Nana Kwasi Gyetuah", meetingTown: "Accra (Atomic Energy Commission)" } });
  const GAR = await db.associationNode.create({ data: { code: "GAR", name: "Greater Accra Regional Council", level: "REGIONAL", region: "Greater Accra", leadTitle: "Regional Chairperson", leadName: "Madam Gifty Asante", parentId: NAT.id, meetingTown: "Accra" } });
  const ASHR = await db.associationNode.create({ data: { code: "ASH", name: "Ashanti Regional Council", level: "REGIONAL", region: "Ashanti", leadTitle: "Regional Chairperson", leadName: "Mr. Kwabena Sarpong", parentId: NAT.id, meetingTown: "Kumasi" } });
  const ER = await db.associationNode.create({ data: { code: "ER", name: "Eastern Regional Council", level: "REGIONAL", region: "Eastern", leadTitle: "Regional Chairperson", leadName: "Madam Dede Tetteh", parentId: NAT.id, meetingTown: "Koforidua" } });
  const AM = await db.associationNode.create({ data: { code: "GAR-AM", name: "Accra Metropolitan District", level: "DISTRICT", region: "Greater Accra", leadTitle: "District Coordinator", leadName: "Mr. Samuel Adjei", parentId: GAR.id, meetingTown: "Accra Central" } });
  const GE = await db.associationNode.create({ data: { code: "GAR-GE", name: "Ga East District", level: "DISTRICT", region: "Greater Accra", leadTitle: "District Coordinator", leadName: "Mr. Samuel Adjei", parentId: GAR.id, meetingTown: "Madina" } });
  const TMA = await db.associationNode.create({ data: { code: "GAR-TMA", name: "Tema Metropolitan District", level: "DISTRICT", region: "Greater Accra", leadTitle: "District Coordinator", leadName: "Mrs. Elizabeth Okai", parentId: GAR.id, meetingTown: "Tema" } });
  const KMA = await db.associationNode.create({ data: { code: "ASH-KMA", name: "Kumasi Metropolitan District", level: "DISTRICT", region: "Ashanti", leadTitle: "District Coordinator", leadName: "Mr. Yaw Baffour", parentId: ASHR.id, meetingTown: "Kumasi" } });
  const NJ = await db.associationNode.create({ data: { code: "ER-NJ", name: "New Juaben District", level: "DISTRICT", region: "Eastern", leadTitle: "District Coordinator", leadName: "Madam Martha Osei", parentId: ER.id, meetingTown: "Koforidua" } });
  const zones = await Promise.all([
    db.associationNode.create({ data: { code: "GAR-GE-AD", name: "Adenta Zone", level: "ZONE", region: "Greater Accra", leadTitle: "Zonal Representative", leadName: "Madam Vida Osei", parentId: GE.id, meetingTown: "Adenta" } }),
    db.associationNode.create({ data: { code: "GAR-GE-MA", name: "Madina Zone", level: "ZONE", region: "Greater Accra", leadTitle: "Zonal Representative", leadName: "Mr. Ebenezer Clottey", parentId: GE.id, meetingTown: "Madina" } }),
    db.associationNode.create({ data: { code: "GAR-AM-KA", name: "Kanda Zone", level: "ZONE", region: "Greater Accra", leadTitle: "Zonal Representative", leadName: "Mrs. Regina Aryee", parentId: AM.id, meetingTown: "Kanda" } }),
    db.associationNode.create({ data: { code: "GAR-AM-CL", name: "Cantonments–La Zone", level: "ZONE", region: "Greater Accra", leadTitle: "Zonal Representative", leadName: "Mrs. Gina Ashong", parentId: AM.id, meetingTown: "Cantonments" } }),
    db.associationNode.create({ data: { code: "GAR-TMA-AS", name: "Ashaiman Zone", level: "ZONE", region: "Greater Accra", leadTitle: "Zonal Representative", leadName: "Mr. Tetteh Nortey", parentId: TMA.id, meetingTown: "Ashaiman" } }),
    db.associationNode.create({ data: { code: "GAR-TMA-TC", name: "Tema Central Zone", level: "ZONE", region: "Greater Accra", leadTitle: "Zonal Representative", leadName: "Mrs. Patience Sowah", parentId: TMA.id, meetingTown: "Tema Community 1" } }),
    db.associationNode.create({ data: { code: "ASH-KMA-AS", name: "Asokwa Zone", level: "ZONE", region: "Ashanti", leadTitle: "Zonal Representative", leadName: "Madam Akua Fosu", parentId: KMA.id, meetingTown: "Asokwa" } }),
    db.associationNode.create({ data: { code: "ER-NJ-KO", name: "Koforidua Zone", level: "ZONE", region: "Eastern", leadTitle: "Zonal Representative", leadName: "Mr. Clement Donkor", parentId: NJ.id, meetingTown: "Koforidua" } }),
  ]);
  const [AD, MA, KA, CL, ASH, TC, ASK, KO] = zones;

  // ─────────────────────────────────────────────
  // 2. SCHOOL GROUPS & SCHOOLS
  // ─────────────────────────────────────────────
  console.log("🏫 Schools…");
  const GKQ = await db.schoolGroup.create({ data: { name: "Great Kings & Queens Academy", proprietorName: "Mr. Emmanuel Osei-Bonsu", contactPhone: "+233 24 400 1188", contactEmail: "proprietor@greatkings.edu.gh" } });

  const schoolDefs = [
    { name: "Golden Excellence Academy", code: "GEA", motto: "Knowledge · Excellence · Service", curriculum: "GES", category: "MID_TIER", node: MA.id, region: "Greater Accra", district: "Ga East", town: "Madina", gps: "GD-123-4567", phone: "+233 30 293 4455", email: "info@goldenexcellence.edu.gh", nasia: "NaSIA/BAS/2019/0341", nasiaStatus: "LICENSED", gnacops: "VERIFIED", est: 2009, color: "#B8860B", group: null as string | null },
    { name: "Sika Gold International School", code: "SGI", motto: "Lux in Tenebris (Light in Darkness)", curriculum: "CAIE", category: "INTERNATIONAL", node: CL.id, region: "Greater Accra", district: "Accra Metro", town: "Cantonments", gps: "GA-045-2231", phone: "+233 30 277 8899", email: "admin@sikagold.edu.gh", nasia: "NaSIA/INT/2016/0088", nasiaStatus: "LICENSED", gnacops: "VERIFIED", est: 2006, color: "#8B6914", group: null },
    { name: "Nyame Nhyira Preparatory School", code: "NNP", motto: "The Blessing of the Lord", curriculum: "GES", category: "LOW_FEE", node: ASH.id, region: "Greater Accra", district: "Tema Metro", town: "Ashaiman", gps: "GT-110-7754", phone: "+233 24 811 2033", email: "nyamenhyiraprep@gmail.com", nasia: "NaSIA/BAS/2021/1120", nasiaStatus: "PROVISIONAL", gnacops: "PENDING", est: 2021, color: "#2E7D32", group: null },
    { name: "Great Kings & Queens Academy (Main)", code: "GKA", motto: "Raising Kings and Queens", curriculum: "GES", category: "MID_TIER", node: AD.id, region: "Greater Accra", district: "Ga East", town: "Adenta", gps: "GD-098-2210", phone: "+233 30 298 5566", email: "main@greatkings.edu.gh", nasia: "NaSIA/BAS/2015/0510", nasiaStatus: "LICENSED", gnacops: "VERIFIED", est: 2015, color: "#6B3FA0", group: GKQ.id, main: true },
    { name: "Great Kings & Queens Academy — Oyarifa", code: "GKO", motto: "Raising Kings and Queens", curriculum: "GES", category: "MID_TIER", node: AD.id, region: "Greater Accra", district: "Ga East", town: "Oyarifa", gps: "GD-076-1123", phone: "+233 24 400 1190", email: "oyarifa@greatkings.edu.gh", nasia: "NaSIA/BAS/2018/0688", nasiaStatus: "LICENSED", gnacops: "VERIFIED", est: 2018, color: "#6B3FA0", group: GKQ.id, main: false },
    { name: "Great Kings & Queens Academy — Ashaley Botwe", code: "GKB", motto: "Raising Kings and Queens", curriculum: "GES", category: "MID_TIER", node: AD.id, region: "Greater Accra", district: "Ga East", town: "Ashaley Botwe", gps: "GD-080-6541", phone: "+233 24 400 1191", email: "abotwe@greatkings.edu.gh", nasia: "NaSIA/BAS/2020/0912", nasiaStatus: "PROVISIONAL", gnacops: "VERIFIED", est: 2020, color: "#6B3FA0", group: GKQ.id, main: false },
    { name: "Star of the East School", code: "SES", motto: "Illuminating Young Minds", curriculum: "IB", category: "INTERNATIONAL", node: KA.id, region: "Greater Accra", district: "Accra Metro", town: "Kanda", gps: "GA-020-9987", phone: "+233 30 223 1144", email: "info@staroftheast.edu.gh", nasia: "NaSIA/INT/2014/0032", nasiaStatus: "LICENSED", gnacops: "VERIFIED", est: 2004, color: "#0E7490", group: null },
    { name: "Adom Lutheran School", code: "ALS", motto: "Grace and Knowledge", curriculum: "GES", category: "MID_TIER", node: ASK.id, region: "Ashanti", district: "Kumasi Metro", town: "Asokwa", gps: "AK-330-2214", phone: "+233 32 202 6677", email: "info@adomlutheran.edu.gh", nasia: "NaSIA/BAS/2012/0208", nasiaStatus: "LICENSED", gnacops: "VERIFIED", est: 2012, color: "#B45309", group: null },
    { name: "Bright Future International School", code: "BFI", motto: "Bright Minds, Brighter Futures", curriculum: "CAIE", category: "PREMIUM", node: KO.id, region: "Eastern", district: "New Juaben", town: "Koforidua", gps: "ER-015-4432", phone: "+233 34 202 8899", email: "admin@brightfuture.edu.gh", nasia: "NaSIA/INT/2018/0655", nasiaStatus: "LICENSED", gnacops: "VERIFIED", est: 2018, color: "#7C2D12", group: null },
    { name: "Eben Academy", code: "EBA", motto: "The Stone the Builders Refused", curriculum: "GES", category: "MID_TIER", node: TC.id, region: "Greater Accra", district: "Tema Metro", town: "Tema Comm. 4", gps: "GT-004-8890", phone: "+233 30 320 4477", email: "info@ebenacademy.edu.gh", nasia: "NaSIA/BAS/2022/1340", nasiaStatus: "PROVISIONAL", gnacops: "VERIFIED", est: 2022, color: "#9F1239", group: null },
  ];
  const schools: Record<string, { id: string; code: string; name: string; curriculum: string }> = {};
  for (const s of schoolDefs) {
    const sc = await db.school.create({ data: { name: s.name, code: s.code, motto: s.motto, curriculum: s.curriculum, category: s.category, associationNodeId: s.node, region: s.region, district: s.district, town: s.town, gpsAddress: s.gps, phone: s.phone, email: s.email, nasiaRegNo: s.nasia, nasiaStatus: s.nasiaStatus, gnacopsStatus: s.gnacops, gnacopsMemberSince: D(`${s.est}-09-01`), establishedYear: s.est, logoColor: s.color, groupId: s.group, isBranchMain: !!(s as any).main } });
    schools[s.code] = { id: sc.id, code: s.code, name: s.name, curriculum: s.curriculum };
  }

  // ─────────────────────────────────────────────
  // 3. ACADEMIC CALENDAR (GES 2026/27 + prior year)
  // ─────────────────────────────────────────────
  console.log("📅 Academic years…");
  const termsBySchool: Record<string, { prevT3: string; curT1: string; curT2: string; curT3: string }> = {};
  for (const code of Object.keys(schools)) {
    const ay1 = await db.academicYear.create({ data: { schoolId: schools[code].id, name: "2025/2026", startsAt: D("2025-09-08"), endsAt: D("2026-07-30"), isCurrent: false } });
    const t_p1 = await db.term.create({ data: { academicYearId: ay1.id, name: "First Term", sequence: 1, startsAt: D("2025-09-08"), endsAt: D("2025-12-12") } });
    const t_p2 = await db.term.create({ data: { academicYearId: ay1.id, name: "Second Term", sequence: 2, startsAt: D("2026-01-06"), endsAt: D("2026-04-02") } });
    const t_p3 = await db.term.create({ data: { academicYearId: ay1.id, name: "Third Term", sequence: 3, startsAt: D("2026-04-21"), endsAt: D("2026-07-23") } });
    const ay2 = await db.academicYear.create({ data: { schoolId: schools[code].id, name: "2026/2027", startsAt: D("2026-09-08"), endsAt: D("2027-07-29"), isCurrent: true } });
    const t1 = await db.term.create({ data: { academicYearId: ay2.id, name: "First Term", sequence: 1, startsAt: D("2026-09-08"), endsAt: D("2026-12-17"), isCurrent: true } });
    const t2 = await db.term.create({ data: { academicYearId: ay2.id, name: "Second Term", sequence: 2, startsAt: D("2027-01-05"), endsAt: D("2027-03-26"), isCurrent: false } });
    const t3 = await db.term.create({ data: { academicYearId: ay2.id, name: "Third Term", sequence: 3, startsAt: D("2027-04-13"), endsAt: D("2027-07-15"), isCurrent: false } });
    termsBySchool[code] = { prevT3: t_p3.id, curT1: t1.id, curT2: t2.id, curT3: t3.id };
  }

  // ─────────────────────────────────────────────
  // 4. CLASSROOMS + SUBJECTS + STAFF
  // ─────────────────────────────────────────────
  console.log("🪑 Classrooms…");
  type ClassDef = { name: string; grade: string; stage: string; n: number };
  const classDefs: Record<string, ClassDef[]> = {
    GEA: [
      { name: "KG 1 — Sunflower", grade: "KG1", stage: "Kindergarten (SBC)", n: 9 },
      { name: "KG 2 — Rose", grade: "KG2", stage: "Kindergarten (SBC)", n: 10 },
      { name: "Basic 1", grade: "B1", stage: "Primary (SBC)", n: 11 },
      { name: "Basic 2", grade: "B2", stage: "Primary (SBC)", n: 10 },
      { name: "Basic 3", grade: "B3", stage: "Primary (SBC)", n: 12 },
      { name: "Basic 4 — Gold", grade: "B4", stage: "Primary (SBC)", n: 12 },
      { name: "Basic 5 — Green", grade: "B5", stage: "Primary (SBC)", n: 11 },
      { name: "Basic 6 — Gold", grade: "B6", stage: "Primary (SBC)", n: 12 },
      { name: "JHS 1 (B7)", grade: "B7", stage: "Common Core (CCP)", n: 13 },
      { name: "JHS 2 (B8)", grade: "B8", stage: "Common Core (CCP)", n: 12 },
      { name: "JHS 3 (B9)", grade: "B9", stage: "Common Core (CCP)", n: 13 },
    ],
    GKA: [
      { name: "Basic 2 — Emerald", grade: "B2", stage: "Primary (SBC)", n: 8 },
      { name: "Basic 4 — Ruby", grade: "B4", stage: "Primary (SBC)", n: 9 },
      { name: "Basic 5 — Sapphire", grade: "B5", stage: "Primary (SBC)", n: 8 },
      { name: "Basic 6 — Diamond", grade: "B6", stage: "Primary (SBC)", n: 9 },
      { name: "JHS 1", grade: "B7", stage: "Common Core (CCP)", n: 10 },
      { name: "JHS 2", grade: "B8", stage: "Common Core (CCP)", n: 9 },
    ],
    GKO: [
      { name: "Basic 1", grade: "B1", stage: "Primary (SBC)", n: 7 },
      { name: "Basic 3", grade: "B3", stage: "Primary (SBC)", n: 7 },
      { name: "Basic 5", grade: "B5", stage: "Primary (SBC)", n: 7 },
      { name: "Basic 6", grade: "B6", stage: "Primary (SBC)", n: 7 },
    ],
    GKB: [
      { name: "KG 2", grade: "KG2", stage: "Kindergarten (SBC)", n: 7 },
      { name: "Basic 2", grade: "B2", stage: "Primary (SBC)", n: 7 },
      { name: "Basic 4", grade: "B4", stage: "Primary (SBC)", n: 7 },
    ],
    NNP: [
      { name: "Basic 1", grade: "B1", stage: "Primary (SBC)", n: 8 },
      { name: "Basic 3", grade: "B3", stage: "Primary (SBC)", n: 8 },
      { name: "Basic 4", grade: "B4", stage: "Primary (SBC)", n: 8 },
      { name: "Basic 5", grade: "B5", stage: "Primary (SBC)", n: 8 },
      { name: "JHS 2", grade: "B8", stage: "Common Core (CCP)", n: 8 },
    ],
    SGI: [
      { name: "Year 2", grade: "Y2", stage: "Cambridge Primary", n: 6 },
      { name: "Year 4", grade: "Y4", stage: "Cambridge Primary", n: 6 },
      { name: "Year 6", grade: "Y6", stage: "Cambridge Primary", n: 6 },
      { name: "Year 8", grade: "Y8", stage: "Cambridge Lower Secondary", n: 6 },
      { name: "Year 10 (IGCSE)", grade: "Y10", stage: "Cambridge Upper Secondary / IGCSE", n: 6 },
    ],
    SES: [
      { name: "PYP 2", grade: "PYP2", stage: "IB Primary Years Programme", n: 5 },
      { name: "PYP 4", grade: "PYP4", stage: "IB Primary Years Programme", n: 5 },
      { name: "MYP 1", grade: "MYP1", stage: "IB Middle Years Programme", n: 5 },
      { name: "MYP 3", grade: "MYP3", stage: "IB Middle Years Programme", n: 5 },
      { name: "DP 1", grade: "DP1", stage: "IB Diploma Programme", n: 5 },
    ],
    ALS: [
      { name: "Basic 2", grade: "B2", stage: "Primary (SBC)", n: 8 },
      { name: "Basic 4", grade: "B4", stage: "Primary (SBC)", n: 8 },
      { name: "Basic 6", grade: "B6", stage: "Primary (SBC)", n: 8 },
      { name: "JHS 1", grade: "B7", stage: "Common Core (CCP)", n: 8 },
      { name: "JHS 3", grade: "B9", stage: "Common Core (CCP)", n: 8 },
    ],
    BFI: [
      { name: "Year 3", grade: "Y3", stage: "Cambridge Primary", n: 7 },
      { name: "Year 5", grade: "Y5", stage: "Cambridge Primary", n: 7 },
      { name: "Year 7", grade: "Y7", stage: "Cambridge Lower Secondary", n: 7 },
      { name: "Year 10 (IGCSE)", grade: "Y10", stage: "Cambridge Upper Secondary / IGCSE", n: 7 },
    ],
    EBA: [
      { name: "KG 2", grade: "KG2", stage: "Kindergarten (SBC)", n: 8 },
      { name: "Basic 3", grade: "B3", stage: "Primary (SBC)", n: 8 },
      { name: "Basic 5", grade: "B5", stage: "Primary (SBC)", n: 8 },
      { name: "Basic 6", grade: "B6", stage: "Primary (SBC)", n: 8 },
      { name: "JHS 2", grade: "B8", stage: "Common Core (CCP)", n: 8 },
    ],
  };

  const classroomBySchool: Record<string, { id: string; name: string; grade: string }[]> = {};
  for (const [code, defs] of Object.entries(classDefs)) {
    classroomBySchool[code] = [];
    for (const cd of defs) {
      const c = await db.classroom.create({ data: { schoolId: schools[code].id, name: cd.name, gradeLevel: cd.grade, stageLabel: cd.stage, capacity: 30 } });
      classroomBySchool[code].push({ id: c.id, name: cd.name, grade: cd.grade });
    }
  }

  // Subjects
  console.log("📚 Subjects…");
  const subjDefs: Record<string, [string, string][]> = {
    GES_P: [["English Language", "ENG"], ["Mathematics", "MAT"], ["Science", "SCI"], ["Our World Our People", "OWOP"], ["Ghanaian Language (Twi)", "GHL"], ["Creative Arts", "CRA"], ["Religious & Moral Education", "RME"], ["Computing", "ICT"]],
    GES_J: [["English Language", "ENG"], ["Mathematics", "MAT"], ["Integrated Science", "SCI"], ["Social Studies", "SOS"], ["Religious & Moral Education", "RME"], ["Career Technology", "CTE"], ["Computing", "ICT"], ["French", "FRE"], ["Ghanaian Language (Twi)", "GHL"]],
    CAIE: [["Cambridge English", "ENG"], ["Cambridge Mathematics", "MAT"], ["Cambridge Science", "SCI"], ["Global Perspectives", "GLP"], ["ICT Starters", "ICT"], ["French", "FRE"]],
    IB: [["Language & Inquiry", "LAN"], ["Mathematics", "MAT"], ["Science & Society", "SCI"], ["The Arts", "ART"], ["Physical & Health Education", "PHE"]],
  };
  const subjectsBySchool: Record<string, Record<string, string>> = {}; // school code -> subj code -> id
  for (const code of Object.keys(schools)) {
    subjectsBySchool[code] = {};
    const cur = schools[code].curriculum;
    const set = cur === "GES" ? [...subjDefs.GES_P, ...subjDefs.GES_J.filter((s) => !subjDefs.GES_P.some((p) => p[0] === s[0]))] : cur === "CAIE" ? subjDefs.CAIE : subjDefs.IB;
    for (const [name, sc] of set) {
      const s = await db.subject.create({ data: { schoolId: schools[code].id, name, code: sc, curriculum: cur } });
      subjectsBySchool[code][sc] = s.id;
    }
  }

  // Staff
  console.log("👩‍🏫 Staff…");
  const graceStaff = await db.staff.create({ data: { schoolId: schools.GEA.id, fullName: "Madam Grace Mensah", role: "HEADTEACHER", title: "Headteacher", email: "head@nyansapo.gh", phone: "+233 24 111 2233" } });
  const isaacStaff = await db.staff.create({ data: { schoolId: schools.GEA.id, fullName: "Mr. Isaac Boateng", role: "TEACHER", title: "Class Teacher — Basic 4 Gold", email: "teacher@nyansapo.gh", phone: "+233 20 888 4455", subjects: "Mathematics, Science" } });
  const patrickStaff = await db.staff.create({ data: { schoolId: schools.GEA.id, fullName: "Mr. Patrick Quaye", role: "BURSAR", title: "Bursar", email: "bursar@nyansapo.gh", phone: "+233 24 555 6677" } });
  const abenaStaff = await db.staff.create({ data: { schoolId: schools.GEA.id, fullName: "Madam Abena Osei", role: "TEACHER", title: "Class Teacher — Basic 6 Gold", subjects: "English, OWOP" } });
  const kwameDuahStaff = await db.staff.create({ data: { schoolId: schools.GEA.id, fullName: "Mr. Kwame Duah", role: "TEACHER", title: "Class Teacher — JHS 3 (B9)", subjects: "Mathematics, Career Technology" } });
  const josephineStaff = await db.staff.create({ data: { schoolId: schools.GKA.id, fullName: "Mrs. Josephine Agyemang", role: "HEADTEACHER", title: "Headteacher — Adenta Main", email: "head.gka@nyansapo.gh" } });
  // assign class teachers
  const gcl = classroomBySchool.GEA;
  await db.classroom.update({ where: { id: gcl[5].id }, data: { classTeacherId: isaacStaff.id } }); // B4 Gold
  await db.classroom.update({ where: { id: gcl[7].id }, data: { classTeacherId: abenaStaff.id } }); // B6 Gold
  await db.classroom.update({ where: { id: gcl[10].id }, data: { classTeacherId: kwameDuahStaff.id } }); // B9

  // ─────────────────────────────────────────────
  // 5. STUDENTS + ENROLLMENTS
  // ─────────────────────────────────────────────
  console.log("🎓 Students…");
  let idxSeq = 1;
  const studentsBySchoolClass: Record<string, Record<string, any[]>> = {}; // schoolcode -> classroomId -> students
  const gradeBirth: Record<string, number> = { KG1: 2021, KG2: 2020, B1: 2019, B2: 2018, B3: 2017, B4: 2016, B5: 2015, B6: 2014, B7: 2013, B8: 2012, B9: 2011, Y2: 2020, Y3: 2019, Y4: 2018, Y5: 2017, Y6: 2016, Y7: 2015, Y8: 2014, Y10: 2012, PYP2: 2020, PYP4: 2018, MYP1: 2014, MYP3: 2012, DP1: 2010 };

  for (const [code, classes] of Object.entries(classroomBySchool)) {
    studentsBySchoolClass[code] = {};
    for (const cls of classes) {
      studentsBySchoolClass[code][cls.id] = [];
      for (let i = 0; i < (classDefs[code].find((c) => c.name === cls.name)?.n ?? 8); i++) {
        const gender = rnd() > 0.5 ? "M" : "F";
        const nm = ghanaianName(gender);
        const birthYear = gradeBirth[cls.grade] ?? 2016;
        const gname = gender === "M" ? female() : pick([male(), female()]);
        const st = await db.student.create({
          data: {
            nyansapoIndex: `NY-${birthYear}-${String(100000 + idxSeq++).slice(1)}`,
            firstName: nm.first,
            lastName: nm.last,
            otherNames: nm.other,
            gender,
            dateOfBirth: D(`${birthYear}-${String(int(1, 12)).padStart(2, "0")}-${String(int(1, 28)).padStart(2, "0")}`),
            guardianName: gname,
            guardianPhone: phone(),
            guardianOccupation: pick(OCCUP),
            guardianAddress: pick(TOWNS_STREETS),
            relationship: rnd() > 0.75 ? "Guardian" : rnd() > 0.5 ? "Father" : "Mother",
            hometown: pick(["Kumasi","Cape Coast","Ho","Koforidua","Tamale","Takoradi","Ada","Akosombo","Nsawam","Kasoa","Winneba","Aflao"]),
            status: "ACTIVE",
          },
        });
        const enr = await db.enrollment.create({
          data: { studentId: st.id, classroomId: cls.id, schoolId: schools[code].id, rollNo: i + 1, admissionDate: D(`${2020 + int(0, 6)}-09-${String(int(1, 28)).padStart(2, "0")}`), status: "ACTIVE" },
        });
        studentsBySchoolClass[code][cls.id].push({ student: st, enrollment: enr });
      }
    }
  }

  // ── showcase student: Kofi Boateng (the defaulter story) ──
  const comfortPhone = "+233 24 477 8123";
  const kofi = await db.student.create({
    data: {
      nyansapoIndex: "NY-2014-004872",
      firstName: "Kofi", lastName: "Boateng", otherNames: "Nana",
      gender: "M", dateOfBirth: D("2014-06-12"),
      guardianName: "Comfort Boateng", guardianPhone: comfortPhone, guardianAltPhone: "+233 55 100 2233",
      guardianOccupation: "Trader (Makola)", guardianAddress: "House No. 21, Ogbojo", relationship: "Mother", hometown: "Akosombo",
      status: "TRACKED",
    },
  });
  // Kofi's enrollment history: NNP B2 (2021) → GEA B6 (2023, withdrawn Oct 2026)
  const nnpB = classroomBySchool.NNP.find((c) => c.grade === "B3")!;
  await db.enrollment.create({ data: { studentId: kofi.id, classroomId: classroomBySchool.GEA[7].id, schoolId: schools.GEA.id, rollNo: 99, admissionDate: D("2023-01-10"), exitDate: D("2026-10-12"), status: "TRANSFERRED", exitReason: "Parent withdrew while owing fees" } });
  // A few named students for storytelling
  const b6Gold = classroomBySchool.GEA[7].id;
  const b4Gold = classroomBySchool.GEA[5].id;
  const b9Class = classroomBySchool.GEA[10].id;

  // Adwoa Sarpong — transferred INTO GEA B6 from EBA in Jan 2026 (clean transfer)
  const adwoa = await db.student.create({ data: { nyansapoIndex: "NY-2014-007321", firstName: "Adwoa", lastName: "Sarpong", gender: "F", dateOfBirth: D("2014-02-20"), guardianName: "Mr. Yaw Sarpong", guardianPhone: "+233 20 331 9987", guardianOccupation: "Civil Servant", guardianAddress: "Madina Market Street", relationship: "Father", hometown: "Winneba", status: "ACTIVE" } });
  await db.enrollment.create({ data: { studentId: adwoa.id, classroomId: b6Gold, schoolId: schools.GEA.id, admissionDate: D("2026-01-12"), status: "ACTIVE", rollNo: 98 } });

  // Daniel Tetteh — GKO B3, resolved WITHDREW_OWING flag (paid later)
  const daniel = studentsBySchoolClass.GKO[classroomBySchool.GKO[1].id][2].student;

  // Elikem Attah — NNP B4 current arrears
  const elikem = studentsBySchoolClass.NNP[classroomBySchool.NNP[2].id][0].student;
  await db.student.update({ where: { id: elikem.id }, data: { firstName: "Elikem", lastName: "Attah", guardianName: "Mr. Setor Attah", guardianPhone: "+233 27 220 4411", guardianOccupation: "Driver" } });

  // Abigail Mensah — EBA B5 arrears
  const ebaB5 = classroomBySchool.EBA[2].id;
  const abigail = studentsBySchoolClass.EBA[ebaB5][1].student;
  await db.student.update({ where: { id: abigail.id }, data: { firstName: "Abigail", lastName: "Mensah" } });

  // Yaw Darko — ALS JHS1 discipline suspension
  const yawD = studentsBySchoolClass.ALS[classroomBySchool.ALS[3].id][0].student;
  await db.student.update({ where: { id: yawD.id }, data: { firstName: "Yaw", lastName: "Darko" } });

  // Priscilla Owusu — GEA B9 exam malpractice (contested)
  const priscilla = studentsBySchoolClass.GEA[b9Class][3].student;
  await db.student.update({ where: { id: priscilla.id }, data: { firstName: "Priscilla", lastName: "Owusu" } });

  console.log(`   ${idxSeq + 7} students created`);

  // ─────────────────────────────────────────────
  // 6. FEE STRUCTURES (current term, per school)
  // ─────────────────────────────────────────────
  console.log("💰 Fee structures…");
  const feePlans: Record<string, { name: string; cedis: number; optional?: boolean }[]> = {
    GEA: [{ name: "Tuition", cedis: 1850 }, { name: "Examination Fee", cedis: 150 }, { name: "Books & Stationery", cedis: 260 }, { name: "Sports & Games", cedis: 100 }, { name: "ICT Lab", cedis: 100 }, { name: "Meals (Optional)", cedis: 400, optional: true }, { name: "Transport (Optional)", cedis: 500, optional: true }],
    GKA: [{ name: "Tuition", cedis: 2000 }, { name: "Examination Fee", cedis: 160 }, { name: "Books & Stationery", cedis: 260 }, { name: "Sports & Games", cedis: 100 }, { name: "ICT Lab", cedis: 90 }],
    GKO: [{ name: "Tuition", cedis: 1850 }, { name: "Examination Fee", cedis: 150 }, { name: "Books & Stationery", cedis: 240 }, { name: "Sports & Games", cedis: 90 }, { name: "ICT Lab", cedis: 85 }],
    GKB: [{ name: "Tuition", cedis: 1850 }, { name: "Examination Fee", cedis: 150 }, { name: "Books & Stationery", cedis: 240 }, { name: "Sports & Games", cedis: 90 }, { name: "ICT Lab", cedis: 85 }],
    NNP: [{ name: "Tuition", cedis: 450 }, { name: "Examination Fee", cedis: 50 }, { name: "Books & Stationery", cedis: 120 }, { name: "Sports", cedis: 30 }, { name: "ICT", cedis: 40 }],
    EBA: [{ name: "Tuition", cedis: 1750 }, { name: "Examination Fee", cedis: 150 }, { name: "Books & Stationery", cedis: 250 }, { name: "Sports", cedis: 80 }, { name: "ICT", cedis: 80 }],
    ALS: [{ name: "Tuition", cedis: 1600 }, { name: "Examination Fee", cedis: 120 }, { name: "Books & Stationery", cedis: 220 }, { name: "Sports", cedis: 80 }, { name: "ICT", cedis: 70 }],
    SGI: [{ name: "Tuition", cedis: 11500 }, { name: "Examination Fee", cedis: 800 }, { name: "Cambridge Resources", cedis: 1200 }, { name: "Meals (Optional)", cedis: 1800, optional: true }, { name: "Transport (Optional)", cedis: 2200, optional: true }],
    BFI: [{ name: "Tuition", cedis: 4200 }, { name: "Cambridge Examination", cedis: 400 }, { name: "Cambridge Resources", cedis: 600 }, { name: "Sports & Co-curricular", cedis: 150 }],
    SES: [{ name: "Tuition", cedis: 13500 }, { name: "IB Programme Resources", cedis: 1500 }, { name: "Meals (Optional)", cedis: 1800, optional: true }, { name: "Transport (Optional)", cedis: 2500, optional: true }],
  };
  const feeStructureByClass: Record<string, string> = {}; // classroomId -> feeStructureId
  for (const [code, plan] of Object.entries(feePlans)) {
    for (const cls of classroomBySchool[code]) {
      const core = plan.filter((p) => !p.optional);
      const total = core.reduce((s, p) => s + CENTS(p.cedis), 0);
      const fs = await db.feeStructure.create({ data: { schoolId: schools[code].id, classroomId: cls.id, termId: termsBySchool[code].curT1, totalCents: total } });
      for (const item of plan) {
        await db.feeItem.create({ data: { feeStructureId: fs.id, name: item.name, amountCents: CENTS(item.cedis), isOptional: !!item.optional } });
      }
      feeStructureByClass[cls.id] = fs.id;
    }
  }

  // ─────────────────────────────────────────────
  // 7. INVOICES + PAYMENTS (current term)
  // ─────────────────────────────────────────────
  console.log("🧾 Invoices & payments…");
  let invSeq = 1, rcpSeq = 1;
  const methods = ["MOMO_MTN", "MOMO_MTN", "MOMO_MTN", "MOMO_MTN", "MOMO_MTN", "MOMO_TELECEL", "MOMO_AT", "MOMO_AT", "CASH", "CASH", "CASH", "BANK", "BANK", "CHEQUE"];

  async function billStudent(schoolCode: string, classroomId: string, studentId: string, override?: { status?: string; paidCents?: number; withOptional?: boolean }) {
    const plan = feePlans[schoolCode];
    const core = plan.filter((p) => !p.optional);
    const items = override?.withOptional ? plan : core;
    const total = items.reduce((s, p) => s + CENTS(p.cedis), 0);
    const cls = classroomBySchool[schoolCode].find((c) => c.id === classroomId)!;
    let paid = override?.paidCents ?? (override?.status === "PAID" ? total : 0);
    if (!override?.paidCents && override?.status === "PARTIAL") paid = Math.round(total * (0.3 + rnd() * 0.4));
    let status = override?.status ?? (paid <= 0 ? "UNPAID" : paid >= total ? "PAID" : "PARTIAL");
    if (status === "UNPAID" && rnd() < 0.3) status = "OVERDUE";
    const inv = await db.invoice.create({
      data: {
        invoiceNo: `INV-2026-${String(invSeq++).padStart(4, "0")}`,
        studentId, schoolId: schools[schoolCode].id, termId: termsBySchool[schoolCode].curT1,
        classroomName: cls.name, totalCents: total, paidCents: Math.min(paid, total),
        status, dueDate: D("2026-10-15"), createdAt: D("2026-09-08"),
      },
    });
    for (const it of items) await db.invoiceItem.create({ data: { invoiceId: inv.id, name: it.name, amountCents: CENTS(it.cedis) } });
    // payments
    let remaining = Math.min(paid, total);
    while (remaining > 0) {
      const chunk = remaining === total ? (rnd() < 0.7 ? total : Math.round(total * 0.6)) : Math.min(remaining, Math.round(total * (rnd() < 0.5 ? 0.5 : 0.35)));
      const payDate = D(`2026-09-${String(int(8, 30)).padStart(2, "0")}T0${int(8, 9)}:${String(int(10, 59))}:00`);
      await db.payment.create({
        data: {
          receiptNo: `RCP-2026-${String(rcpSeq++).padStart(5, "0")}`, invoiceId: inv.id, studentId: inv.studentId, schoolId: schools[schoolCode].id,
          amountCents: chunk, method: pick(methods), reference: rnd() < 0.5 ? `MP${int(100000, 999999).toString()}.{GCASH}` : null,
          receivedByName: schoolCode === "GEA" ? "Mr. Patrick Quaye" : "Bursar", paidAt: payDate,
        },
      });
      remaining -= chunk;
      if (remaining < CENTS(20)) remaining = 0;
    }
    return inv;
  }

  for (const [code, classes] of Object.entries(studentsBySchoolClass)) {
    for (const [clsId, list] of Object.entries(classes)) {
      for (const { student } of list) {
        const r = rnd();
        const status = r < 0.45 ? "PAID" : r < 0.72 ? "PARTIAL" : "UNPAID";
        await billStudent(code, clsId, student.id, { status, withOptional: rnd() < 0.3, paidCents: status === "PARTIAL" ? undefined : undefined });
      }
    }
  }

  // Showcase invoices
  // Kofi Boateng: GEA B6 total with meals+transport = 3360; paid 1510 → owes 1850
  await billStudent("GEA", b6Gold, kofi.id, { paidCents: CENTS(1510), withOptional: true, status: "PARTIAL" });
  // Adwoa: PAID with sibling discount
  const adwoaInv = await billStudent("GEA", b6Gold, adwoa.id, { status: "PAID", withOptional: true });
  await db.invoice.update({ where: { id: adwoaInv.id }, data: { discountCents: CENTS(246), discountReason: "Sibling discount (10%)" } });
  // Elikem NNP: owes 600 → paid 90 of 690
  await db.student.update({ where: { id: elikem.id }, data: { status: "ACTIVE" } });
  // Elikem's default invoice already created above; adjust it:
  const elikemInv = await db.invoice.findFirst({ where: { studentId: elikem.id } });
  if (elikemInv) await db.invoice.update({ where: { id: elikemInv.id }, data: { paidCents: CENTS(90), status: "OVERDUE" } });
  // Abigail EBA: owes 920 → paid 1390 of 2310
  const abInv = await db.invoice.findFirst({ where: { studentId: abigail.id } });
  if (abInv) await db.invoice.update({ where: { id: abInv.id }, data: { paidCents: CENTS(1390), status: "OVERDUE" } });
  // Daniel GKO: RESOLVED — fully paid after follow-up
  const dInv = await db.invoice.findFirst({ where: { studentId: daniel.id } });
  if (dInv) {
    await db.invoice.update({ where: { id: dInv.id }, data: { paidCents: dInv.totalCents, status: "PAID" } });
    await db.payment.create({ data: { receiptNo: `RCP-2026-${String(rcpSeq++).padStart(5, "0")}`, invoiceId: dInv.id, studentId: dInv.studentId, schoolId: schools.GKO.id, amountCents: CENTS(540), method: "MOMO_MTN", reference: "MP238812.7742_GCASH", receivedByName: "Bursar", paidAt: D("2026-09-28T10:12:00") } });
  }

  // ─────────────────────────────────────────────
  // 8. ATTENDANCE (GEA all classes + GKA, last 10 school days)
  // ─────────────────────────────────────────────
  console.log("📋 Attendance…");
  const days = lastSchoolDays(10, D("2026-10-30"));
  for (const [code, classes] of Object.entries({ GEA: 1, GKA: 1 } as any)) {
    for (const cls of classroomBySchool[code]) {
      const list = studentsBySchoolClass[code][cls.id] || [];
      if (!list.length) continue;
      for (const day of days) {
        const session = await db.attendanceSession.create({ data: { classroomId: cls.id, schoolId: schools[code].id, termId: termsBySchool[code].curT1, date: day, takenByName: cls.name.includes("4 — Gold") ? "Mr. Isaac Boateng" : "Class Teacher" } });
        for (const { student } of list) {
          const r = rnd();
          const status = r < 0.88 ? "PRESENT" : r < 0.93 ? "ABSENT" : r < 0.97 ? "LATE" : "SICK";
          await db.attendanceRecord.create({ data: { sessionId: session.id, studentId: student.id, status } });
        }
      }
    }
  }
  // Adwoa joined current classes too
  {
    const session = await db.attendanceSession.create({ data: { classroomId: b6Gold, schoolId: schools.GEA.id, termId: termsBySchool.GEA.curT1, date: days[days.length - 1], takenByName: "Madam Abena Osei" } });
    await db.attendanceRecord.create({ data: { sessionId: session.id, studentId: adwoa.id, status: "PRESENT" } });
  }

  // ─────────────────────────────────────────────
  // 9. ASSESSMENTS + MARKS (GEA: B4, B5, B6, B9)
  // ─────────────────────────────────────────────
  console.log("✏️  Gradebook…");
  const assessPlan: { clsIdx: number; subj: string[]; types: [string, string, number][] }[] = [
    { clsIdx: 5, subj: ["ENG", "MAT", "SCI", "OWOP"], types: [["Class Exercise 1", "EXERCISE", 10], ["Class Exercise 2", "EXERCISE", 10], ["Quiz 1", "QUIZ", 20], ["Project — My Community", "PROJECT", 20]] },
    { clsIdx: 6, subj: ["ENG", "MAT", "SCI"], types: [["Class Exercise 1", "EXERCISE", 10], ["Quiz 1", "QUIZ", 20]] },
    { clsIdx: 7, subj: ["ENG", "MAT", "SCI", "OWOP"], types: [["Class Exercise 1", "EXERCISE", 10], ["Class Exercise 2", "EXERCISE", 10], ["Quiz 1", "QUIZ", 20], ["Group Work — Ghana Regions", "GROUP_WORK", 10]] },
    { clsIdx: 10, subj: ["ENG", "MAT", "SCI", "SOS", "CTE"], types: [["Class Exercise 1", "EXERCISE", 10], ["Quiz 1", "QUIZ", 20], ["Quiz 2", "QUIZ", 20]] },
  ];
  const marksByStudent: Record<string, { subj: string; score: number; max: number }[]> = {};
  for (const plan of assessPlan) {
    const cls = classroomBySchool.GEA[plan.clsIdx];
    const list = studentsBySchoolClass.GEA[cls.id].map((x: any) => x.student);
    if (plan.clsIdx === 7) list.push(adwoa);
    for (const subjCode of plan.subj) {
      for (const [title, type, max] of plan.types) {
        const subjId = subjectsBySchool.GEA[subjCode];
        const a = await db.assessment.create({ data: { classroomId: cls.id, schoolId: schools.GEA.id, termId: termsBySchool.GEA.curT1, subjectId: subjId, title, type, maxScore: max, date: D(`2026-10-${String(int(5, 28)).padStart(2, "0")}`) } });
        for (const st of list) {
          const base = 0.45 + rnd() * 0.5;
          const score = Math.max(0, Math.min(max, Math.round(max * base)));
          await db.mark.create({ data: { assessmentId: a.id, studentId: st.id, score } });
          if (!marksByStudent[st.id]) marksByStudent[st.id] = [];
          marksByStudent[st.id].push({ subj: subjCode, score, max });
        }
      }
    }
  }

  // ─────────────────────────────────────────────
  // 10. REPORT CARDS (previous year, Third Term — B4, B6, B9)
  // ─────────────────────────────────────────────
  console.log("📄 Report cards (2025/26 Term 3)…");
  const prevSubjects: [number, string[]][] = [[5, ["English Language", "Mathematics", "Science", "Our World Our People", "Ghanaian Language (Twi)", "Creative Arts", "RME"]], [7, ["English Language", "Mathematics", "Science", "Our World Our People", "Ghanaian Language (Twi)", "Creative Arts", "RME"]], [10, ["English Language", "Mathematics", "Integrated Science", "Social Studies", "RME", "Career Technology", "Computing"]]];
  const gesGrade = (pct: number) => pct >= 80 ? "1 (Advanced)" : pct >= 68 ? "2 (Proficient)" : pct >= 55 ? "3 (Approaching)" : pct >= 40 ? "4 (Developing)" : "5 (Beginning)";
  const conductPool = ["Excellent", "Very Good", "Good"];
  const interestPool = ["Football, Science Club", "Reading, Debate Club", "Music, Cultural Dance", "Athletics, Maths Club", "Art, Chess Club"];
  let posSeq = 1;
  for (const [clsIdx, subjs] of prevSubjects) {
    const cls = classroomBySchool.GEA[clsIdx];
    const list = studentsBySchoolClass.GEA[cls.id].map((x: any) => x.student);
    if (clsIdx === 7) list.push(adwoa);
    const perf = list.map((st: any) => ({ st, avg: 45 + rnd() * 50 })).sort((a: any, b: any) => b.avg - a.avg);
    for (let i = 0; i < perf.length; i++) {
      const { st, avg } = perf[i];
      const rc = await db.reportCard.create({ data: { studentId: st.id, termId: termsBySchool.GEA.prevT3, classroomName: cls.name, totalScore: Math.round(avg * subjs.length), average: Math.round(avg), classPosition: i + 1, classSize: perf.length, conduct: pick(conductPool), interest: pick(interestPool), attendance: `Present ${int(34, 42)} of 42 days`, promotion: avg >= 40 ? "PROMOTED" : "PROBATION", published: true, publishedAt: D("2026-07-22"), teacherRemark: `${st.firstName} worked steadily this term. Keep practising mathematics at home.`, headRemark: "A satisfactory result. We look forward to greater heights next term." } });
      for (const s of subjs) {
        const classScore = Math.round(30 * Math.min(1, Math.max(0.3, avg / 100 + (rnd() - 0.5) * 0.2)));
        const examScore = Math.round(70 * Math.min(1, Math.max(0.25, avg / 100 + (rnd() - 0.5) * 0.15)));
        const total = classScore + examScore;
        await db.reportCardResult.create({ data: { reportCardId: rc.id, subjectName: s, classScore, examScore, total, grade: gesGrade(total), remark: total >= 68 ? "Commendable" : total >= 55 ? "Good effort" : "Needs support" } });
      }
    }
  }

  // ─────────────────────────────────────────────
  // 11. FLAGS, TRANSFERS, RECORD REQUESTS, INQUIRIES
  // ─────────────────────────────────────────────
  console.log("🚩 Flags & transfer registry…");
  const flagKofi = await db.studentFlag.create({
    data: { studentId: kofi.id, schoolId: schools.GEA.id, raisedByName: "Madam Grace Mensah", type: "WITHDREW_OWING", severity: "CRITICAL", status: "ACTIVE", amountOwedCents: CENTS(1850), description: "Parent (Comfort Boateng) withdrew Kofi on 12 Oct 2026 with an outstanding balance of GH₵1,850.00 for First Term 2026/2027, including GH₵600 carried over from Third Term 2025/2026. Multiple reminders sent; guardian promised payment on 3 occasions." , raisedAt: D("2026-10-13") },
  });
  await db.flagVerification.create({ data: { flagId: flagKofi.id, officerName: "Mr. Samuel Adjei", officerTitle: "Ga East District Coordinator", action: "CONFIRMED", note: "School records verified during the Ga East district spot check on 20 Oct 2026. Arrears confirmed at GH₵1,850.00.", verifiedAt: D("2026-10-20") } });

  const flagElikem = await db.studentFlag.create({ data: { studentId: elikem.id, schoolId: schools.NNP.id, raisedByName: "Mr. Tetteh Nortey (Headteacher)", type: "FEE_ARREARS", severity: "MEDIUM", status: "ACTIVE", amountOwedCents: CENTS(600), description: "First Term 2026/2027 balance of GH₵600.00 outstanding despite the agreed two-instalment plan. Guardian has been served two reminders.", raisedAt: D("2026-10-18") } });

  const flagAbigail = await db.studentFlag.create({ data: { studentId: abigail.id, schoolId: schools.EBA.id, raisedByName: "Mrs. Patience Sowah (Headteacher)", type: "FEE_ARREARS", severity: "HIGH", status: "ACTIVE", amountOwedCents: CENTS(920), description: "Third consecutive term with arrears. Current balance GH₵920.00. Guardian contacted by phone on 14 Oct 2026.", raisedAt: D("2026-10-15") } });

  const flagYaw = await db.studentFlag.create({ data: { studentId: yawD.id, schoolId: schools.ALS.id, raisedByName: "Mr. Yaw Baffour (Headteacher)", type: "DISCIPLINE_SUSPENSION", severity: "HIGH", status: "ACTIVE", description: "Suspended for two weeks (5–16 Oct 2026) following repeated fighting on campus and failure to respond to two prior warnings. Counselling sessions recommended before re-admission.", raisedAt: D("2026-10-05") } });

  const flagPriscilla = await db.studentFlag.create({ data: { studentId: priscilla.id, schoolId: schools.GEA.id, raisedByName: "Mr. Kwame Duah", type: "EXAM_MALPRACTICE", severity: "MEDIUM", status: "CONTESTED", description: "Found with unauthorised notes during the Third Term 2025/2026 Mathematics paper. Guardian contests the finding and has appealed to the Headteacher.", raisedAt: D("2026-07-15") } });
  await db.flagVerification.create({ data: { flagId: flagPriscilla.id, officerName: "Mr. Ebenezer Clottey", officerTitle: "Madina Zone Representative", action: "EVIDENCE_REQUESTED", note: "Invigilator's report and script requested from the school before the flag can stand.", verifiedAt: D("2026-07-24") } });

  const flagDaniel = await db.studentFlag.create({ data: { studentId: daniel.id, schoolId: schools.GKO.id, raisedByName: "Oyarifa Branch Headteacher", type: "WITHDREW_OWING", severity: "MEDIUM", status: "RESOLVED", amountOwedCents: CENTS(540), description: "Withdrew at end of 2025/2026 owing GH₵540.00. Balance cleared in full via MTN MoMoo on 28 Sep 2026.", raisedAt: D("2026-07-28"), resolvedAt: D("2026-09-28"), resolutionNote: "Full payment of GH₵540.00 received (receipt RCP-2026-00921). Flag resolved and cleared from the registry.", resolvedByName: "Oyarifa Branch Headteacher" } });

  // Transfer records
  await db.transferRecord.create({ data: { studentId: kofi.id, fromSchoolId: schools.NNP.id, toSchoolId: schools.GEA.id, date: D("2023-01-10"), reason: "Family relocated to Madina", outstandingCents: 0, documentsReleased: true, initiatedBy: "PARENT", status: "COMPLETED" } });
  await db.transferRecord.create({ data: { studentId: kofi.id, fromSchoolId: schools.GEA.id, toSchoolId: schools.GKA.id, date: D("2026-10-26"), reason: "Parent seeking admission at Great Kings Adenta while owing GH₵1,850.00", outstandingCents: CENTS(1850), documentsReleased: false, initiatedBy: "PARENT", status: "PENDING_RELEASE", notes: "Blocked pending settlement of arrears and record release by Golden Excellence Academy." } });
  await db.transferRecord.create({ data: { studentId: adwoa.id, fromSchoolId: schools.EBA.id, toSchoolId: schools.GEA.id, date: D("2026-01-12"), reason: "Closer to family home", outstandingCents: 0, documentsReleased: true, initiatedBy: "PARENT", status: "COMPLETED", notes: "Clean transfer — all fees settled, records released within 3 days." } });
  const kwakuAm = studentsBySchoolClass.GEA[classroomBySchool.GEA[7].id][5].student;
  await db.transferRecord.create({ data: { studentId: kwakuAm.id, fromSchoolId: schools.GEA.id, toSchoolId: schools.GKA.id, date: D("2025-09-15"), reason: "Parent's preference", outstandingCents: 0, documentsReleased: true, initiatedBy: "PARENT", status: "COMPLETED" } });
  const efuaD = studentsBySchoolClass.BFI[classroomBySchool.BFI[2].id][3].student;
  await db.student.update({ where: { id: efuaD.id }, data: { firstName: "Efua", lastName: "Danso" } });
  await db.transferRecord.create({ data: { studentId: efuaD.id, fromSchoolId: schools.ALS.id, toSchoolId: schools.BFI.id, date: D("2026-04-20"), reason: "Switching to Cambridge pathway", outstandingCents: CENTS(320), documentsReleased: true, initiatedBy: "PARENT", status: "COMPLETED", notes: "GH₵320 balance settled by guardian one week after transfer." } });

  // Record requests
  const rrPending = await db.recordRequest.create({ data: { studentId: kofi.id, requestingSchoolId: schools.GKA.id, targetSchoolId: schools.GEA.id, requestedByName: "Mrs. Josephine Agyemang (Headteacher, GKA Adenta)", requestedAt: D("2026-10-27T09:32:00"), purpose: "ADMISSION", status: "PENDING", notes: "Parent applied for Basic 6 admission on 26 Oct 2026. Registry shows an unresolved WITHDREW_OWING flag. Please share academic records and fee position." } });
  await db.requestMessage.create({ data: { requestId: rrPending.id, senderName: "Mrs. Josephine Agyemang", senderTitle: "Headteacher — Great Kings & Queens Academy (Adenta Main)", body: "Good morning Madam Grace. A parent, Mrs. Comfort Boateng, applied for admission for her son Kofi Boateng into Basic 6. The Nyansapo registry shows he was enrolled with you and withdrew on 12 October 2026 with an outstanding balance. Kindly release his academic records and confirm the fee position so we can process the application. Thank you.", sentAt: D("2026-10-27T09:32:00") } });
  const rrOld = await db.recordRequest.create({ data: { studentId: adwoa.id, requestingSchoolId: schools.GEA.id, targetSchoolId: schools.EBA.id, requestedByName: "Madam Grace Mensah (Headteacher, GEA)", requestedAt: D("2026-01-05T10:00:00"), purpose: "ADMISSION", status: "FULFILLED", respondedAt: D("2026-01-07T14:20:00"), respondedByName: "Mrs. Patience Sowah (Headteacher, EBA)", notes: "Records received — clean file, no arrears." } });
  await db.requestMessage.create({ data: { requestId: rrOld.id, senderName: "Madam Grace Mensah", senderTitle: "Headteacher — Golden Excellence Academy", body: "Requesting academic records for Adwoa Sarpong, transferring from your school.", sentAt: D("2026-01-05T10:00:00") } });
  await db.requestMessage.create({ data: { requestId: rrOld.id, senderName: "Mrs. Patience Sowah", senderTitle: "Headteacher — Eben Academy", body: "Records attached. Adwoa was a delight — no disciplinary or fee issues. Wishing her well at Golden Excellence.", sentAt: D("2026-01-07T14:20:00") } });

  // Admission inquiries
  await db.admissionInquiry.create({ data: { schoolId: schools.GKA.id, studentName: "Kofi Boateng", dateOfBirth: D("2014-06-12"), gender: "M", guardianName: "Comfort Boateng", guardianPhone: comfortPhone, classAppliedFor: "Basic 6", matchedStudentId: kofi.id, registryCheckedAt: D("2026-10-26T11:05:00"), registrySummary: JSON.stringify({ matched: true, priorSchools: 2, activeFlags: 1, flagSummary: "WITHDREW_OWING — GH₵1,850.00 (CRITICAL, confirmed by Ga East District)" }), status: "FLAGGED", handledByName: "Mrs. Josephine Agyemang", notes: "Registry match found. Admission on hold pending clearance from Golden Excellence Academy.", createdAt: D("2026-10-26T10:58:00") } });
  await db.admissionInquiry.create({ data: { schoolId: schools.GEA.id, studentName: "Maame Yaa Tetteh", gender: "F", guardianName: "Mrs. Dede Tetteh", guardianPhone: "+233 26 334 1122", classAppliedFor: "Basic 2", status: "NEW", createdAt: D("2026-10-28T08:15:00") } });
  await db.admissionInquiry.create({ data: { schoolId: schools.GEA.id, studentName: "Kwesi Appiah", gender: "M", guardianName: "Mr. John Appiah", guardianPhone: "+233 24 990 3344", classAppliedFor: "JHS 1", matchedStudentId: null, registryCheckedAt: D("2026-10-29T09:00:00"), registrySummary: JSON.stringify({ matched: false }), status: "CLEARED", handledByName: "Madam Grace Mensah", notes: "No registry match — new to private basic education (transferring from a public school). Cleared for entrance assessment.", createdAt: D("2026-10-29T08:47:00") } });
  await db.admissionInquiry.create({ data: { schoolId: schools.SGI.id, studentName: "Aria Clottey", gender: "F", guardianName: "Mr. Nii Clottey", guardianPhone: "+233 20 456 7788", classAppliedFor: "Year 3", status: "NEW", createdAt: D("2026-10-25T12:30:00") } });

  // ─────────────────────────────────────────────
  // 12. RISK SCORES (GEA + flagged students)
  // ─────────────────────────────────────────────
  console.log("🤖 Risk scores…");
  async function computeRisk(studentId: string, schoolId: string) {
    const inv = await db.invoice.findFirst({ where: { studentId }, orderBy: { createdAt: "desc" } });
    const flags = await db.studentFlag.count({ where: { studentId, status: "ACTIVE" } });
    const att = await db.attendanceRecord.findMany({ where: { studentId } });
    const absent = att.filter((r) => r.status === "ABSENT").length;
    const attRate = att.length ? (att.length - absent) / att.length : 1;
    const arrearsRatio = inv && inv.totalCents > 0 ? (inv.totalCents - inv.paidCents) / inv.totalCents : 0;
    const score = Math.min(100, Math.round(arrearsRatio * 55 + (1 - attRate) * 100 * 0.25 + flags * 12));
    const level = score >= 75 ? "CRITICAL" : score >= 50 ? "HIGH" : score >= 25 ? "MODERATE" : "LOW";
    const factors = { arrearsRatio: +arrearsRatio.toFixed(2), attendanceRate: +attRate.toFixed(2), activeFlags: flags };
    const rec = level === "CRITICAL" ? "Immediate guardian conference; consider a signed payment plan before exams." : level === "HIGH" ? "Follow up with guardian this week; monitor attendance daily." : level === "MODERATE" ? "Send friendly fee reminder; keep an eye on attendance." : "No action needed.";
    await db.riskScore.create({ data: { studentId, schoolId, termId: termsBySchool.GEA.curT1, score, level, factorsJson: JSON.stringify(factors), recommendation: rec } });
  }
  const geaStudents = await db.student.findMany({ where: { enrollments: { some: { schoolId: schools.GEA.id, status: "ACTIVE" } } } });
  for (const st of geaStudents) await computeRisk(st.id, schools.GEA.id);
  await computeRisk(elikem.id, schools.NNP.id);
  await computeRisk(abigail.id, schools.EBA.id);

  // ─────────────────────────────────────────────
  // 13. ANNOUNCEMENTS
  // ─────────────────────────────────────────────
  console.log("📣 Announcements…");
  await db.announcement.create({ data: { associationNodeId: NAT.id, title: "National Executive Council Meeting — November 2026", body: "All regional chairpersons and district coordinators are invited to the NEC meeting at the National Secretariat (Atomic Energy, Accra) on Thursday 12 November 2026, 10:00 GMT. Agenda: 2026/2027 compliance drive, teacher licensing with NTC, and the Nyansapo defaulter-registry review.", audience: "NATIONAL", category: "COMPLIANCE", priority: "HIGH", postedByName: "Obenfo Nana Kwasi Gyetuah", postedAt: D("2026-10-20") } });
  await db.announcement.create({ data: { associationNodeId: NAT.id, title: "GES 2026/2027 Basic School Calendar", body: "First Term: 8 September – 17 December 2026 (15 weeks, mid-term break 9–13 November). Second Term: 5 January – 26 March 2027. Third Term: 13 April – 15 July 2027. Member schools should align their calendars accordingly.", audience: "SCHOOLS", category: "CALENDAR", postedByName: "GNACOPS National Secretariat", postedAt: D("2026-09-01") } });
  await db.announcement.create({ data: { associationNodeId: GAR.id, title: "Greater Accra Verification Exercise", body: "The regional council will visit member schools in Accra Metro, Ga East and Tema Metro between 10–21 November 2026 to verify NaSIA licensing status and enrolment returns. Please have your registration documents ready.", audience: "REGIONAL", category: "COMPLIANCE", priority: "HIGH", postedByName: "Madam Gifty Asante", postedAt: D("2026-10-22") } });
  await db.announcement.create({ data: { associationNodeId: GE.id, title: "SBC Refresher for KG & Lower Primary Teachers", body: "The Ga East District presents a Standards-Based Curriculum refresher workshop on Saturday 14 November 2026 at Madina Social Centre, 9:00 GMT. Facilitators from NaCCA. Certificates for attendees. GH₵20 per teacher.", audience: "DISTRICT", category: "TRAINING", postedByName: "Mr. Samuel Adjei", postedAt: D("2026-10-18") } });
  await db.announcement.create({ data: { schoolId: schools.GEA.id, title: "Mid-Term Break — 9 to 13 November", body: "School closes for the mid-term break on Friday 6 November and reopens on Monday 16 November 2026. Parents are reminded that Second Term fees billing opens in December.", audience: "PARENTS", category: "CALENDAR", postedByName: "Madam Grace Mensah", postedAt: D("2026-10-27") } });

  // ─────────────────────────────────────────────
  // 14. USERS (demo accounts)
  // ─────────────────────────────────────────────
  console.log("👤 Users…");
  const pw = "nyansapo";
  await db.user.create({ data: { email: "national@nyansapo.gh", password: pw, fullName: "Obenfo Nana Kwasi Gyetuah", phone: "+233 24 000 1001", role: "ASSOCIATION_NATIONAL", title: "National Executive Director — GNACOPS", associationNodeId: NAT.id } });
  await db.user.create({ data: { email: "regional.gar@nyansapo.gh", password: pw, fullName: "Madam Gifty Asante", phone: "+233 24 000 1002", role: "ASSOCIATION_REGIONAL", title: "Greater Accra Regional Chairperson", associationNodeId: GAR.id } });
  await db.user.create({ data: { email: "district.gaeast@nyansapo.gh", password: pw, fullName: "Mr. Samuel Adjei", phone: "+233 24 000 1003", role: "ASSOCIATION_DISTRICT", title: "Ga East District Coordinator", associationNodeId: GE.id } });
  await db.user.create({ data: { email: "zone.adenta@nyansapo.gh", password: pw, fullName: "Madam Vida Osei", phone: "+233 24 000 1004", role: "ASSOCIATION_ZONAL", title: "Adenta Zone Representative", associationNodeId: AD.id } });
  await db.user.create({ data: { email: "proprietor@nyansapo.gh", password: pw, fullName: "Mr. Emmanuel Osei-Bonsu", phone: "+233 24 400 1188", role: "GROUP_OWNER", title: "Proprietor — Great Kings & Queens Academy", schoolGroupId: GKQ.id } });
  await db.user.create({ data: { email: "head@nyansapo.gh", password: pw, fullName: "Madam Grace Mensah", phone: "+233 24 111 2233", role: "SCHOOL_ADMIN", title: "Headteacher — Golden Excellence Academy", schoolId: schools.GEA.id } });
  await db.user.create({ data: { email: "head.gka@nyansapo.gh", password: pw, fullName: "Mrs. Josephine Agyemang", phone: "+233 24 400 1190", role: "SCHOOL_ADMIN", title: "Headteacher — Great Kings Adenta (Main)", schoolId: schools.GKA.id } });
  await db.user.create({ data: { email: "teacher@nyansapo.gh", password: pw, fullName: "Mr. Isaac Boateng", phone: "+233 20 888 4455", role: "TEACHER", title: "Class Teacher — Basic 4 Gold, Golden Excellence Academy", schoolId: schools.GEA.id } });
  await db.user.create({ data: { email: "bursar@nyansapo.gh", password: pw, fullName: "Mr. Patrick Quaye", phone: "+233 24 555 6677", role: "ACCOUNTANT", title: "Bursar — Golden Excellence Academy", schoolId: schools.GEA.id } });

  // update studentsOnRoll
  for (const code of Object.keys(schools)) {
    const n = await db.enrollment.count({ where: { schoolId: schools[code].id, status: "ACTIVE" } });
    await db.school.update({ where: { id: schools[code].id }, data: { studentsOnRoll: n } });
  }

  const counts = { schools: await db.school.count(), students: await db.student.count(), invoices: await db.invoice.count(), payments: await db.payment.count(), flags: await db.studentFlag.count(), transfers: await db.transferRecord.count(), users: await db.user.count(), attendance: await db.attendanceRecord.count() };
  console.log("✅ Seed complete", JSON.stringify(counts, null, 2));

  // ops modules: daily services, transport, SMS, payroll, inventory, discounts
  const { seedOps } = await import("./seed-ops");
  await seedOps();
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

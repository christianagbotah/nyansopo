import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { sendSms } from "@/lib/sms";

// ── Clubs & Societies (wave 27 — the co-curricular register) ──
// The club ledger: club register with patron & meeting venue, membership
// rosters with executive offices (one holder per office), the meeting
// register with attendance (present / absent / excused), inter-schools &
// competition events with outcomes and placements, learner co-curricular
// profiles, analytics and guardian meeting notices through the SMS centre.

const CATEGORIES = ["ACADEMIC", "SERVICE", "FAITH", "CULTURAL", "SPORTS", "GENERAL"];
const CLUB_STATUSES = ["ACTIVE", "DORMANT", "ARCHIVED"];
const OFFICES = ["PRESIDENT", "VICE_PRESIDENT", "SECRETARY", "TREASURER", "ORGANISER"];
const ATT_STATUSES = ["PRESENT", "ABSENT", "EXCUSED"];
const LEVELS = ["SCHOOL", "ZONAL", "DISTRICT", "REGIONAL", "NATIONAL"];
const OUTCOMES = ["UPCOMING", "COMPLETED"];
const WEEKDAYS = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];

const bad = (error: string, status = 400) => NextResponse.json({ error }, { status });
const optStr = (v: unknown): string | null => {
  if (typeof v !== "string") return null;
  const t = v.trim();
  return t.length ? t : null;
};
const enumVal = (v: unknown, allowed: string[]): string | null => {
  if (typeof v !== "string" || !v.trim()) return null;
  const u = v.trim().toUpperCase();
  return allowed.includes(u) ? u : "…invalid…";
};
const optDate = (v: unknown): Date | null | "invalid" => {
  if (typeof v !== "string" || !v.trim()) return null;
  const d = new Date(v);
  return isNaN(d.getTime()) ? "invalid" : d;
};

const MEMBER_INCLUDE = {
  student: {
    select: {
      id: true, firstName: true, lastName: true, nyansapoIndex: true,
      guardianName: true, guardianPhone: true,
      enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: { select: { id: true, name: true } } } },
    },
  },
} as const;

const CLUB_INCLUDE = {
  _count: { select: { memberships: { where: { status: "ACTIVE" } }, meetings: true, events: true } },
} as const;

/** Map a membership row (+ student include) to the wire member row. */
function toMember(m: any) {
  const active = (m.student?.enrollments ?? []).find((e: any) => e.status === "ACTIVE");
  const att = m.attendance ?? [];
  const present = att.filter((a: any) => a.status === "PRESENT").length;
  const excused = att.filter((a: any) => a.status === "EXCUSED").length;
  return {
    id: m.id,
    studentId: m.studentId,
    studentName: `${m.student?.firstName ?? ""} ${m.student?.lastName ?? ""}`.trim(),
    nyansapoIndex: m.student?.nyansapoIndex ?? "",
    classroomId: active?.classroom?.id ?? null,
    classroomName: active?.classroom?.name ?? null,
    guardianName: m.student?.guardianName ?? null,
    guardianPhone: m.student?.guardianPhone ?? null,
    office: m.office ?? null,
    joinedOn: m.joinedOn?.toISOString?.() ?? new Date(m.joinedOn).toISOString(),
    status: m.status,
    leftOn: m.leftOn?.toISOString?.() ?? null,
    leftReason: m.leftReason ?? null,
    meetings: att.length,
    present,
    excused,
    attRate: att.length ? Math.round(((present + excused) / att.length) * 100) : null,
  };
}

/** Map a meeting row (+ attendance include) to the wire meeting row. */
function toMeeting(mt: any) {
  const att = mt.attendance ?? [];
  const present = att.filter((a: any) => a.status === "PRESENT").length;
  const excused = att.filter((a: any) => a.status === "EXCUSED").length;
  return {
    id: mt.id,
    clubId: mt.clubId,
    clubName: mt.club?.name ?? null,
    date: mt.date?.toISOString?.() ?? new Date(mt.date).toISOString(),
    topic: mt.topic ?? null,
    minutes: mt.minutes ?? null,
    takenByName: mt.takenByName ?? null,
    guardiansNotifiedAt: mt.guardiansNotifiedAt?.toISOString?.() ?? null,
    expected: att.length,
    present,
    excused,
    absent: att.length - present - excused,
    rate: att.length ? Math.round(((present + excused) / att.length) * 100) : null,
  };
}

function toEvent(e: any) {
  return {
    id: e.id,
    clubId: e.clubId,
    clubName: e.club?.name ?? null,
    title: e.title,
    date: e.date?.toISOString?.() ?? new Date(e.date).toISOString(),
    level: e.level,
    outcome: e.outcome,
    placement: e.placement ?? null,
    participants: e.participants ?? null,
    note: e.note ?? null,
    recordedByName: e.recordedByName ?? null,
  };
}

/** Attendance stats for a bag of status strings. */
function attStats(rows: { status: string }[]) {
  const present = rows.filter((a) => a.status === "PRESENT").length;
  const excused = rows.filter((a) => a.status === "EXCUSED").length;
  return {
    meetings: rows.length,
    present,
    excused,
    rate: rows.length ? Math.round(((present + excused) / rows.length) * 100) : null,
  };
}

/** Fetch a club (school-scoped) or null. */
async function getClub(schoolId: string, clubId: string | null) {
  if (!clubId) return null;
  const club = await db.club.findUnique({ where: { id: clubId }, include: CLUB_INCLUDE });
  return club && club.schoolId === schoolId ? club : null;
}

/** Verify a learner holds an ACTIVE enrolment at the school. */
async function learnerAtSchool(schoolId: string, studentId: string | null) {
  if (!studentId) return null;
  const student = await db.student.findUnique({
    where: { id: studentId },
    select: { id: true, enrollments: { where: { status: "ACTIVE" }, take: 1, select: { classroom: { select: { schoolId: true } } } } },
  });
  if (!student || !student.enrollments.length || student.enrollments[0].classroom.schoolId !== schoolId) return null;
  return student;
}

// ── GET /api/school/[id]/ops/clubs — board | club | meeting | events | profile | analytics | roster ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["clubs.view"] });
  if (!auth.ok) return auth.res;

  const sp = req.nextUrl.searchParams;
  const action = sp.get("action") ?? "board";
  const now = new Date();

  /* ── club: one club — members roster, meetings, events ── */
  if (action === "club") {
    const club = await getClub(schoolId, optStr(sp.get("clubId")));
    if (!club) return bad("Club not found", 404);
    const [members, meetings, events] = await Promise.all([
      db.clubMembership.findMany({
        where: { clubId: club.id },
        include: { ...MEMBER_INCLUDE, attendance: { select: { status: true } } },
        orderBy: [{ status: "asc" }, { office: "asc" }, { student: { lastName: "asc" } }],
      }),
      db.clubMeeting.findMany({
        where: { clubId: club.id },
        include: { attendance: { select: { status: true } } },
        orderBy: { date: "desc" },
        take: 40,
      }),
      db.clubEvent.findMany({ where: { clubId: club.id }, orderBy: { date: "desc" }, take: 40 }),
    ]);
    return NextResponse.json({
      club: {
        id: club.id, name: club.name, category: club.category, description: club.description,
        patronName: club.patronName, meetingDay: club.meetingDay, venue: club.venue,
        status: club.status, foundedOn: club.foundedOn?.toISOString?.() ?? null,
        createdByName: club.createdByName,
        memberCount: members.filter((m) => m.status === "ACTIVE").length,
      },
      members: members.map(toMember),
      meetings: meetings.map(toMeeting),
      events: events.map(toEvent),
    });
  }

  /* ── meeting: one meeting's attendance sheet ── */
  if (action === "meeting") {
    const meetingId = optStr(sp.get("meetingId"));
    const meeting = await db.clubMeeting.findUnique({
      where: { id: meetingId ?? "" },
      include: {
        club: { select: { id: true, name: true, patronName: true, meetingDay: true, venue: true, status: true } },
        attendance: {
          include: { membership: { include: MEMBER_INCLUDE } },
          orderBy: { membership: { student: { lastName: "asc" } } },
        },
      },
    });
    if (!meeting || meeting.schoolId !== schoolId) return bad("Meeting not found", 404);
    return NextResponse.json({
      meeting: {
        id: meeting.id, clubId: meeting.clubId, clubName: meeting.club.name, date: meeting.date.toISOString(),
        topic: meeting.topic, minutes: meeting.minutes, takenByName: meeting.takenByName,
        guardiansNotifiedAt: meeting.guardiansNotifiedAt?.toISOString?.() ?? null,
      },
      rows: meeting.attendance.map((a) => {
        const mem = toMember(a.membership);
        return { attendanceId: a.id, membershipId: a.membershipId, status: a.status, note: a.note, member: mem };
      }),
    });
  }

  /* ── events: the events & competitions register ── */
  if (action === "events") {
    const clubId = optStr(sp.get("clubId"));
    const events = await db.clubEvent.findMany({
      where: { schoolId, ...(clubId ? { clubId } : {}) },
      include: { club: { select: { id: true, name: true } } },
      orderBy: { date: "desc" },
      take: 200,
    });
    return NextResponse.json({ events: events.map(toEvent) });
  }

  /* ── meetings: the full meeting register (filterable by club) ── */
  if (action === "meetings") {
    const clubId = optStr(sp.get("clubId"));
    const meetings = await db.clubMeeting.findMany({
      where: { schoolId, ...(clubId ? { clubId } : {}) },
      include: { club: { select: { id: true, name: true } }, attendance: { select: { status: true } } },
      orderBy: { date: "desc" },
      take: 200,
    });
    return NextResponse.json({ meetings: meetings.map(toMeeting) });
  }

  /* ── profile: one learner's co-curricular profile ── */
  if (action === "profile") {
    const studentId = optStr(sp.get("studentId"));
    if (!studentId) return bad("studentId is required");
    const student = await db.student.findUnique({
      where: { id: studentId },
      select: {
        id: true, firstName: true, lastName: true, nyansapoIndex: true, gender: true,
        guardianName: true, guardianPhone: true,
        enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: { select: { id: true, name: true } } } },
      },
    });
    const active = student?.enrollments.find((e) => e.status === "ACTIVE");
    if (!student || !active) return bad("Learner has no active enrolment at this school", 404);

    const memberships = await db.clubMembership.findMany({
      where: { studentId },
      include: {
        club: { select: { id: true, name: true, category: true, patronName: true, status: true } },
        attendance: { select: { status: true } },
      },
      orderBy: { joinedOn: "asc" },
    });
    const clubIds = memberships.map((m) => m.clubId);
    const events = clubIds.length
      ? await db.clubEvent.findMany({
          where: { clubId: { in: clubIds } },
          include: { club: { select: { name: true } } },
          orderBy: { date: "desc" },
        })
      : [];
    const allAtt = memberships.flatMap((m) => m.attendance.map((a) => ({ status: a.status })));
    const stats = attStats(allAtt);
    return NextResponse.json({
      student: {
        id: student.id,
        name: `${student.firstName} ${student.lastName}`.trim(),
        nyansapoIndex: student.nyansapoIndex,
        gender: student.gender,
        classroomId: active.classroomId,
        classroomName: active.classroom?.name ?? null,
        guardianName: student.guardianName,
        guardianPhone: student.guardianPhone,
      },
      memberships: memberships.map((m) => ({
        id: m.id,
        clubName: m.club.name,
        clubCategory: m.club.category,
        patronName: m.club.patronName,
        clubStatus: m.club.status,
        office: m.office,
        joinedOn: m.joinedOn.toISOString(),
        status: m.status,
        leftOn: m.leftOn?.toISOString?.() ?? null,
        leftReason: m.leftReason ?? null,
        ...attStats(m.attendance),
      })),
      events: events.map(toEvent),
      summary: {
        clubs: memberships.length,
        active: memberships.filter((m) => m.status === "ACTIVE").length,
        offices: memberships.filter((m) => m.office && m.status === "ACTIVE").length,
        meetings: stats.meetings,
        attRate: stats.rate,
        represented: events.filter((e) => (e.participants ?? "").includes(student.firstName)).length,
      },
    });
  }

  /* ── analytics: participation, attendance trends, category mix ── */
  if (action === "analytics") {
    const since = new Date(now.getTime() - 90 * 86400000);
    const [clubs, memberships, meetings, attendance, events, classrooms, activeEns] = await Promise.all([
      db.club.findMany({ where: { schoolId }, include: { memberships: { where: { status: "ACTIVE" }, select: { id: true } } } }),
      db.clubMembership.findMany({
        where: { schoolId, status: "ACTIVE" },
        include: { ...MEMBER_INCLUDE },
      }),
      db.clubMeeting.findMany({ where: { schoolId, date: { gte: since } }, select: { id: true, clubId: true, date: true } }),
      db.clubAttendance.findMany({
        where: { meeting: { schoolId, date: { gte: since } } },
        select: { status: true, meeting: { select: { date: true, clubId: true } } },
      }),
      db.clubEvent.findMany({ where: { schoolId }, orderBy: { date: "desc" }, include: { club: { select: { name: true } } } }),
      db.classroom.findMany({ where: { schoolId }, select: { id: true, name: true }, orderBy: { name: "asc" } }),
      db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, select: { classroomId: true } }),
    ]);

    // category mix (clubs + members per category)
    const catMap = new Map<string, { clubs: number; members: number }>();
    for (const c of clubs) {
      const cur = catMap.get(c.category) ?? { clubs: 0, members: 0 };
      cur.clubs++;
      cur.members += c.memberships.length;
      catMap.set(c.category, cur);
    }
    const categoryMix = [...catMap.entries()].map(([name, v]) => ({ name, ...v })).sort((a, b) => b.members - a.members);

    // per-club attendance rate (all time)
    const allAttByClub = await db.clubAttendance.groupBy({
      by: ["status", "membershipId"],
      where: { membership: { schoolId, status: "ACTIVE" }, meeting: { schoolId } },
      _count: { _all: true },
    });
    const memberClub = new Map(memberships.map((m) => [m.id, m.clubId]));
    const clubStats = new Map<string, { present: number; total: number }>();
    for (const g of allAttByClub) {
      const cid = memberClub.get(g.membershipId);
      if (!cid) continue;
      const cur = clubStats.get(cid) ?? { present: 0, total: 0 };
      cur.total += g._count._all;
      if (g.status !== "ABSENT") cur.present += g._count._all;
      clubStats.set(cid, cur);
    }
    const clubRanking = clubs
      .map((c) => ({
        name: c.name,
        members: c.memberships.length,
        meetings: meetings.filter((m) => m.clubId === c.id).length,
        rate: (() => {
          const s = clubStats.get(c.id);
          return s && s.total ? Math.round((s.present / s.total) * 100) : null;
        })(),
      }))
      .sort((a, b) => b.members - a.members);

    // weekly attendance trend (8 ISO weeks)
    const weeks: { week: string; meetings: number; rate: number }[] = [];
    for (let i = 7; i >= 0; i--) {
      const end = new Date(now.getTime() - i * 7 * 86400000);
      const start = new Date(end.getTime() - 7 * 86400000);
      const inRange = (d: Date) => d >= start && d < end;
      const wkMeetings = meetings.filter((m) => inRange(new Date(m.date)));
      const wkAtt = attendance.filter((a) => inRange(new Date(a.meeting.date)));
      const ok = wkAtt.filter((a) => a.status !== "ABSENT").length;
      weeks.push({
        week: end.toLocaleDateString("en-GB", { day: "numeric", month: "short" }),
        meetings: wkMeetings.length,
        rate: wkAtt.length ? Math.round((ok / wkAtt.length) * 100) : 0,
      });
    }

    // class participation: ACTIVE members per class ÷ class size
    const classSizes = new Map<string, number>();
    for (const e of activeEns) classSizes.set(e.classroomId, (classSizes.get(e.classroomId) ?? 0) + 1);
    const classById = new Map(classrooms.map((c) => [c.id, c]));
    const perClass = new Map<string, { name: string; members: number }>();
    for (const m of memberships) {
      const active = (m.student?.enrollments ?? []).find((e: any) => e.status === "ACTIVE");
      const cls = active ? classById.get(active.classroomId) : undefined;
      const key = cls?.id ?? "none";
      const cur = perClass.get(key) ?? { name: cls?.name ?? "Unassigned", members: 0 };
      cur.members++;
      perClass.set(key, cur);
    }
    const classParticipation = [...perClass.values()]
      .map((v) => {
        const size = [...classSizes.entries()].find(([k]) => classById.get(k)?.name === v.name)?.[1] ?? 0;
        return { name: v.name, members: v.members, learners: size, pct: size ? Math.round((v.members / size) * 100) : 0 };
      })
      .sort((a, b) => b.pct - a.pct);

    // office holders
    const officeHolders = memberships
      .filter((m) => m.office)
      .map((m) => ({
        office: m.office!,
        name: `${m.student?.firstName ?? ""} ${m.student?.lastName ?? ""}`.trim(),
      }))
      .sort((a, b) => a.office.localeCompare(b.office));

    const attStats90 = attStats(attendance.map((a) => ({ status: a.status })));
    return NextResponse.json({
      window: { since: since.toISOString().slice(0, 10) },
      totals: {
        clubs: clubs.length,
        activeClubs: clubs.filter((c) => c.status === "ACTIVE").length,
        members: memberships.length,
        offices: officeHolders.length,
        meetings90d: meetings.length,
        attRate90d: attStats90.rate,
        events: events.length,
        upcoming: events.filter((e) => new Date(e.date) >= now && e.outcome === "UPCOMING").length,
      },
      categoryMix, clubRanking, weeks, classParticipation, officeHolders,
      recentEvents: events.slice(0, 8).map(toEvent),
      upcomingEvents: events
        .filter((e) => new Date(e.date) >= now)
        .map(toEvent)
        .sort((a, b) => a.date.localeCompare(b.date)),
    });
  }

  /* ── roster: active learners of one classroom (member pickers) ── */
  if (action === "roster") {
    const classroomId = optStr(sp.get("classroomId"));
    if (!classroomId) return bad("classroomId is required");
    const ens = await db.enrollment.findMany({
      where: { schoolId, classroomId, status: "ACTIVE" },
      include: { student: { select: { id: true, firstName: true, lastName: true, nyansapoIndex: true } } },
      orderBy: [{ rollNo: "asc" }, { student: { lastName: "asc" } }],
      take: 300,
    });
    return NextResponse.json({
      rows: ens.map((e) => ({
        id: e.student.id,
        name: `${e.student.firstName} ${e.student.lastName}`.trim(),
        rollNo: e.rollNo ?? null,
        nyansapoIndex: e.student.nyansapoIndex,
      })),
    });
  }

  /* ── board (default): stat row + club cards + recent meetings + upcoming events ── */
  const [classrooms, clubs, recentMeetings, upcomingEvents, membershipCount, officerCount, meetingCount90, attRows90, eventCount] = await Promise.all([
    db.classroom.findMany({ where: { schoolId }, select: { id: true, name: true }, orderBy: { name: "asc" } }),
    db.club.findMany({ where: { schoolId }, include: CLUB_INCLUDE, orderBy: { name: "asc" } }),
    db.clubMeeting.findMany({
      where: { schoolId },
      include: { club: { select: { name: true } }, attendance: { select: { status: true } } },
      orderBy: { date: "desc" },
      take: 10,
    }),
    db.clubEvent.findMany({
      where: { schoolId, date: { gte: new Date(now.getTime() - 7 * 86400000) }, outcome: "UPCOMING" },
      include: { club: { select: { name: true } } },
      orderBy: { date: "asc" },
      take: 8,
    }),
    db.clubMembership.count({ where: { schoolId, status: "ACTIVE" } }),
    db.clubMembership.count({ where: { schoolId, status: "ACTIVE", office: { not: null } } }),
    db.clubMeeting.count({ where: { schoolId, date: { gte: new Date(now.getTime() - 90 * 86400000) } } }),
    db.clubAttendance.findMany({
      where: { meeting: { schoolId, date: { gte: new Date(now.getTime() - 90 * 86400000) } } },
      select: { status: true },
      take: 5000,
    }),
    db.clubEvent.count({ where: { schoolId } }),
  ]);

  // last meeting date per club (for the club cards)
  const lastMet = await db.clubMeeting.groupBy({
    by: ["clubId"],
    where: { schoolId },
    _max: { date: true },
  });
  const lastMetBy = new Map(lastMet.map((g) => [g.clubId, g._max.date?.toISOString?.() ?? null]));

  const okAtt = attRows90.filter((a) => a.status !== "ABSENT").length;
  return NextResponse.json({
    classrooms,
    stat: {
      clubs: clubs.length,
      activeClubs: clubs.filter((c) => c.status === "ACTIVE").length,
      members: membershipCount,
      officers: officerCount,
      meetings90d: meetingCount90,
      attRate90d: attRows90.length ? Math.round((okAtt / attRows90.length) * 100) : null,
      events: eventCount,
      upcoming: upcomingEvents.length,
    },
    clubs: clubs.map((c) => ({
      id: c.id, name: c.name, category: c.category, description: c.description,
      patronName: c.patronName, meetingDay: c.meetingDay, venue: c.venue, status: c.status,
      foundedOn: c.foundedOn?.toISOString?.() ?? null,
      members: c._count.memberships,
      meetings: c._count.meetings,
      events: c._count.events,
      lastMetAt: lastMetBy.get(c.id) ?? null,
    })),
    recentMeetings: recentMeetings.map(toMeeting),
    upcomingEvents: upcomingEvents.map(toEvent),
    catalogues: { categories: CATEGORIES, weekdays: WEEKDAYS, offices: OFFICES },
  });
}

// ── POST /api/school/[id]/ops/clubs — club-create | club-update | membership-add | membership-update |
//    meeting-create | meeting-update | attendance-save | notify | event-create | event-update ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["clubs.manage"] });
  if (!auth.ok) return auth.res;
  const sentByName = auth.user.fullName || auth.user.email;

  /* ── club-create: register a club ── */
  if (action === "club-create") {
    const { name, category, description, patronName, meetingDay, venue, foundedOn, status } = body;
    const nm = optStr(name);
    if (!nm) return bad("Club name is required");
    if (nm.length > 80) return bad("Club name is too long (max 80 characters)");
    const cat = optStr(category) ? enumVal(category, CATEGORIES) : "GENERAL";
    if (cat === "…invalid…") return bad("Category must be one of " + CATEGORIES.join(", "));
    const day = optStr(meetingDay);
    if (day && !WEEKDAYS.map((d) => d.toLowerCase()).includes(day.toLowerCase())) return bad("Meeting day must be a weekday name");
    const st = status != null && `${status}`.trim() ? enumVal(status, CLUB_STATUSES) : "ACTIVE";
    if (!st || st === "…invalid…") return bad("Status must be ACTIVE, DORMANT or ARCHIVED");
    const founded = optDate(foundedOn);
    if (founded === "invalid") return bad("Invalid founded-on date");
    const dupe = await db.club.findFirst({ where: { schoolId, name: { equals: nm, mode: "insensitive" } } });
    if (dupe) return bad(`A club called "${nm}" already exists at this school`, 409);
    const created = await db.club.create({
      data: {
        schoolId, name: nm, category: cat ?? "GENERAL",
        description: optStr(description), patronName: optStr(patronName),
        meetingDay: day ? day.charAt(0).toUpperCase() + day.slice(1).toLowerCase() : null,
        venue: optStr(venue), status: st,
        foundedOn: typeof founded === "object" ? founded : null,
        createdByName: sentByName,
      },
    });
    return NextResponse.json({ ok: true, club: { id: created.id, name: created.name } });
  }

  /* ── club-update: edit details / status ── */
  if (action === "club-update") {
    const club = await getClub(schoolId, optStr(body.id));
    if (!club) return bad("Club not found", 404);
    const data: any = {};
    if (body.name != null) {
      const nm = optStr(body.name);
      if (!nm) return bad("Club name cannot be empty");
      if (nm.length > 80) return bad("Club name is too long (max 80 characters)");
      const dupe = await db.club.findFirst({ where: { schoolId, name: { equals: nm, mode: "insensitive" }, NOT: { id: club.id } } });
      if (dupe) return bad(`A club called "${nm}" already exists at this school`, 409);
      data.name = nm;
    }
    if (body.category != null) {
      const v = enumVal(body.category, CATEGORIES);
      if (!v || v === "…invalid…") return bad("Category must be one of " + CATEGORIES.join(", "));
      data.category = v;
    }
    if (body.description != null) data.description = optStr(body.description);
    if (body.patronName != null) data.patronName = optStr(body.patronName);
    if (body.meetingDay != null) {
      const day = optStr(body.meetingDay);
      if (day && !WEEKDAYS.map((d) => d.toLowerCase()).includes(day.toLowerCase())) return bad("Meeting day must be a weekday name");
      data.meetingDay = day ? day.charAt(0).toUpperCase() + day.slice(1).toLowerCase() : null;
    }
    if (body.venue != null) data.venue = optStr(body.venue);
    if (body.status != null) {
      const v = enumVal(body.status, CLUB_STATUSES);
      if (!v || v === "…invalid…") return bad("Status must be ACTIVE, DORMANT or ARCHIVED");
      data.status = v;
    }
    if (body.foundedOn !== undefined) {
      const f = optDate(body.foundedOn);
      if (f === "invalid") return bad("Invalid founded-on date");
      data.foundedOn = typeof f === "object" ? f : null;
    }
    const updated = await db.club.update({ where: { id: club.id }, data });
    return NextResponse.json({ ok: true, club: { id: updated.id, name: updated.name, status: updated.status } });
  }

  /* ── membership-add: enrol a learner ── */
  if (action === "membership-add") {
    const club = await getClub(schoolId, optStr(body.clubId));
    if (!club) return bad("Club not found", 404);
    if (club.status !== "ACTIVE") return bad("This club is not active — reactivate it before adding members", 409);
    const student = await learnerAtSchool(schoolId, optStr(body.studentId));
    if (!student) return bad("Learner not found at this school", 404);
    let office: string | null = null;
    if (body.office != null && `${body.office}`.trim()) {
      const v = enumVal(body.office, OFFICES);
      if (!v || v === "…invalid…") return bad("Office must be one of " + OFFICES.join(", "));
      office = v;
    }
    const joined = optDate(body.joinedOn);
    if (joined === "invalid") return bad("Invalid joined-on date");
    const existing = await db.clubMembership.findUnique({ where: { clubId_studentId: { clubId: club.id, studentId: student.id } } });
    if (existing && existing.status === "ACTIVE") return bad("This learner is already a member of the club", 409);

    const created = await db.$transaction(async (tx) => {
      // one holder per office: transfer the office from any previous holder
      if (office) {
        await tx.clubMembership.updateMany({
          where: { clubId: club.id, office, status: "ACTIVE" },
          data: { office: null },
        });
      }
      if (existing) {
        return tx.clubMembership.update({
          where: { id: existing.id },
          data: {
            status: "ACTIVE", office, leftOn: null, leftReason: null,
            joinedOn: joined ?? new Date(),
            createdByName: sentByName,
          },
        });
      }
      return tx.clubMembership.create({
        data: {
          schoolId, clubId: club.id, studentId: student.id, office,
          joinedOn: joined ?? new Date(),
          createdByName: sentByName,
        },
      });
    });
    return NextResponse.json({ ok: true, membership: { id: created.id, studentId: created.studentId, office: created.office } });
  }

  /* ── membership-update: office change / leave / rejoin ── */
  if (action === "membership-update") {
    const membership = await db.clubMembership.findUnique({
      where: { id: optStr(body.id) ?? "" },
      include: { club: { select: { id: true, schoolId: true, name: true } } },
    });
    if (!membership || membership.schoolId !== schoolId) return bad("Membership not found", 404);

    const data: any = {};
    if (body.office !== undefined) {
      if (body.office == null || `${body.office}`.trim() === "") {
        data.office = null;
      } else {
        const v = enumVal(body.office, OFFICES);
        if (!v || v === "…invalid…") return bad("Office must be one of " + OFFICES.join(", "));
        data.office = v;
      }
    }
    if (body.status != null) {
      const st = optStr(body.status);
      if (!st || !["ACTIVE", "LEFT"].includes(st)) return bad("Status must be ACTIVE or LEFT");
      if (st === "LEFT") {
        data.status = "LEFT";
        data.leftOn = new Date();
        data.leftReason = optStr(body.leftReason);
        data.office = null; // leaving vacates any office
      } else {
        data.status = "ACTIVE";
        data.leftOn = null;
        data.leftReason = null;
      }
    }

    const updated = await db.$transaction(async (tx) => {
      // transferring an office vacates the previous holder
      if (data.office && membership.status === "ACTIVE") {
        await tx.clubMembership.updateMany({
          where: { clubId: membership.clubId, office: data.office, status: "ACTIVE", NOT: { id: membership.id } },
          data: { office: null },
        });
      }
      return tx.clubMembership.update({ where: { id: membership.id }, data });
    });
    return NextResponse.json({ ok: true, membership: { id: updated.id, office: updated.office, status: updated.status } });
  }

  /* ── meeting-create: schedule a meeting + seed the register ── */
  if (action === "meeting-create") {
    const club = await getClub(schoolId, optStr(body.clubId));
    if (!club) return bad("Club not found", 404);
    const when = optDate(body.date);
    if (!when || when === "invalid") return bad("Meeting date is required");
    const dupe = await db.clubMeeting.findFirst({
      where: { clubId: club.id, date: { gte: new Date(new Date(when).getTime() - 3600000), lt: new Date(new Date(when).getTime() + 3600000) } },
    });
    if (dupe) return bad("A meeting for this club already exists on this date", 409);

    const meeting = await db.$transaction(async (tx) => {
      const m = await tx.clubMeeting.create({
        data: {
          schoolId, clubId: club.id, date: when as Date,
          topic: optStr(body.topic), minutes: optStr(body.minutes),
          takenByName: sentByName,
        },
      });
      // seed the attendance register: one row per ACTIVE member (default ABSENT)
      const members = await tx.clubMembership.findMany({
        where: { clubId: club.id, status: "ACTIVE" },
        select: { id: true },
      });
      if (members.length) {
        await tx.clubAttendance.createMany({
          data: members.map((mem) => ({ meetingId: m.id, membershipId: mem.id, status: "ABSENT" })),
        });
      }
      return m;
    });
    return NextResponse.json({ ok: true, meeting: { id: meeting.id, clubId: meeting.clubId, date: meeting.date.toISOString() } });
  }

  /* ── meeting-update: topic / minutes / date ── */
  if (action === "meeting-update") {
    const meeting = await db.clubMeeting.findUnique({ where: { id: optStr(body.id) ?? "" } });
    if (!meeting || meeting.schoolId !== schoolId) return bad("Meeting not found", 404);
    const data: any = {};
    if (body.topic !== undefined) data.topic = optStr(body.topic);
    if (body.minutes !== undefined) data.minutes = optStr(body.minutes);
    if (body.date != null) {
      const when = optDate(body.date);
      if (!when || when === "invalid") return bad("Invalid meeting date");
      data.date = when;
    }
    const updated = await db.clubMeeting.update({ where: { id: meeting.id }, data });
    return NextResponse.json({ ok: true, meeting: { id: updated.id } });
  }

  /* ── attendance-save: mark the register (P/A/E per member) ── */
  if (action === "attendance-save") {
    const meeting = await db.clubMeeting.findUnique({
      where: { id: optStr(body.meetingId) ?? "" },
      include: { club: { select: { name: true } } },
    });
    if (!meeting || meeting.schoolId !== schoolId) return bad("Meeting not found", 404);
    const rows: { membershipId: string; status: string; note?: string | null }[] = Array.isArray(body.rows) ? body.rows : [];
    if (!rows.length) return bad("No attendance rows submitted");
    const validIds = new Set(
      (await db.clubMembership.findMany({ where: { clubId: meeting.clubId }, select: { id: true } })).map((m) => m.id),
    );
    for (const r of rows) {
      if (!validIds.has(r.membershipId)) return bad("A submitted member does not belong to this club");
      const st = enumVal(r.status, ATT_STATUSES);
      if (!st || st === "…invalid…") return bad("Attendance status must be PRESENT, ABSENT or EXCUSED");
    }
    await db.$transaction(
      rows.map((r) =>
        db.clubAttendance.upsert({
          where: { meetingId_membershipId: { meetingId: meeting.id, membershipId: r.membershipId } },
          update: { status: enumVal(r.status, ATT_STATUSES)!, note: optStr(r.note) },
          create: { meetingId: meeting.id, membershipId: r.membershipId, status: enumVal(r.status, ATT_STATUSES)!, note: optStr(r.note) },
        }),
      ),
    );
    const attendance = await db.clubAttendance.findMany({ where: { meetingId: meeting.id }, select: { status: true } });
    const ok = attendance.filter((a) => a.status !== "ABSENT").length;
    return NextResponse.json({
      ok: true,
      saved: rows.length,
      summary: {
        expected: attendance.length,
        present: attendance.filter((a) => a.status === "PRESENT").length,
        rate: attendance.length ? Math.round((ok / attendance.length) * 100) : null,
      },
    });
  }

  /* ── notify: SMS the guardians of the club's members about a meeting ── */
  if (action === "notify") {
    const meeting = await db.clubMeeting.findUnique({
      where: { id: optStr(body.meetingId) ?? "" },
      include: { club: { select: { id: true, name: true, meetingDay: true, venue: true } } },
    });
    if (!meeting || meeting.schoolId !== schoolId) return bad("Meeting not found", 404);
    const members = await db.clubMembership.findMany({
      where: { clubId: meeting.clubId, status: "ACTIVE" },
      include: MEMBER_INCLUDE,
    });
    const withPhone = members.filter((m) => (m.student?.guardianPhone ?? "").trim());
    if (!withPhone.length) return bad("No member of this club has a guardian phone on file", 409);

    const school = await db.school.findUnique({ where: { id: schoolId }, select: { name: true } });
    const dateStr = new Date(meeting.date).toLocaleDateString("en-GB", { weekday: "long", day: "numeric", month: "short" });
    const bodyText = optStr(body.message) ?? `[${school?.name ?? "School"}] Clubs notice — ${meeting.club.name} meets on ${dateStr}${meeting.club.venue ? ` at ${meeting.club.venue}` : ""}. Attendance kindly expected. — Clubs & Societies`;

    const results = await Promise.all(
      withPhone.map((m) =>
        sendSms({
          db, schoolId,
          studentId: m.studentId,
          recipientName: m.student?.guardianName ?? "Guardian",
          recipientPhone: (m.student?.guardianPhone ?? "").trim(),
          body: bodyText,
          category: "CLUBS",
          sentByName,
        }),
      ),
    );
    const sent = results.filter((r) => r.status !== "FAILED").length;
    if (!sent) {
      const fail = results.find((r) => r.status === "FAILED");
      return NextResponse.json({ error: fail?.errorReason ?? "SMS failed — check SMS credits in Settings" }, { status: 409 });
    }
    const updated = await db.clubMeeting.update({
      where: { id: meeting.id },
      data: { guardiansNotifiedAt: new Date() },
    });
    return NextResponse.json({
      ok: true,
      notified: sent,
      failed: results.length - sent,
      meeting: { id: updated.id, guardiansNotifiedAt: updated.guardiansNotifiedAt?.toISOString() ?? null },
    });
  }

  /* ── event-create: record an event / competition ── */
  if (action === "event-create") {
    const { clubId, title, date, level, outcome, placement, participants, note } = body;
    const t = optStr(title);
    if (!t) return bad("Event title is required");
    if (t.length > 120) return bad("Event title is too long (max 120 characters)");
    const when = optDate(date);
    if (!when || when === "invalid") return bad("Event date is required");
    let club: { id: string } | null = null;
    if (optStr(clubId)) {
      club = await getClub(schoolId, optStr(clubId));
      if (!club) return bad("Club not found", 404);
    }
    const lvl = optStr(level) ? enumVal(level, LEVELS) : "SCHOOL";
    if (!lvl || lvl === "…invalid…") return bad("Level must be one of " + LEVELS.join(", "));
    const out = optStr(outcome) ? enumVal(outcome, OUTCOMES) : "UPCOMING";
    if (!out || out === "…invalid…") return bad("Outcome must be UPCOMING or COMPLETED");
    const created = await db.clubEvent.create({
      data: {
        schoolId, clubId: club?.id ?? null, title: t, date: when as Date, level: lvl, outcome: out,
        placement: optStr(placement), participants: optStr(participants),
        note: optStr(note), recordedByName: sentByName,
      },
    });
    return NextResponse.json({ ok: true, event: { id: created.id, title: created.title } });
  }

  /* ── event-update: edit details / record the outcome ── */
  if (action === "event-update") {
    const event = await db.clubEvent.findUnique({ where: { id: optStr(body.id) ?? "" } });
    if (!event || event.schoolId !== schoolId) return bad("Event not found", 404);
    const data: any = {};
    if (body.title != null) {
      const t = optStr(body.title);
      if (!t) return bad("Event title cannot be empty");
      data.title = t;
    }
    if (body.clubId !== undefined) {
      const cid = optStr(body.clubId);
      if (!cid) data.clubId = null;
      else {
        const club = await getClub(schoolId, cid);
        if (!club) return bad("Club not found", 404);
        data.clubId = club.id;
      }
    }
    if (body.date != null) {
      const when = optDate(body.date);
      if (!when || when === "invalid") return bad("Invalid event date");
      data.date = when;
    }
    if (body.level != null) {
      const v = enumVal(body.level, LEVELS);
      if (!v || v === "…invalid…") return bad("Level must be one of " + LEVELS.join(", "));
      data.level = v;
    }
    if (body.outcome != null) {
      const v = enumVal(body.outcome, OUTCOMES);
      if (!v || v === "…invalid…") return bad("Outcome must be UPCOMING or COMPLETED");
      data.outcome = v;
    }
    if (body.placement !== undefined) data.placement = optStr(body.placement);
    if (body.participants !== undefined) data.participants = optStr(body.participants);
    if (body.note !== undefined) data.note = optStr(body.note);
    const updated = await db.clubEvent.update({ where: { id: event.id }, data });
    return NextResponse.json({ ok: true, event: { id: updated.id, outcome: updated.outcome } });
  }

  return bad("Unknown action");
}

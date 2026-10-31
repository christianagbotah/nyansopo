import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getUser, isAssociation } from "@/lib/session";

// GET /api/registry?q=... — cross-school student registry lookup (the "Nyansapo Index")
export async function GET(req: NextRequest) {
  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });
  const q = (new URL(req.url).searchParams.get("q") ?? "").trim().toLowerCase();
  if (q.length < 3) return NextResponse.json({ matches: [], query: q });
  const phoneVars = phoneVariants(q);

  const students = await db.student.findMany({
    where: {
      OR: [
        { nyansapoIndex: { contains: q.toUpperCase() } },
        { firstName: { contains: cap(q) } },
        { lastName: { contains: cap(q) } },
        ...phoneVars.map((v) => ({ guardianPhone: { contains: v } })),
      ],
    },
    include: {
      enrollments: { include: { school: { select: { id: true, name: true, town: true, region: true, phone: true, email: true, curriculum: true } }, classroom: true }, orderBy: { admissionDate: "asc" } },
      flags: { include: { school: { select: { id: true, name: true, phone: true } }, verifications: true }, orderBy: { raisedAt: "desc" } },
      transfers: { include: { fromSchool: { select: { id: true, name: true } }, toSchool: { select: { id: true, name: true } } } },
    },
    take: 10,
  });

  // also match full name searches
  const nameMatch = q.includes(" ")
    ? await db.student.findMany({
        where: { AND: [{ firstName: { contains: cap(q.split(" ")[0]) } }, { lastName: { contains: cap(q.split(" ").slice(-1)[0]) } }] },
        include: { enrollments: { include: { school: { select: { id: true, name: true, town: true, region: true, phone: true, email: true, curriculum: true } }, classroom: true } }, flags: { include: { school: { select: { id: true, name: true, phone: true } }, verifications: true } }, transfers: { include: { fromSchool: { select: { id: true, name: true } }, toSchool: { select: { id: true, name: true } } } } },
        take: 10,
      })
    : [];

  const merged = [...students, ...nameMatch.filter((n) => !students.some((s) => s.id === n.id))];

  return NextResponse.json({
    query: q,
    matches: merged.map((s) => ({
      id: s.id, nyansapoIndex: s.nyansapoIndex, name: `${s.firstName} ${s.lastName}`, gender: s.gender, dob: s.dateOfBirth, status: s.status,
      guardianName: s.guardianName, guardianPhone: s.guardianPhone,
      history: s.enrollments.map((e) => ({
        school: e.school, classroom: e.classroom.name, admissionDate: e.admissionDate, exitDate: e.exitDate,
        status: e.status, exitReason: e.exitReason,
        current: e.status === "ACTIVE",
      })),
      flags: s.flags.map((f) => ({
        id: f.id, type: f.type, severity: f.severity, status: f.status, amountOwed: f.amountOwedCents, raisedAt: f.raisedAt,
        school: f.school, description: f.description,
        verified: f.verifications.length > 0 ? f.verifications[0].action : null,
      })),
      transfers: s.transfers.map((t) => ({ from: t.fromSchool.name, to: t.toSchool.name, date: t.date, status: t.status, outstanding: t.outstandingCents })),
      activeFinancialFlags: s.flags.filter((f) => f.status === "ACTIVE" && ["FEE_ARREARS", "WITHDREW_OWING", "PROPERTY_UNRETURNED"].includes(f.type)).length,
      activeDisciplineFlags: s.flags.filter((f) => f.status === "ACTIVE" && f.type.startsWith("DISCIPLINE")).length,
    })),
  });
}

function cap(s: string) { return s.charAt(0).toUpperCase() + s.slice(1); }

// Build searchable phone variants from a raw query (+233 24 477 8123 | 0244778123 | 244778123 …)
export function phoneVariants(q: string): string[] {
  const digits = q.replace(/\D/g, "");
  const out = new Set<string>();
  if (digits.length >= 9) {
    const last9 = digits.slice(-9); // e.g. 244778123
    out.add(last9);
    out.add(`${last9.slice(0, 2)} ${last9.slice(2, 5)} ${last9.slice(5)}`); // 24 477 8123
    out.add(`0${last9}`);
  }
  if (digits.length >= 6) out.add(digits.slice(-7));
  return [...out].filter(Boolean);
}
export const dynamic = "force-dynamic";

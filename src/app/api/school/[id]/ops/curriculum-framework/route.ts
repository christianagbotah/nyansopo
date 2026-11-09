import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { schoolCurricula, isCurriculumKey } from "@/lib/curricula";

// ── Curriculum Framework Library (enhanced CI3 curriculum_strands.php,
//    curriculum_sub_strands.php, curriculum_content_standards.php,
//    curriculum_learning_indicators.php + curriculum_import.php)
//    GET  ?meta=1 → curricula available to this school + subjects/grades
//                    covered by the shared library
//         ?curriculum=&subjectName=&gradeLevel= → full nested tree
//    POST strand.save | strand.delete | sub.save | sub.delete |
//         standard.save | standard.delete | indicator.save |
//         indicator.delete | framework.import ──

/** subject-name matching is case-insensitive: resolve the canonical
 *  library spelling ("Mathematics" == "MATHEMATICS" == "mathematics") */
async function canonicalSubjectName(schoolId: string, subjectName: string): Promise<string> {
  const subjects = await db.subject.findMany({ where: { schoolId }, select: { name: true } });
  const wanted = subjectName.trim().toLowerCase();
  const hit = subjects.find((s) => s.name.trim().toLowerCase() === wanted);
  return hit?.name ?? subjectName.trim();
}

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["curriculum.view"] });
  if (!auth.ok) return auth.res;
  const url = new URL(req.url);
  const school = await db.school.findUnique({ where: { id: schoolId } });
  if (!school) return NextResponse.json({ error: "School not found" }, { status: 404 });
  const enabled = schoolCurricula(school);

  if (url.searchParams.get("meta")) {
    // distinct subject/grade combinations covered per curriculum + counts
    const strands = await db.curriculumStrand.findMany({
      where: { curriculum: { in: enabled } },
      select: { curriculum: true, subjectName: true, gradeLevel: true },
    });
    const byKey = new Map<string, { curriculum: string; subjectName: string; gradeLevel: string }>();
    for (const s of strands) byKey.set(`${s.curriculum}|${s.subjectName}|${s.gradeLevel}`, s);
    const coverage = [...byKey.values()].sort((a, b) =>
      a.curriculum.localeCompare(b.curriculum) || a.subjectName.localeCompare(b.subjectName) ||
      a.gradeLevel.localeCompare(b.gradeLevel, undefined, { numeric: true }));
    return NextResponse.json({
      enabled,
      primary: school.curriculum,
      coverage,
      subjects: await db.subject.findMany({ where: { schoolId }, select: { id: true, name: true, curriculum: true }, orderBy: { name: "asc" } }),
    });
  }

  const curriculum = url.searchParams.get("curriculum") ?? enabled[0];
  const subjectName = url.searchParams.get("subjectName") ?? "";
  const gradeLevel = url.searchParams.get("gradeLevel") ?? "";
  if (!isCurriculumKey(curriculum)) return NextResponse.json({ error: "Unknown curriculum" }, { status: 400 });
  if (!subjectName || !gradeLevel) return NextResponse.json({ error: "subjectName and gradeLevel required" }, { status: 400 });

  const canonical = await canonicalSubjectName(schoolId, subjectName);
  const strands = await db.curriculumStrand.findMany({
    where: { curriculum, subjectName: canonical, gradeLevel },
    orderBy: { sortOrder: "asc" },
    include: {
      subStrands: {
        orderBy: { sortOrder: "asc" },
        include: {
          standards: {
            orderBy: { code: "asc" },
            include: { indicators: { orderBy: { sortOrder: "asc" } } },
          },
        },
      },
    },
  });
  return NextResponse.json({ curriculum, subjectName: canonical, gradeLevel, strands });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["curriculum.review", "settings.manage", "curriculum.view"] });
  if (!auth.ok) return auth.res;
  const body = await req.json().catch(() => ({}));
  const action = String(body.action ?? "");
  const str = (v: unknown) => (typeof v === "string" ? v.trim() : "");
  const nullable = (v: unknown) => { const s = str(v); return s ? s : null; };

  try {
    switch (action) {
      case "strand.save": {
        const curriculum = str(body.curriculum);
        const subjectName = await canonicalSubjectName(schoolId, str(body.subjectName));
        const gradeLevel = str(body.gradeLevel);
        const name = str(body.name);
        if (!isCurriculumKey(curriculum) || !subjectName || !gradeLevel || !name)
          return NextResponse.json({ error: "curriculum, subjectName, gradeLevel and name are required" }, { status: 400 });
        const code = nullable(body.code);
        const sortOrder = Number(body.sortOrder ?? 0) || 0;
        const existing = await db.curriculumStrand.findUnique({
          where: { curriculum_subjectName_gradeLevel_name: { curriculum, subjectName, gradeLevel, name } },
        });
        if (body.id) {
          const row = await db.curriculumStrand.update({ where: { id: str(body.id) }, data: { name, code, sortOrder } });
          return NextResponse.json({ ok: true, strand: row });
        }
        if (existing) return NextResponse.json({ error: `Strand “${name}” already exists for ${subjectName} ${gradeLevel}` }, { status: 409 });
        const row = await db.curriculumStrand.create({ data: { curriculum, subjectName, gradeLevel, name, code, sortOrder } });
        return NextResponse.json({ ok: true, strand: row });
      }
      case "strand.delete": {
        await db.curriculumStrand.delete({ where: { id: str(body.id) } });
        return NextResponse.json({ ok: true });
      }
      case "sub.save": {
        const strandId = str(body.strandId);
        const name = str(body.name);
        if (!strandId || !name) return NextResponse.json({ error: "strandId and name are required" }, { status: 400 });
        const dup = await db.curriculumSubStrand.findFirst({ where: { strandId, name, NOT: body.id ? { id: str(body.id) } : undefined } });
        if (dup) return NextResponse.json({ error: `Sub-strand “${name}” already exists in this strand` }, { status: 409 });
        const data = { name, code: nullable(body.code), sortOrder: Number(body.sortOrder ?? 0) || 0 };
        const row = body.id
          ? await db.curriculumSubStrand.update({ where: { id: str(body.id) }, data })
          : await db.curriculumSubStrand.create({ data: { ...data, strandId } });
        return NextResponse.json({ ok: true, sub: row });
      }
      case "sub.delete": {
        await db.curriculumSubStrand.delete({ where: { id: str(body.id) } });
        return NextResponse.json({ ok: true });
      }
      case "standard.save": {
        const subStrandId = str(body.subStrandId);
        const statement = str(body.statement);
        if (!subStrandId || !statement) return NextResponse.json({ error: "subStrandId and statement are required" }, { status: 400 });
        const dup = await db.curriculumContentStandard.findFirst({ where: { subStrandId, statement, NOT: body.id ? { id: str(body.id) } : undefined } });
        if (dup) return NextResponse.json({ error: "This content standard already exists in the sub-strand" }, { status: 409 });
        const data = { statement, code: nullable(body.code) };
        const row = body.id
          ? await db.curriculumContentStandard.update({ where: { id: str(body.id) }, data })
          : await db.curriculumContentStandard.create({ data: { ...data, subStrandId } });
        return NextResponse.json({ ok: true, standard: row });
      }
      case "standard.delete": {
        await db.curriculumContentStandard.delete({ where: { id: str(body.id) } });
        return NextResponse.json({ ok: true });
      }
      case "indicator.save": {
        const standardId = str(body.standardId);
        const statement = str(body.statement);
        if (!standardId || !statement) return NextResponse.json({ error: "standardId and statement are required" }, { status: 400 });
        const dup = await db.curriculumIndicator.findFirst({ where: { standardId, statement, NOT: body.id ? { id: str(body.id) } : undefined } });
        if (dup) return NextResponse.json({ error: "This indicator already exists on the content standard" }, { status: 409 });
        const data = { statement, code: nullable(body.code), sortOrder: Number(body.sortOrder ?? 0) || 0 };
        const row = body.id
          ? await db.curriculumIndicator.update({ where: { id: str(body.id) }, data })
          : await db.curriculumIndicator.create({ data: { ...data, standardId } });
        return NextResponse.json({ ok: true, indicator: row });
      }
      case "indicator.delete": {
        await db.curriculumIndicator.delete({ where: { id: str(body.id) } });
        return NextResponse.json({ ok: true });
      }
      /* CSV import — one row per indicator/standard/sub-strand/strand, upserted.
         Header: curriculum,subjectName,gradeLevel,strand,strandCode,subStrand,subStrandCode,standard,standardCode,indicator,indicatorCode
         (matches CI3 curriculum_import.php; blank lower levels are skipped so a
         strand-only row imports the strand, a standard-only row attaches to an
         existing strand/sub-strand, etc.) */
      case "framework.import": {
        const csv = str(body.csv);
        if (!csv) return NextResponse.json({ error: "csv payload required" }, { status: 400 });
        const lines = csv.split(/\r?\n/).map((l) => l.trim()).filter(Boolean);
        if (!lines.length) return NextResponse.json({ error: "csv payload is empty" }, { status: 400 });
        const split = (line: string) => line.split(",").map((c) => c.trim().replace(/^"(.*)"$/, "$1"));
        let start = 0;
        const header = split(lines[0]).map((h) => h.toLowerCase());
        if (header.includes("strand")) start = 1;
        let imported = 0, skipped = 0;
        const errors: string[] = [];
        for (let i = start; i < lines.length; i++) {
          const c = split(lines[i]);
          const col = (name: string) => { const idx = header.length ? header.indexOf(name) : -1; return idx >= 0 ? (c[idx] ?? "") : ""; };
          const curriculum = (header.length ? col("curriculum") : c[0] ?? "").toUpperCase();
          const subjectRaw = header.length ? col("subjectname") : c[1] ?? "";
          const gradeLevel = header.length ? col("gradelevel") : c[2] ?? "";
          const strandName = header.length ? col("strand") : c[3] ?? "";
          const strandCode = header.length ? col("strandcode") : c[4] ?? "";
          const subName = header.length ? col("substrand") : c[5] ?? "";
          const subCode = header.length ? col("substrandcode") : c[6] ?? "";
          const stdStatement = header.length ? col("standard") : c[7] ?? "";
          const stdCode = header.length ? col("standardcode") : c[8] ?? "";
          const indStatement = header.length ? col("indicator") : c[9] ?? "";
          const indCode = header.length ? col("indicatorcode") : c[10] ?? "";
          if (!isCurriculumKey(curriculum) || !subjectRaw || !gradeLevel || !strandName) { skipped++; continue; }
          try {
            const subjectName = await canonicalSubjectName(schoolId, subjectRaw);
            const strand = await db.curriculumStrand.upsert({
              where: { curriculum_subjectName_gradeLevel_name: { curriculum, subjectName, gradeLevel, name: strandName } },
              create: { curriculum, subjectName, gradeLevel, name: strandName, code: strandCode || null, sortOrder: 99 },
              update: strandCode ? { code: strandCode } : {},
            });
            let cursor = strand.id;
            if (subName) {
              const sub = await db.curriculumSubStrand.upsert({
                where: { strandId_name: { strandId: cursor, name: subName } },
                create: { strandId: cursor, name: subName, code: subCode || null, sortOrder: 99 },
                update: subCode ? { code: subCode } : {},
              });
              cursor = sub.id;
            }
            if (stdStatement) {
              const std = await db.curriculumContentStandard.upsert({
                where: { subStrandId_statement: { subStrandId: cursor, statement: stdStatement } },
                create: { subStrandId: cursor, statement: stdStatement, code: stdCode || null },
                update: stdCode ? { code: stdCode } : {},
              });
              cursor = std.id;
              if (indStatement) {
                await db.curriculumIndicator.upsert({
                  where: { standardId_statement: { standardId: cursor, statement: indStatement } },
                  create: { standardId: cursor, statement: indStatement, code: indCode || null, sortOrder: 99 },
                  update: indCode ? { code: indCode } : {},
                });
              }
            }
            imported++;
          } catch (e: any) {
            skipped++; errors.push(`line ${i + 1}: ${e?.message ?? "failed"}`);
          }
        }
        return NextResponse.json({ ok: true, imported, skipped, errors: errors.slice(0, 5) });
      }
      default:
        return NextResponse.json({ error: `Unknown action “${action}”` }, { status: 400 });
    }
  } catch (e: any) {
    if (e?.code === "P2025") return NextResponse.json({ error: "Record not found (already deleted?)" }, { status: 404 });
    return NextResponse.json({ error: e?.message ?? "Framework operation failed" }, { status: 500 });
  }
}

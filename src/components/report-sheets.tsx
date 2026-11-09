"use client";
/* Nyansapo — Terminal report sheet styles (iedupro parity).
   Three professional print-ready designs:
   · ClassicSheet  — formal GES broadsheet: two-tier score header
     (CLASS 30 / EXAM 70 / TOTAL 100), per-subject position, colour-coded
     grades, BECE best-six aggregate, conduct & interest, grading key,
     triple signature block, next-term date
   · ModernSheet   — compact Kente-accented card with score split
   · CompactSheet  — primary / KG descriptor sheet (code · raw score ·
     grade · descriptor) — no ranking, iedupro enhanced-print style
   All A4 print-optimised; wrap in .print-area — each sheet carries
   .print-sheet so bulk printing paginates cleanly, and break-inside-avoid
   (CI3 .page-container { page-break-inside: avoid } parity) keeps a sheet
   or a subject row from being sliced across a page boundary. */
import { Badge } from "@/components/ui/badge";
import { LogoMark, KenteStrip } from "@/components/brand";
import { SCALES_TABLE } from "@/lib/grading-scale";
import { cn } from "@/lib/utils";

export type SheetResult = {
  subject: string; code?: string | null; isCore?: boolean;
  classScore?: number | null; examScore?: number | null;
  total: number; grade: string; remark: string; position?: number | null;
};
export type SheetData = {
  student: { name: string; index?: string; classroom: string; gender?: string };
  termName: string;
  curriculum?: string; // GES | CAIE | IB
  position: number | null;
  classSize: number | null;
  average: number | null;
  conduct?: string | null;
  interest?: string | null;
  promotion?: string | null;
  attendanceSummary?: string | null;
  teacherRemark?: string | null;
  headRemark?: string | null;
  aiGenerated?: boolean;
  published?: boolean;
  school: { name: string; motto?: string | null; town?: string | null; region?: string | null; phone?: string | null };
  results: SheetResult[];
  nextTermBegins?: string | null;
};

function gradingBands(curriculum?: string) {
  return SCALES_TABLE[curriculum ?? "GES"] ?? SCALES_TABLE.GES;
}

const ordinal = (n: number) => {
  const s = ["th", "st", "nd", "rd"], v = n % 100;
  return n + (s[(v - 20) % 10] || s[v] || s[0]);
};

/* grade-code → band index → colour chip (iedupro grade-a1…grade-f parity) */
const GRADE_CHIPS = [
  "bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-300",
  "bg-sky-100 text-sky-900 dark:bg-sky-950 dark:text-sky-300",
  "bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-300",
  "bg-orange-100 text-orange-900 dark:bg-orange-950 dark:text-orange-300",
  "bg-rose-100 text-rose-900 dark:bg-rose-950 dark:text-rose-300",
];
function gradeChipIdx(curriculum: string | undefined, grade: string): number {
  const bands = gradingBands(curriculum).bands;
  const i = bands.findIndex((b) => b.grade === grade);
  if (i < 0) return 4;
  return Math.min(Math.floor((i / bands.length) * 5), 4);
}

/* WAEC BECE best-six aggregate (core subjects first, then best others; grades 1–9) */
export function beceAggregate(results: SheetResult[]): number | null {
  const waec = (t: number) => (t >= 75 ? 1 : t >= 70 ? 2 : t >= 65 ? 3 : t >= 60 ? 4 : t >= 55 ? 5 : t >= 50 ? 6 : t >= 45 ? 7 : t >= 40 ? 8 : 9);
  const scored = results.filter((r) => typeof r.total === "number");
  if (scored.length < 6) return null;
  const byTotal = (a: SheetResult, b: SheetResult) => b.total - a.total;
  const six = [...scored.filter((r) => r.isCore).sort(byTotal), ...scored.filter((r) => !r.isCore).sort(byTotal)].slice(0, 6);
  return six.reduce((s, r) => s + waec(r.total), 0);
}

/* ── CLASSIC GES BROADSHEET (iedupro terminal_report_print) ──── */
export function ClassicSheet({ d }: { d: SheetData }) {
  const bands = gradingBands(d.curriculum);
  const hasSplit = d.results.some((r) => r.classScore != null || r.examScore != null);
  const aggregate = d.curriculum === "GES" || !d.curriculum ? beceAggregate(d.results) : null;
  return (
    <div className="print-sheet break-inside-avoid bg-card text-card-foreground rounded-xl border shadow-sm overflow-hidden mx-auto max-w-[860px]">
      {/* letterhead */}
      <div className="px-8 pt-6 pb-4 text-center border-b-2 border-foreground/80">
        <div className="flex items-center justify-center gap-3">
          <LogoMark size={52} />
          <div className="text-left">
            <div className="font-bold text-xl tracking-tight uppercase leading-tight">{d.school.name}</div>
            <div className="text-[11px] text-muted-foreground">
              {[d.school.town, d.school.region].filter(Boolean).join(", ")}{d.school.phone ? ` · ${d.school.phone}` : ""}
            </div>
            {d.school.motto && <div className="text-[10px] italic text-muted-foreground">{d.school.motto}</div>}
          </div>
        </div>
        <div className="mt-3 inline-block border-2 border-foreground px-4 py-1 font-bold tracking-[0.2em] text-sm uppercase">
          Terminal Report Sheet
        </div>
        <div className="text-xs text-muted-foreground mt-1.5">{d.termName} · Graded on the {bands.name}</div>
      </div>

      <div className="px-8 py-5">
        {/* bio grid */}
        <div className="grid grid-cols-3 gap-x-6 gap-y-2 text-[13px]">
          <div className="col-span-2"><span className="text-muted-foreground">Name of learner: </span><b className="uppercase">{d.student.name}</b></div>
          <div><span className="text-muted-foreground">Index / ID: </span><b className="font-mono">{d.student.index ?? "—"}</b></div>
          <div><span className="text-muted-foreground">Class: </span><b>{d.student.classroom}</b></div>
          <div><span className="text-muted-foreground">Position: </span><b>{d.position ? `${ordinal(d.position)} of ${d.classSize}` : "—"}</b></div>
          <div><span className="text-muted-foreground">Attendance: </span><b>{d.attendanceSummary ?? "—"}</b></div>
        </div>

        {/* learning areas — two-tier score header (Ghana 30/70 standard) */}
        <table className="w-full text-[12.5px] mt-4 border border-foreground/50">
          <thead>
            <tr className="bg-foreground text-background">
              <th rowSpan={2} className="px-3 py-2 font-semibold uppercase text-[10px] tracking-wider text-left">Learning area</th>
              {hasSplit && <th colSpan={2} className="px-2 py-1.5 font-semibold uppercase text-[10px] tracking-wider text-center border-l border-background/30">Scores</th>}
              <th rowSpan={2} className="px-2 py-2 font-semibold uppercase text-[10px] tracking-wider text-center w-[76px] border-l border-background/30">Total<br />(100)</th>
              <th rowSpan={2} className="px-2 py-2 font-semibold uppercase text-[10px] tracking-wider text-center w-14 border-l border-background/30">Grade</th>
              <th rowSpan={2} className="px-2 py-2 font-semibold uppercase text-[10px] tracking-wider text-center w-12 border-l border-background/30">Pos.</th>
              <th rowSpan={2} className="px-3 py-2 font-semibold uppercase text-[10px] tracking-wider text-left border-l border-background/30">Subject teacher&apos;s remark</th>
            </tr>
            {hasSplit && (
              <tr className="bg-foreground/90 text-background">
                <th className="px-1.5 py-1.5 font-semibold uppercase text-[9px] tracking-wider text-center w-16 border-l border-background/30">Class<br />(30)</th>
                <th className="px-1.5 py-1.5 font-semibold uppercase text-[9px] tracking-wider text-center w-16">Exam<br />(70)</th>
              </tr>
            )}
          </thead>
          <tbody>
            {d.results.map((r, i) => {
              const band = bands.bands.find((b) => r.total >= b.min) ?? bands.bands[bands.bands.length - 1];
              const chip = GRADE_CHIPS[gradeChipIdx(d.curriculum, r.grade)];
              return (
                <tr key={i} className={cn("break-inside-avoid", i % 2 && "bg-secondary/40")}>
                  <td className="px-3 py-1.5 border-t border-foreground/20 font-medium">
                    {r.isCore && <span title="Core subject" className="mr-1 text-amber-600 dark:text-amber-400">★</span>}
                    {r.subject}
                    {r.code && <span className="ml-1.5 font-mono text-[10px] text-muted-foreground">{r.code}</span>}
                  </td>
                  {hasSplit && <td className="px-1.5 py-1.5 border-t border-foreground/20 text-center tabular-nums">{r.classScore ?? "—"}</td>}
                  {hasSplit && <td className="px-1.5 py-1.5 border-t border-foreground/20 text-center tabular-nums">{r.examScore ?? "—"}</td>}
                  <td className="px-2 py-1.5 border-t border-foreground/20 text-center tabular-nums font-semibold">{r.total}</td>
                  <td className="px-2 py-1.5 border-t border-foreground/20 text-center">
                    <span className={`inline-block min-w-7 rounded px-1.5 py-0.5 font-bold ${chip}`}>{r.grade}</span>
                  </td>
                  <td className="px-2 py-1.5 border-t border-foreground/20 text-center text-[11px] font-semibold">{r.position ? ordinal(r.position) : "—"}</td>
                  <td className="px-3 py-1.5 border-t border-foreground/20 text-muted-foreground text-[11.5px]">{r.remark}</td>
                </tr>
              );
            })}
            <tr className="break-inside-avoid bg-foreground/10 font-semibold">
              <td className="px-3 py-2 border-t-2 border-foreground/50">Overall average</td>
              {hasSplit && <td colSpan={2} className="px-2 py-2 border-t-2 border-foreground/50 text-center text-[10px] uppercase tracking-widest text-muted-foreground">Class 30 + Exam 70</td>}
              <td className="px-2 py-2 border-t-2 border-foreground/50 text-center tabular-nums">{d.average ?? "—"}%</td>
              <td className="px-2 py-2 border-t-2 border-foreground/50 text-center text-[11px]" colSpan={hasSplit ? 3 : 2}>
                {aggregate != null ? <>BECE aggregate (best six): <b>{aggregate}</b> · </> : ""}
                {d.promotion ? `Promotion: ${d.promotion === "PROMOTED" ? "Promoted" : d.promotion === "REPEAT" ? "To repeat the class" : d.promotion}` : "—"}
              </td>
            </tr>
          </tbody>
        </table>

        {/* grading key */}
        <div className="mt-3 rounded border border-foreground/30 p-2.5">
          <div className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground mb-1">Grading key — {bands.name}</div>
          <div className="flex flex-wrap gap-x-4 gap-y-1 text-[10.5px]">
            {bands.bands.map((b, i) => {
              const prev = i === 0 ? 100 : bands.bands[i - 1].min - 1;
              return (
                <span key={b.grade} className="text-muted-foreground">
                  <b className="text-foreground">{b.grade}</b> · {b.min}–{prev} · {b.label}
                </span>
              );
            })}
            <span className="text-muted-foreground">★ core subject</span>
          </div>
        </div>

        {/* conduct, interest & disposition */}
        <div className="grid grid-cols-3 gap-4 mt-4 text-[12.5px]">
          <div>
            <div className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground">Conduct</div>
            <p className="mt-0.5">{d.conduct ?? "—"}</p>
          </div>
          <div>
            <div className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground">Interests</div>
            <p className="mt-0.5">{d.interest ?? "—"}</p>
          </div>
          <div>
            <div className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground">Learning disposition</div>
            <p className="mt-0.5 text-muted-foreground">{d.average == null ? "—" : d.average >= 75 ? "Highly engaged, consistent performer" : d.average >= 55 ? "Steady engagement, improving steadily" : "Needs closer support and supervision"}</p>
          </div>
        </div>

        <div className="mt-4 space-y-3 text-[12.5px]">
          <div>
            <div className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground">Class teacher&apos;s remarks</div>
            <p className="mt-1 min-h-6 italic">“{d.teacherRemark ?? "—"}”</p>
          </div>
          <div>
            <div className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground">Head of school&apos;s remarks</div>
            <p className="mt-1 min-h-6 italic">“{d.headRemark ?? "—"}”</p>
          </div>
        </div>

        {/* signatures + next term */}
        <div className="grid grid-cols-3 gap-8 pt-8 text-[10px] text-muted-foreground">
          <div className="border-t border-foreground/60 pt-1">Class teacher (sign &amp; date)</div>
          <div className="border-t border-foreground/60 pt-1">Head of school (sign &amp; stamp)</div>
          <div className="border-t border-foreground/60 pt-1">Parent / guardian (sign)</div>
        </div>
        <div className="mt-3 flex justify-between text-[11px]">
          <span className="text-muted-foreground">{d.nextTermBegins ? <>Next term begins: <b className="text-foreground">{d.nextTermBegins}</b></> : ""}</span>
          <span className="text-muted-foreground">Status: <b className={d.published ? "text-foreground" : "text-amber-600"}>{d.published ? "OFFICIAL" : "PROVISIONAL"}</b></span>
        </div>
      </div>
      <KenteStrip />
    </div>
  );
}

/* ── MODERN COMPACT CARD ────────────────────────────────────── */
export function ModernSheet({ d }: { d: SheetData }) {
  const bands = gradingBands(d.curriculum);
  const aggregate = d.curriculum === "GES" || !d.curriculum ? beceAggregate(d.results) : null;
  return (
    <div className="print-sheet break-inside-avoid bg-card text-card-foreground rounded-xl border shadow-sm overflow-hidden mx-auto max-w-[780px]">
      <KenteStrip />
      <div className="p-6">
        <div className="flex items-center justify-between gap-3">
          <div className="flex items-center gap-2.5">
            <LogoMark size={40} />
            <div>
              <div className="font-bold text-lg leading-tight">Terminal Report Card</div>
              <div className="text-xs text-muted-foreground">{d.termName} · {bands.name.split("—")[0].trim()}</div>
            </div>
          </div>
          <Badge variant={d.published ? "default" : "secondary"}>{d.published ? "PUBLISHED" : "DRAFT"}</Badge>
        </div>

        <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-4 text-sm">
          <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Learner</div><div className="font-semibold">{d.student.name}</div></div>
          <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Index</div><div className="font-mono text-xs font-semibold mt-0.5">{d.student.index ?? "—"}</div></div>
          <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Class</div><div className="font-semibold">{d.student.classroom}</div></div>
          <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Position</div><div className="font-semibold">{d.position != null ? `${d.position} of ${d.classSize ?? "—"}` : "—"}</div></div>
        </div>

        <table className="w-full text-sm mt-4 border rounded-lg overflow-hidden">
          <thead>
            <tr className="bg-secondary text-left">
              <th className="px-2.5 py-2 font-medium">Subject</th>
              <th className="px-2 py-2 font-medium text-center text-[11px]" title="Class score (30%)">Cls 30</th>
              <th className="px-2 py-2 font-medium text-center text-[11px]" title="Exam score (70%)">Exam 70</th>
              <th className="px-2.5 py-2 font-medium text-center">Total</th>
              <th className="px-2.5 py-2 font-medium">Grade</th>
              <th className="px-2.5 py-2 font-medium text-center text-[11px]">Pos</th>
              <th className="px-2.5 py-2 font-medium hidden sm:table-cell">Remark</th>
            </tr>
          </thead>
          <tbody>
            {d.results.map((r: any, i: number) => {
              const band = bands.bands.find((b) => r.total >= b.min) ?? bands.bands[bands.bands.length - 1];
              const chip = GRADE_CHIPS[gradeChipIdx(d.curriculum, r.grade)];
              return (
                <tr key={i} className="border-t break-inside-avoid">
                  <td className="px-2.5 py-2 font-medium">{r.isCore && <span className="mr-1 text-amber-600 dark:text-amber-400">★</span>}{r.subject}</td>
                  <td className="px-2 py-2 text-center tabular-nums text-muted-foreground">{r.classScore ?? "—"}</td>
                  <td className="px-2 py-2 text-center tabular-nums text-muted-foreground">{r.examScore ?? "—"}</td>
                  <td className="px-2.5 py-2 text-center font-semibold tabular-nums">{r.total}</td>
                  <td className="px-2.5 py-2"><span className={`inline-block min-w-7 rounded px-1.5 py-0.5 text-xs font-bold ${chip}`}>{r.grade}</span></td>
                  <td className="px-2.5 py-2 text-center text-xs font-semibold">{r.position ?? "—"}</td>
                  <td className="px-2.5 py-2 text-muted-foreground text-xs hidden sm:table-cell">{r.remark}{band ? "" : ""}</td>
                </tr>
              );
            })}
          </tbody>
        </table>

        <div className="grid sm:grid-cols-4 gap-2 mt-4 text-sm">
          <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Average</div><div className="font-bold text-lg">{d.average != null ? `${d.average}%` : "—"}</div></div>
          <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Conduct</div><div className="font-semibold">{d.conduct ?? "—"}</div></div>
          <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Interests</div><div className="font-semibold">{d.interest ?? "—"}</div></div>
          <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">{aggregate != null ? "BECE aggregate" : "Promotion"}</div><div className="font-semibold">{aggregate != null ? `${aggregate} (best six)` : (d.promotion ?? "—")}</div></div>
        </div>

        <div className="mt-4 space-y-3">
          <div className="rounded-lg border p-3">
            <div className="text-xs font-semibold uppercase text-muted-foreground">Class teacher&apos;s remarks</div>
            <p className="text-sm mt-1 italic">“{d.teacherRemark ?? "—"}”</p>
          </div>
          <div className="rounded-lg border p-3">
            <div className="text-xs font-semibold uppercase text-muted-foreground">Head of school&apos;s remarks</div>
            <p className="text-sm mt-1 italic">“{d.headRemark ?? "—"}”</p>
          </div>
          <div className="text-[11px] text-muted-foreground">
            Attendance: {d.attendanceSummary ?? "—"} · Generated by Nyansapo — The Wisdom Knot Cloud
          </div>
        </div>
      </div>
      <KenteStrip />
    </div>
  );
}

/* ── COMPACT PRIMARY / KG DESCRIPTOR SHEET (iedupro enhanced) ── */
export function CompactSheet({ d }: { d: SheetData }) {
  const bands = gradingBands(d.curriculum);
  return (
    <div className="print-sheet break-inside-avoid bg-card text-card-foreground rounded-xl border shadow-sm overflow-hidden mx-auto max-w-[820px]">
      <div className="px-8 pt-6 pb-4">
        <div className="flex items-center justify-between gap-3">
          <div className="flex items-center gap-3">
            <LogoMark size={46} />
            <div>
              <div className="font-bold text-lg uppercase tracking-tight leading-tight">{d.school.name}</div>
              <div className="text-[10.5px] text-muted-foreground">{[d.school.town, d.school.region].filter(Boolean).join(", ")}{d.school.phone ? ` · ${d.school.phone}` : ""}</div>
            </div>
          </div>
          <div className="text-right">
            <div className="inline-block border-2 border-foreground px-3 py-0.5 font-bold tracking-[0.15em] text-[11px] uppercase">Progress Report</div>
            <div className="text-[10px] text-muted-foreground mt-1">{d.termName}</div>
          </div>
        </div>
      </div>
      <div className="px-8 pb-6">
        {/* bio strip */}
        <div className="grid grid-cols-2 gap-y-1.5 text-[12.5px] border-y border-foreground/30 py-3">
          <div className="col-span-2"><span className="text-muted-foreground">Learner: </span><b className="uppercase">{d.student.name}</b></div>
          <div><span className="text-muted-foreground">Class: </span><b>{d.student.classroom}</b></div>
          <div><span className="text-muted-foreground">Learner ID: </span><b className="font-mono">{d.student.index ?? "—"}</b></div>
        </div>

        {/* descriptor table — no ranking in early years */}
        <table className="w-full text-[12.5px] mt-4 border border-foreground/40">
          <thead>
            <tr className="bg-foreground/90 text-background text-left">
              <th className="px-2.5 py-2 font-semibold uppercase text-[10px] tracking-wider w-14">Code</th>
              <th className="px-3 py-2 font-semibold uppercase text-[10px] tracking-wider">Learning area</th>
              <th className="px-2 py-2 font-semibold uppercase text-[10px] tracking-wider text-center w-[86px]">Raw score (100)</th>
              <th className="px-2 py-2 font-semibold uppercase text-[10px] tracking-wider text-center w-16">Grade</th>
              <th className="px-3 py-2 font-semibold uppercase text-[10px] tracking-wider w-[190px]">Descriptor</th>
            </tr>
          </thead>
          <tbody>
            {d.results.map((r, i) => {
              const band = bands.bands.find((b) => r.total >= b.min) ?? bands.bands[bands.bands.length - 1];
              const chip = GRADE_CHIPS[gradeChipIdx(d.curriculum, r.grade)];
              return (
                <tr key={i} className={cn("break-inside-avoid", i % 2 && "bg-secondary/40")}>
                  <td className="px-2.5 py-2 border-t border-foreground/20 font-mono text-[11px] text-muted-foreground">{r.code ?? "—"}</td>
                  <td className="px-3 py-2 border-t border-foreground/20 font-medium">{r.subject}</td>
                  <td className="px-2 py-2 border-t border-foreground/20 text-center tabular-nums font-semibold">{r.total}</td>
                  <td className="px-2 py-2 border-t border-foreground/20 text-center"><span className={`inline-block min-w-7 rounded px-1.5 py-0.5 font-bold ${chip}`}>{r.grade}</span></td>
                  <td className="px-3 py-2 border-t border-foreground/20 text-[11.5px]">{band.label} — {r.remark}</td>
                </tr>
              );
            })}
            <tr className="break-inside-avoid bg-foreground/10 font-semibold">
              <td colSpan={2} className="px-3 py-2 border-t-2 border-foreground/50">Overall performance</td>
              <td className="px-2 py-2 border-t-2 border-foreground/50 text-center tabular-nums">{d.average ?? "—"}%</td>
              <td colSpan={2} className="px-3 py-2 border-t-2 border-foreground/50 text-center">
                {(() => { const b = d.average == null ? null : bands.bands.find((x) => d.average! >= x.min); return b ? `${b.grade} — ${b.label}` : "—"; })()}
              </td>
            </tr>
          </tbody>
        </table>

        {/* descriptors key */}
        <div className="mt-3 rounded border border-foreground/30 p-2.5">
          <div className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground mb-1">Descriptors — {bands.name}</div>
          <div className="flex flex-wrap gap-x-4 gap-y-1 text-[10.5px]">
            {bands.bands.map((b, i) => (
              <span key={b.grade} className="text-muted-foreground"><b className="text-foreground">{b.grade}</b> · {b.label}</span>
            ))}
          </div>
        </div>

        {/* teacher narrative */}
        <div className="mt-4 space-y-3 text-[12.5px]">
          <div className="rounded border border-foreground/25 p-3">
            <div className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground">How {d.student.name.split(" ")[0]} is getting on</div>
            <p className="mt-1 italic">“{d.teacherRemark ?? "—"}”</p>
          </div>
          <div className="rounded border border-foreground/25 p-3">
            <div className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground">Head of school&apos;s comments</div>
            <p className="mt-1 italic">“{d.headRemark ?? "—"}”</p>
          </div>
        </div>

        <div className="grid grid-cols-2 gap-8 pt-8 text-[10px] text-muted-foreground">
          <div className="border-t border-foreground/60 pt-1">Class teacher</div>
          <div className="border-t border-foreground/60 pt-1">Parent / guardian</div>
        </div>
        <div className="mt-2 text-[11px] text-muted-foreground">
          Attendance: {d.attendanceSummary ?? "—"}{d.nextTermBegins ? <> · Next term begins: <b className="text-foreground">{d.nextTermBegins}</b></> : null}
          {" "}· Status: <b className={d.published ? "text-foreground" : "text-amber-600"}>{d.published ? "OFFICIAL" : "PROVISIONAL"}</b>
        </div>
      </div>
      <KenteStrip />
    </div>
  );
}

/* ── CRÈCHE / EARLY-YEARS DEVELOPMENTAL REPORT (NaCCA domains) ──
   A5-ish progress sheet: one section per developmental domain, one
   row per observable skill with its proficiency code badge, the
   grading legend (code → label), narrative remarks, attendance and
   signature lines. No scores, no ranking — early-years convention. */
export type CrecheSheetSkill = { skill: string; code: string | null };
export type CrecheSheetDomain = {
  domain: string;
  dominant: string | null;   // mode of the learner's codes in this domain
  label?: string | null;     // scale label of the dominant code
  ratedCount?: number | null;
  skills: CrecheSheetSkill[];
};
export type CrecheSheetData = {
  student: { name: string; index?: string; classroom: string };
  termName: string;
  scale: { code: string; label: string }[];
  domains: CrecheSheetDomain[];
  conduct?: string | null;
  attendanceSummary?: string | null;
  teacherRemark?: string | null;
  headRemark?: string | null;
  published?: boolean;
  school: { name: string; motto?: string | null; town?: string | null; region?: string | null; phone?: string | null };
};

const CRECHE_CHIPS: Record<string, string> = {
  EX: "bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-300",
  GD: "bg-lime-100 text-lime-900 dark:bg-lime-950 dark:text-lime-300",
  SD: "bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-300",
  NA: "bg-rose-100 text-rose-900 dark:bg-rose-950 dark:text-rose-300",
};

export function CrecheSheet({ d }: { d: CrecheSheetData }) {
  return (
    <div className="print-sheet break-inside-avoid bg-card text-card-foreground rounded-xl border shadow-sm overflow-hidden mx-auto max-w-[760px]">
      {/* letterhead */}
      <div className="px-7 pt-5 pb-3 text-center border-b-2 border-foreground/80">
        <div className="flex items-center justify-center gap-3">
          <LogoMark size={44} />
          <div className="text-left">
            <div className="font-bold text-lg tracking-tight uppercase leading-tight">{d.school.name}</div>
            <div className="text-[10px] text-muted-foreground">
              {[d.school.town, d.school.region].filter(Boolean).join(", ")}{d.school.phone ? ` · ${d.school.phone}` : ""}
            </div>
            {d.school.motto && <div className="text-[9px] italic text-muted-foreground">{d.school.motto}</div>}
          </div>
        </div>
        <div className="mt-2.5 inline-block border-2 border-foreground px-4 py-1 font-bold tracking-[0.2em] text-xs uppercase">
          Early-Years Progress Report
        </div>
        <div className="text-[11px] text-muted-foreground mt-1.5">{d.termName} · Crèche / Kindergarten developmental domains</div>
      </div>

      <div className="px-7 py-4">
        {/* bio strip */}
        <div className="grid grid-cols-3 gap-x-5 gap-y-1.5 text-[12.5px] border-y border-foreground/30 py-2.5">
          <div className="col-span-2"><span className="text-muted-foreground">Name of learner: </span><b className="uppercase">{d.student.name}</b></div>
          <div><span className="text-muted-foreground">Learner ID: </span><b className="font-mono">{d.student.index ?? "—"}</b></div>
          <div><span className="text-muted-foreground">Class: </span><b>{d.student.classroom}</b></div>
          <div className="col-span-2"><span className="text-muted-foreground">Attendance: </span><b>{d.attendanceSummary ?? "—"}</b></div>
        </div>

        {/* grading legend */}
        <div className="mt-3 rounded border border-foreground/30 p-2.5">
          <div className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground mb-1">Proficiency ratings</div>
          <div className="flex flex-wrap gap-x-4 gap-y-1 text-[10.5px]">
            {d.scale.map((s) => (
              <span key={s.code} className="text-muted-foreground">
                <b className="text-foreground">{s.code}</b> · {s.label}
              </span>
            ))}
            <span className="text-muted-foreground">— · not yet observed</span>
          </div>
        </div>

        {/* per-domain skill tables */}
        <div className="mt-4 space-y-4">
          {d.domains.map((dom) => (
            <div key={dom.domain} className="break-inside-avoid">
              <div className="flex flex-wrap items-center justify-between gap-2 bg-foreground text-background px-3 py-1.5">
                <span className="font-semibold uppercase text-[11px] tracking-wider">{dom.domain}</span>
                <span className="flex items-center gap-2 text-[10px]">
                  {typeof dom.ratedCount === "number" && <span className="opacity-80">{dom.ratedCount} of {dom.skills.length} indicators rated</span>}
                  {dom.dominant ? (
                    <span className="rounded bg-background/20 px-1.5 py-0.5 font-bold">{dom.dominant}{dom.label ? ` — ${dom.label}` : ""}</span>
                  ) : (
                    <span className="opacity-70">no ratings yet</span>
                  )}
                </span>
              </div>
              <table className="w-full text-[12px] border border-t-0 border-foreground/40">
                <tbody>
                  {dom.skills.map((s, i) => (
                    <tr key={i} className={cn("break-inside-avoid", i % 2 && "bg-secondary/40")}>
                      <td className="px-3 py-1.5 border-t border-foreground/15 w-10 text-center text-[10px] text-muted-foreground tabular-nums">{i + 1}</td>
                      <td className="px-3 py-1.5 border-t border-foreground/15">{s.skill}</td>
                      <td className="px-2 py-1.5 border-t border-foreground/15 text-center w-16">
                        {s.code ? (
                          <span className={`inline-block min-w-8 rounded px-1.5 py-0.5 font-bold ${CRECHE_CHIPS[s.code] ?? "bg-secondary text-secondary-foreground"}`}>{s.code}</span>
                        ) : (
                          <span className="text-muted-foreground">—</span>
                        )}
                      </td>
                    </tr>
                  ))}
                  {dom.skills.length === 0 && (
                    <tr><td colSpan={3} className="px-3 py-2 border-t border-foreground/15 text-muted-foreground text-[11px]">No indicators configured for this domain yet.</td></tr>
                  )}
                </tbody>
              </table>
            </div>
          ))}
          {d.domains.length === 0 && (
            <div className="rounded border border-dashed border-foreground/30 p-4 text-center text-sm text-muted-foreground">
              No developmental domains configured — set them up in Settings → Early years.
            </div>
          )}
        </div>

        {/* conduct + remarks */}
        {d.conduct && (
          <div className="mt-4 text-[12.5px]">
            <div className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground">Conduct &amp; disposition</div>
            <p className="mt-0.5">{d.conduct}</p>
          </div>
        )}
        <div className="mt-4 space-y-3 text-[12.5px]">
          <div>
            <div className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground">Class teacher&apos;s remarks</div>
            <p className="mt-1 min-h-6 italic">“{d.teacherRemark ?? "—"}”</p>
          </div>
          <div>
            <div className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground">Head of school&apos;s remarks</div>
            <p className="mt-1 min-h-6 italic">“{d.headRemark ?? "—"}”</p>
          </div>
        </div>

        {/* signatures + status */}
        <div className="grid grid-cols-3 gap-8 pt-8 text-[10px] text-muted-foreground">
          <div className="border-t border-foreground/60 pt-1">Class teacher (sign &amp; date)</div>
          <div className="border-t border-foreground/60 pt-1">Head of school (sign &amp; stamp)</div>
          <div className="border-t border-foreground/60 pt-1">Parent / guardian (sign)</div>
        </div>
        <div className="mt-3 flex justify-between text-[10.5px]">
          <span className="text-muted-foreground">Rated on the {d.scale.length}-point early-years proficiency scale (NaCCA developmental domains).</span>
          <span className="text-muted-foreground">Status: <b className={d.published ? "text-foreground" : "text-amber-600"}>{d.published ? "OFFICIAL" : "PROVISIONAL"}</b></span>
        </div>
      </div>
      <KenteStrip />
    </div>
  );
}

/* ═══════════════════════════════════════════════════════════════
   WAEC-STYLE JHS STATEMENT OF RESULTS (CI3 Examination::waec_result
   parity) — the formal double-border BECE (mock) statement: core and
   elective subject tables, aggregate = best 4 core + best 2 electives
   on the WAEC 1–9 scale (lower is better), grading key, stamps.
   ═══════════════════════════════════════════════════════════════ */

/** WASSC/BECE grading scale: grade → point + interpretation (WAEC standard). */
const WAEC_SCALE: { grade: string; point: number; from: number; to: number; interpretation: string }[] = [
  { grade: "A1", point: 1, from: 80, to: 100, interpretation: "Excellent" },
  { grade: "B2", point: 2, from: 70, to: 79, interpretation: "Very Good" },
  { grade: "B3", point: 3, from: 65, to: 69, interpretation: "Good" },
  { grade: "C4", point: 4, from: 60, to: 64, interpretation: "Credit" },
  { grade: "C5", point: 5, from: 55, to: 59, interpretation: "Credit" },
  { grade: "C6", point: 6, from: 50, to: 54, interpretation: "Credit" },
  { grade: "D7", point: 7, from: 45, to: 49, interpretation: "Pass" },
  { grade: "E8", point: 8, from: 40, to: 44, interpretation: "Pass" },
  { grade: "F9", point: 9, from: 0, to: 39, interpretation: "Fail" },
];
const waecGrade = (total: number) => WAEC_SCALE.find((b) => total >= b.from && total <= b.to) ?? WAEC_SCALE[WAEC_SCALE.length - 1];

export function WaecStatement({ d }: { d: SheetData }) {
  const core = d.results.filter((r) => r.isCore).map((r) => ({ ...r, waec: waecGrade(r.total) }));
  const elective = d.results.filter((r) => !r.isCore).map((r) => ({ ...r, waec: waecGrade(r.total) }));
  // BECE aggregate: best 4 core + best 2 elective grade points (lower is better)
  const bestCore = [...core].sort((a, b) => a.waec.point - b.waec.point).slice(0, 4);
  const bestElective = [...elective].sort((a, b) => a.waec.point - b.waec.point).slice(0, 2);
  const aggregate = [...bestCore, ...bestElective].reduce((s, r) => s + r.waec.point, 0);
  const year = new Date().getFullYear();

  const table = (rows: typeof core) => (
    <table className="w-full border-collapse text-[11px]">
      <thead>
        <tr className="bg-black text-white">
          <th className="border border-black px-2 py-1 text-left">SUBJECT</th>
          <th className="border border-black px-2 py-1 w-32">GRADE</th>
          <th className="border border-black px-2 py-1 text-left w-40">INTERPRETATION</th>
        </tr>
      </thead>
      <tbody>
        {rows.map((r, i) => (
          <tr key={i}>
            <td className="border border-black px-2 py-1">{r.subject}{r.code ? <span className="text-black/50"> ({r.code})</span> : null}</td>
            <td className="border border-black px-2 py-1 text-center font-bold">{r.waec.grade}</td>
            <td className="border border-black px-2 py-1">{r.waec.interpretation}</td>
          </tr>
        ))}
        {!rows.length && (
          <tr><td className="border border-black px-2 py-1 text-black/50" colSpan={3}>None recorded</td></tr>
        )}
      </tbody>
    </table>
  );

  return (
    <div className="print-sheet break-inside-avoid bg-white text-black mx-auto max-w-[820px] p-2" style={{ fontFamily: "Georgia, 'Times New Roman', serif" }}>
      <div className="border-[3px] double border-black p-4">
        {/* header */}
        <div className="flex items-center gap-4 border-b-2 border-black pb-3">
          <div className="shrink-0 w-16 h-16 rounded-full border-2 border-black flex items-center justify-center text-[8px] text-center font-bold leading-tight">SCHOOL<br />LOGO</div>
          <div className="flex-1 text-center">
            <div className="text-lg font-bold uppercase tracking-wide">{d.school.name}</div>
            <div className="text-[11px]">
              {d.school.town ? `${d.school.town}, ` : ""}{d.school.region ?? ""}
              {d.school.phone ? ` · Tel: ${d.school.phone}` : ""}
            </div>
            <div className="mt-1.5 inline-block border border-black px-3 py-0.5 text-[11px] font-bold tracking-wide">
              JUNIOR HIGH SCHOOL STATEMENT OF RESULTS
            </div>
            <div className="text-[10px] italic mt-0.5">(BASIC EDUCATION CERTIFICATE EXAMINATION — MOCK)</div>
          </div>
        </div>

        {/* candidate */}
        <div className="grid grid-cols-2 gap-x-6 gap-y-1 text-[12px] mt-3">
          <div><b>Candidate Name:</b> <span className="uppercase">{d.student.name}</span></div>
          <div><b>Index Number:</b> {d.student.index ?? "—"}</div>
          <div><b>Exam Session:</b> {d.termName}</div>
          <div><b>Year:</b> {year}</div>
        </div>

        {/* core / elective tables */}
        <div className="mt-3 space-y-3">
          <div>
            <div className="bg-zinc-200 border border-black px-2 py-0.5 text-[11px] font-bold">CORE SUBJECTS</div>
            {table(core)}
          </div>
          {elective.length > 0 && (
            <div>
              <div className="bg-zinc-200 border border-black px-2 py-0.5 text-[11px] font-bold">ELECTIVE SUBJECTS</div>
              {table(elective)}
            </div>
          )}
        </div>

        {/* aggregate */}
        <div className="mt-3 border-2 border-yellow-600 bg-yellow-50 px-3 py-2 flex items-center justify-between">
          <div className="text-[11px]">
            <div className="font-bold">AGGREGATE — BEST 4 CORE + BEST 2 ELECTIVES</div>
            <div className="text-black/70">
              Core: {bestCore.map((r) => r.waec.grade).join(", ") || "—"} · Elective: {bestElective.map((r) => r.waec.grade).join(", ") || "—"}
            </div>
          </div>
          <div className="text-right">
            <div className="text-[10px] font-semibold">AGGREGATE SCORE</div>
            <div className="text-4xl font-bold leading-none tabular-nums">{aggregate}</div>
          </div>
        </div>

        {/* grading key */}
        <div className="mt-3 text-[10px] border border-black/40 px-2 py-1.5">
          <b>GRADE KEY:</b>{" "}
          {WAEC_SCALE.map((b) => `${b.grade} ${b.from}–${b.to}`).join("  ·  ")}
          <span className="ml-2 text-black/60">(1–3 Highly Proficient · 4–6 Proficient · 7–8 Basic · 9 Unsatisfactory)</span>
        </div>

        {/* footer */}
        <div className="mt-4 flex items-end justify-between text-[11px]">
          <div className="w-40 border border-dashed border-black h-16 flex items-center justify-center text-[9px] text-black/50">SCHOOL STAMP</div>
          <div className="text-center">
            <div className="border-t border-black px-4 pt-0.5">__________________________</div>
            <div className="mt-0.5">Head of School</div>
            <div className="text-black/60 text-[10px]">Date: {new Date().toLocaleDateString("en-GB")}</div>
          </div>
          <div className="w-40 border border-dashed border-black h-16 flex items-center justify-center text-[9px] text-black/50">OFFICE STAMP</div>
        </div>
        <div className="mt-2 text-center text-[9px] italic text-black/60">
          This is an official document. Any alteration renders it invalid. · Generated on {new Date().toLocaleString("en-GB")}
        </div>
      </div>
    </div>
  );
}

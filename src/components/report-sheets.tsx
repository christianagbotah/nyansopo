"use client";
/* Nyansapo — Terminal report sheet styles.
   Two professional print-ready designs:
   · ClassicSheet  — formal GES-style broadsheet (bio grid, learning areas,
     grading key, conduct, triple signature block, next-term date)
   · ModernSheet   — compact Kente-accented card
   Both are A4 print-optimised; wrap in .print-area and each sheet carries
   .print-sheet so bulk printing paginates cleanly. */
import { Badge } from "@/components/ui/badge";
import { LogoMark, KenteStrip } from "@/components/brand";
import { SCALES_TABLE } from "@/lib/grading-scale";

export type SheetResult = { subject: string; total: number; grade: string; remark: string };
export type SheetData = {
  student: { name: string; index?: string; classroom: string; gender?: string };
  termName: string;
  curriculum?: string; // GES | CAIE | IB
  position: number | null;
  classSize: number | null;
  average: number | null;
  conduct?: string | null;
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

/* ── CLASSIC GES BROADSHEET ─────────────────────────────────── */
export function ClassicSheet({ d }: { d: SheetData }) {
  const bands = gradingBands(d.curriculum);
  const ordinal = (n: number) => {
    const s = ["th", "st", "nd", "rd"], v = n % 100;
    return n + (s[(v - 20) % 10] || s[v] || s[0]);
  };
  return (
    <div className="print-sheet bg-card text-card-foreground rounded-xl border shadow-sm overflow-hidden mx-auto max-w-[820px]">
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

        {/* learning areas */}
        <table className="w-full text-[12.5px] mt-4 border border-foreground/50">
          <thead>
            <tr className="bg-foreground text-background text-left">
              <th className="px-3 py-2 font-semibold uppercase text-[10px] tracking-wider">Learning area</th>
              <th className="px-2 py-2 font-semibold uppercase text-[10px] tracking-wider text-center w-20">Total (100)</th>
              <th className="px-2 py-2 font-semibold uppercase text-[10px] tracking-wider text-center w-16">Grade</th>
              <th className="px-2 py-2 font-semibold uppercase text-[10px] tracking-wider w-24 text-center">Descriptor</th>
              <th className="px-3 py-2 font-semibold uppercase text-[10px] tracking-wider">Subject teacher&apos;s remark</th>
            </tr>
          </thead>
          <tbody>
            {d.results.map((r, i) => {
              const band = bands.bands.find((b) => r.total >= b.min) ?? bands.bands[bands.bands.length - 1];
              return (
                <tr key={i} className={i % 2 ? "bg-secondary/40" : ""}>
                  <td className="px-3 py-1.5 border-t border-foreground/20 font-medium">{r.subject}</td>
                  <td className="px-2 py-1.5 border-t border-foreground/20 text-center tabular-nums font-semibold">{r.total}</td>
                  <td className="px-2 py-1.5 border-t border-foreground/20 text-center font-bold">{r.grade}</td>
                  <td className="px-2 py-1.5 border-t border-foreground/20 text-center text-[11px]">{band.label}</td>
                  <td className="px-3 py-1.5 border-t border-foreground/20 text-muted-foreground text-[11.5px]">{r.remark}</td>
                </tr>
              );
            })}
            <tr className="bg-foreground/10 font-semibold">
              <td className="px-3 py-2 border-t-2 border-foreground/50">Overall average</td>
              <td className="px-2 py-2 border-t-2 border-foreground/50 text-center tabular-nums">{d.average ?? "—"}%</td>
              <td className="px-2 py-2 border-t-2 border-foreground/50 text-center" colSpan={3}>
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
          </div>
        </div>

        {/* conduct & remarks */}
        <div className="grid grid-cols-2 gap-4 mt-4 text-[12.5px]">
          <div>
            <div className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground">Conduct</div>
            <p className="mt-0.5">{d.conduct ?? "—"}</p>
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
  return (
    <div className="print-sheet bg-card text-card-foreground rounded-xl border shadow-sm overflow-hidden mx-auto max-w-[760px]">
      <KenteStrip />
      <div className="p-6">
        <div className="flex items-center justify-between gap-3">
          <div className="flex items-center gap-2.5">
            <LogoMark size={40} />
            <div>
              <div className="font-bold text-lg leading-tight">Terminal Report Card</div>
              <div className="text-xs text-muted-foreground">{d.termName}</div>
            </div>
          </div>
          <Badge variant={d.published ? "default" : "secondary"}>{d.published ? "PUBLISHED" : "DRAFT"}</Badge>
        </div>

        <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-4 text-sm">
          <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Learner</div><div className="font-semibold">{d.student.name}</div></div>
          <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Index</div><div className="font-mono text-xs font-semibold mt-0.5">{d.student.index ?? "—"}</div></div>
          <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Class</div><div className="font-semibold">{d.student.classroom}</div></div>
          <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Position</div><div className="font-semibold">{d.position} of {d.classSize}</div></div>
        </div>

        <table className="w-full text-sm mt-4 border rounded-lg overflow-hidden">
          <thead>
            <tr className="bg-secondary text-left">
              <th className="px-2.5 py-2 font-medium">Subject</th>
              <th className="px-2.5 py-2 font-medium text-center">Score</th>
              <th className="px-2.5 py-2 font-medium">Grade</th>
              <th className="px-2.5 py-2 font-medium hidden sm:table-cell">Remark</th>
            </tr>
          </thead>
          <tbody>
            {d.results.map((r: any, i: number) => (
              <tr key={i} className="border-t">
                <td className="px-2.5 py-2">{r.subject}</td>
                <td className="px-2.5 py-2 text-center font-semibold tabular-nums">{r.total}%</td>
                <td className="px-2.5 py-2"><Badge variant="outline">{r.grade}</Badge></td>
                <td className="px-2.5 py-2 text-muted-foreground text-xs hidden sm:table-cell">{r.remark}</td>
              </tr>
            ))}
          </tbody>
        </table>

        <div className="grid sm:grid-cols-3 gap-2 mt-4 text-sm">
          <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Average</div><div className="font-bold text-lg">{d.average}%</div></div>
          <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Conduct</div><div className="font-semibold">{d.conduct ?? "—"}</div></div>
          <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Promotion</div><div className="font-semibold">{d.promotion ?? "—"}</div></div>
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

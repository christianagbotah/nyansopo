"use client";
/* Nyansapo — Terminal marksheet print-view family.
   Legacy parity: student_marksheet_print_view{,_2,_3,_creche,_creche_2}
   plus the bulk variants, rebuilt as one React component family:

   · MarksheetClassicSheet — legacy template 1: GES letterhead, grading
     key, Class(30)/Exam(70)/Total(100) table with per-subject grade and
     position, TOTAL row, attendance + promoted-to, conducts grid,
     teacher + principal remarks, accounts (arrears) block, signatures
   · MarksheetModernSheet  — legacy template 2: modern letterhead with
     kente strip, colour-coded grade chips and a per-subject bar chart
   · MarksheetCompactSheet — legacy template 3: A5 booklet-style compact
     sheet, same doctrine at half the size
   · MarksheetCrecheSheet — legacy creche: grading abbreviations key,
     developmental domains with per-skill rating codes, attendance,
     physical development measurement grid and accounts & billing

   All are A4/A5 print-optimised: wrap in .print-area — each sheet
   carries .print-sheet so bulk printing paginates cleanly, and
   break-inside-avoid keeps a sheet from being sliced across pages
   (CI3 .page-container { page-break-inside: avoid } parity). */
import { Badge } from "@/components/ui/badge";
import { LogoMark, KenteStrip } from "@/components/brand";
import { SCALES_TABLE } from "@/lib/grading-scale";
import { cn } from "@/lib/utils";

/* ────────────────────────── payload types ────────────────────────── */

export type MarksheetSchoolInfo = {
  id: string; name: string; code: string; motto: string | null; town: string; region: string;
  district: string; gpsAddress: string | null; phone: string | null; email: string | null;
  logoColor: string; curriculum: string;
};
export type MarksheetRow = {
  subjectId: string; subjectName: string; subjectCode: string | null;
  classScore: number | null; examScore: number | null; total: number | null;
  grade: string | null; remark: string | null; position: number | null;
};
export type MarksheetPayload = {
  school: MarksheetSchoolInfo;
  term: { id: string; name: string; sequence: number; yearName: string };
  student: {
    id: string; firstName: string; lastName: string; otherNames: string | null; nyansapoIndex: string;
    gender: string; guardianName: string; guardianPhone: string; dateOfBirth?: string | null;
  };
  classroom: { id: string; name: string } | null;
  classTeacher: string | null;
  rows: MarksheetRow[];
  totals: { classScore: number; examScore: number; total: number; subjects: number };
  card: {
    id: string; totalScore: number | null; average: number | null; classPosition: number | null; classSize: number | null;
    aggregate: number | null; promoted: boolean | null; published: boolean;
    conductIds: string[] | null; interestId: string | null;
    attitude: string | null; conduct: string | null;
    teacherRemark: string | null; headRemark: string | null;
    remarkSource: string | null; headSource: string | null;
    daysOpened: number | null; daysPresent: number | null;
    attendance: string | null; attendancePct: number | null;
  } | null;
  attendance: { daysTaken: number; daysAttended: number };
  conductItems: { id: string; name: string; kind: string }[];
  teacherTemplates: { id: string; category: string; text: string }[];
  headBands: { id: string; minPercent: number | null; maxPercent: number | null; text: string }[];
  suggestedHead: string | null;
};

/* creche-ratings grid — GET /api/school/{id}/ops/academics?action=creche-ratings */
export type CrecheGridData = {
  scale: { id: string; code: string; label: string }[];
  domains: { id: string; name: string; active: boolean; skills: { id: string; name: string }[] }[];
  students: { id: string; name: string; rollNo: number }[];
  ratings: Record<string, string>;
};

/* billing summary for the accounts block (legacy "ACCOUNTS AND BILLING"):
   previous arrears = balances on invoices before this term;
   next-term bills = unpaid balances on invoices of later terms. */
export type MarksheetBilling = {
  arrearsCents: number | null;
  nextTermCents: number | null;
  lines: { invoiceNo: string; term: string; balanceCents: number; status: string }[];
};

export type MarksheetStyle = "classic" | "modern" | "compact" | "creche" | "creche2";

export const MARKSHEET_STYLES: { id: MarksheetStyle; label: string; blurb: string }[] = [
  { id: "classic", label: "Classic GES", blurb: "Legacy terminal report — grading key, boxed marks, conducts grid, arrears block" },
  { id: "modern", label: "Modern chart", blurb: "Kente-accented sheet with colour-coded grades and a per-subject bar chart" },
  { id: "compact", label: "Compact A5", blurb: "Booklet-style compact sheet — the whole term on one A5 page" },
  { id: "creche", label: "Early-years booklet", blurb: "Developmental-domain progress record — rating codes, physical development, billing" },
  { id: "creche2", label: "Early-years grid", blurb: "Template-2 checkmark grid — one column per rating code with a tick on the rated code" },
];

/* ─────────────────────────── shared helpers ─────────────────────────── */

const ordinal = (n: number) => {
  const s = ["th", "st", "nd", "rd"], v = n % 100;
  return n + (s[(v - 20) % 10] || s[v] || s[0]);
};
const fmt1 = (n: number | null | undefined) => (n === null || n === undefined ? "—" : (Math.round(n * 100) / 100).toFixed(1).replace(/\.0$/, ""));
const cedi = (cents: number | null | undefined) => (cents === null || cents === undefined ? "—" : `GH\u00A2 ${(cents / 100).toLocaleString("en-GH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
const fullName = (s: MarksheetPayload["student"]) => [s.lastName, s.firstName, s.otherNames].filter(Boolean).join(" ");

function ageWords(birthDate?: string | null, at = new Date()): string | null {
  if (!birthDate) return null;
  const dob = new Date(birthDate);
  if (Number.isNaN(dob.getTime())) return null;
  let y = at.getFullYear() - dob.getFullYear();
  let m = at.getMonth() - dob.getMonth();
  let d = at.getDate() - dob.getDate();
  if (d < 0) { m -= 1; d += new Date(at.getFullYear(), at.getMonth(), 0).getDate(); }
  if (m < 0) { y -= 1; m += 12; }
  if (y < 0) return null;
  return `${y} ${y === 1 ? "year" : "years"}, ${m} ${m === 1 ? "month" : "months"} and ${d} ${d === 1 ? "day" : "days"}`;
}
const studentAge = (s: MarksheetPayload["student"]) => ageWords(s.dateOfBirth);

function gradingBands(curriculum?: string) {
  return SCALES_TABLE[curriculum ?? "GES"] ?? SCALES_TABLE.GES;
}

/* grade-code → colour chip (report-sheets grade-a1…grade-f parity) */
const GRADE_CHIPS = [
  "bg-emerald-100 text-emerald-900",
  "bg-lime-100 text-lime-900",
  "bg-amber-100 text-amber-900",
  "bg-orange-100 text-orange-900",
  "bg-rose-100 text-rose-900",
];
function gradeChipIdx(curriculum: string | undefined, grade: string): number {
  const bands = gradingBands(curriculum).bands;
  const i = bands.findIndex((b) => b.grade === grade);
  if (i < 0) return 4;
  return Math.min(Math.floor((i / bands.length) * 5), 4);
}

/* tiny SVG subject bar chart — pure, prints without JS chart libs
   (legacy print_view_2 "Graph display" parity) */
function SubjectBars({ rows }: { rows: MarksheetRow[] }) {
  const scored = rows.filter((r) => typeof r.total === "number") as (MarksheetRow & { total: number })[];
  if (!scored.length) return null;
  const max = Math.max(100, ...scored.map((r) => r.total));
  return (
    <div className="mt-3 border border-neutral-700 p-2">
      <p className="mb-1.5 text-[10px] font-bold uppercase tracking-wide">Subject performance (out of 100)</p>
      <div className="grid gap-1">
        {scored.map((r) => (
          <div key={r.subjectId} className="grid grid-cols-[130px_1fr_36px] items-center gap-1.5">
            <span className="truncate text-[9.5px]">{r.subjectName}</span>
            <span className="h-2.5 bg-neutral-200">
              <span className="block h-2.5" style={{ width: `${Math.max(2, (r.total / max) * 100)}%`, background: "#7c3aed" }} />
            </span>
            <span className="text-right text-[9.5px] font-semibold tabular-nums">{r.total}</span>
          </div>
        ))}
      </div>
    </div>
  );
}

/* GES-style letterhead shared by classic + compact (legacy #gh / #add / #box) */
function GesLetterhead({ p, tagline }: { p: MarksheetPayload; tagline: string }) {
  return (
    <div className="border-b-2 border-black pb-2 text-center">
      <div className="flex items-center justify-center gap-3">
        <LogoMark size={46} />
        <div>
          <h1 className="text-[22px] font-bold uppercase tracking-[0.12em] leading-tight">{p.school.name}</h1>
          <p className="text-[10.5px]">
            {p.school.town ? `${p.school.town}, ` : ""}{p.school.region}{p.school.district ? ` · ${p.school.district}` : ""}
            {p.school.phone ? ` · ${p.school.phone}` : ""}
          </p>
          {p.school.motto ? <p className="text-[9.5px] italic">“{p.school.motto}”</p> : null}
          <p className="mt-0.5 text-[11px] font-semibold uppercase tracking-wide underline">Ghana Education Service</p>
        </div>
        <div className="w-14" />
      </div>
      <p className="mt-1.5 text-[13px] font-bold uppercase tracking-[0.25em]">{tagline}</p>
      <p className="text-[10.5px]">{p.term.name} · {p.term.yearName} · {p.classroom?.name ?? "—"}</p>
    </div>
  );
}

/* grading key strip (legacy grade_table) */
function GradingKey({ p }: { p: MarksheetPayload }) {
  const bands = gradingBands(p.school.curriculum).bands;
  return (
    <table className="mt-2 w-full border-collapse text-[9.5px]">
      <thead>
        <tr className="bg-neutral-200">
          <th className="border border-neutral-700 px-1 py-0.5 text-left">GRADING KEY</th>
          {bands.map((b) => <th key={b.grade} className="border border-neutral-700 px-1 py-0.5 text-center font-bold">{b.grade}</th>)}
        </tr>
      </thead>
      <tbody>
        <tr>
          <td className="border border-neutral-700 px-1 py-0.5 text-left">Marks range</td>
          {bands.map((b, i) => {
            const upper = i === 0 ? 100 : bands[i - 1].min - 1;
            return <td key={b.grade} className="border border-neutral-700 px-1 py-0.5 text-center tabular-nums">{b.min}–{upper}</td>;
          })}
        </tr>
        <tr>
          <td className="border border-neutral-700 px-1 py-0.5 text-left">Descriptor</td>
          {bands.map((b) => <td key={b.grade} className="border border-neutral-700 px-1 py-0.5 text-center">{b.label}</td>)}
        </tr>
      </tbody>
    </table>
  );
}

/* accounts block (legacy chart_account_sem arrears_tb parity) */
function AccountsBlock({ billing }: { billing?: MarksheetBilling | null }) {
  if (!billing) return null;
  const total = (billing.arrearsCents ?? 0) + (billing.nextTermCents ?? 0);
  return (
    <div className="border border-neutral-700 p-2 text-[10px]">
      <p className="mb-1 font-bold uppercase tracking-wide">Accounts &amp; billing</p>
      <table className="w-full border-collapse">
        <tbody>
          <tr>
            <td className="border border-neutral-700 px-1.5 py-0.5">Previous arrears</td>
            <td className="border border-neutral-700 px-1.5 py-0.5 text-right tabular-nums">{cedi(billing.arrearsCents)}</td>
          </tr>
          <tr>
            <td className="border border-neutral-700 px-1.5 py-0.5">Next-term bills</td>
            <td className="border border-neutral-700 px-1.5 py-0.5 text-right tabular-nums">{cedi(billing.nextTermCents)}</td>
          </tr>
          <tr className="font-bold">
            <td className="border border-neutral-700 bg-neutral-200 px-1.5 py-0.5">Outstanding total</td>
            <td className="border border-neutral-700 bg-neutral-200 px-1.5 py-0.5 text-right tabular-nums">{cedi(total)}</td>
          </tr>
        </tbody>
      </table>
      {billing.lines.length > 0 ? (
        <ul className="mt-1 space-y-0.5 text-[9px] text-neutral-600">
          {billing.lines.slice(0, 4).map((l) => (
            <li key={l.invoiceNo} className="flex justify-between gap-2">
              <span className="truncate">{l.invoiceNo} · {l.term}</span>
              <span className="tabular-nums">{cedi(l.balanceCents)}</span>
            </li>
          ))}
        </ul>
      ) : null}
    </div>
  );
}

/* ────────────── CLASSIC — legacy student_marksheet_print_view ────────────── */

export function MarksheetClassicSheet({ p, billing }: { p: MarksheetPayload; billing?: MarksheetBilling | null }) {
  const conducts = p.conductItems.filter((c) => c.kind === "CONDUCT");
  const interests = p.conductItems.filter((c) => c.kind === "INTEREST");
  const conductNames = (p.card?.conductIds ?? []).map((id) => p.conductItems.find((c) => c.id === id)?.name).filter(Boolean) as string[];
  const interestName = interests.find((i) => i.id === p.card?.interestId)?.name ?? null;
  const daysOpened = p.card?.daysOpened ?? (p.attendance.daysTaken || null);
  const daysPresent = p.card?.daysPresent ?? (p.attendance.daysAttended || null);
  const age = studentAge(p.student);
  const name = fullName(p.student);

  return (
    <div className="print-sheet break-inside-avoid bg-white text-black mx-auto max-w-[820px] p-3" style={{ fontFamily: "Georgia, 'Times New Roman', serif" }}>
      <GesLetterhead p={p} tagline="Terminal Report" />
      <GradingKey p={p} />

      {/* particulars (legacy Name of pupil / Class / Gender / Age grid) */}
      <table className="mt-2 w-full border-collapse text-[11.5px]">
        <tbody>
          <tr>
            <td className="border border-neutral-700 px-2 py-1 w-32 font-semibold">Name of pupil</td>
            <td className="border border-neutral-700 px-2 py-1">{name}</td>
            <td className="border border-neutral-700 px-2 py-1 w-20 font-semibold">Index</td>
            <td className="border border-neutral-700 px-2 py-1">{p.student.nyansapoIndex || "—"}</td>
          </tr>
          <tr>
            <td className="border border-neutral-700 px-2 py-1 font-semibold">Class</td>
            <td className="border border-neutral-700 px-2 py-1">{p.classroom?.name ?? "—"}</td>
            <td className="border border-neutral-700 px-2 py-1 font-semibold">Gender</td>
            <td className="border border-neutral-700 px-2 py-1">{p.student.gender ? p.student.gender.charAt(0).toUpperCase() + p.student.gender.slice(1).toLowerCase() : "—"}</td>
          </tr>
          <tr>
            <td className="border border-neutral-700 px-2 py-1 font-semibold">Position in class</td>
            <td className="border border-neutral-700 px-2 py-1">{p.card?.classPosition ? `${ordinal(p.card.classPosition)} of ${p.card.classSize ?? "—"}` : "—"}</td>
            <td className="border border-neutral-700 px-2 py-1 font-semibold">Age</td>
            <td className="border border-neutral-700 px-2 py-1">{age ?? "—"}</td>
          </tr>
        </tbody>
      </table>

      {/* marks table (legacy mark_print: Subject / Class score / Exam score / Total / Grade / Position) */}
      <table className="mt-2 w-full border-collapse text-[11px]">
        <thead>
          <tr className="bg-neutral-200">
            <th className="border border-neutral-700 px-1.5 py-1 w-8">S/N</th>
            <th className="border border-neutral-700 px-2 py-1 text-left">SUBJECT</th>
            <th className="border border-neutral-700 px-1.5 py-1">CLASS SCORE</th>
            <th className="border border-neutral-700 px-1.5 py-1">EXAM SCORE</th>
            <th className="border border-neutral-700 px-1.5 py-1">TOTAL SCORE</th>
            <th className="border border-neutral-700 px-1.5 py-1">GRADE</th>
            <th className="border border-neutral-700 px-1.5 py-1">POSITION</th>
          </tr>
        </thead>
        <tbody>
          {p.rows.map((r, i) => (
            <tr key={r.subjectId}>
              <td className="border border-neutral-700 px-1.5 py-0.5 text-center">{i + 1}</td>
              <td className="border border-neutral-700 px-2 py-0.5">{r.subjectName.toUpperCase()}</td>
              <td className="border border-neutral-700 px-1.5 py-0.5 text-center tabular-nums">{r.classScore ?? "—"}</td>
              <td className="border border-neutral-700 px-1.5 py-0.5 text-center tabular-nums">{r.examScore ?? "—"}</td>
              <td className="border border-neutral-700 px-1.5 py-0.5 text-center tabular-nums font-semibold">{r.total ?? "—"}</td>
              <td className="border border-neutral-700 px-1.5 py-0.5 text-center">{r.grade ?? "—"}</td>
              <td className="border border-neutral-700 px-1.5 py-0.5 text-center">{r.position ? ordinal(r.position) : "—"}</td>
            </tr>
          ))}
          <tr className="font-bold">
            <td className="border border-neutral-700 px-1.5 py-1 text-center" colSpan={2}>TOTAL</td>
            <td className="border border-neutral-700 px-1.5 py-1 text-center tabular-nums">{p.totals.classScore || "—"}</td>
            <td className="border border-neutral-700 px-1.5 py-1 text-center tabular-nums">{p.totals.examScore || "—"}</td>
            <td className="border border-neutral-700 px-1.5 py-1 text-center tabular-nums">{p.totals.total || "—"}</td>
            <td className="border border-neutral-700 px-1.5 py-1 text-center" colSpan={2}>
              Avg {fmt1(p.card?.average)}%{p.card?.aggregate !== null && p.card?.aggregate !== undefined ? ` · Aggregate ${fmt1(p.card.aggregate)}` : ""}
            </td>
          </tr>
        </tbody>
      </table>

      {/* attendance + promotion (legacy Attendance / Out of + Promoted to) */}
      <div className="mt-2 grid grid-cols-2 gap-3 text-[11px]">
        <table className="border-collapse">
          <tbody>
            <tr>
              <td className="border border-neutral-700 px-2 py-1">Attendance</td>
              <td className="border border-neutral-700 px-2 py-1 text-center font-bold">{daysPresent ?? "—"}</td>
              <td className="border border-neutral-700 px-2 py-1">out of</td>
              <td className="border border-neutral-700 px-2 py-1 text-center font-bold">{daysOpened ?? "—"}</td>
            </tr>
            <tr>
              <td className="border border-neutral-700 px-2 py-1">Interest shown</td>
              <td className="border border-neutral-700 px-2 py-1 text-center font-bold" colSpan={3}>{interestName ?? "—"}</td>
            </tr>
            {p.card?.attitude ? (
              <tr>
                <td className="border border-neutral-700 px-2 py-1">Attitude</td>
                <td className="border border-neutral-700 px-2 py-1 text-center font-bold" colSpan={3}>{p.card.attitude}</td>
              </tr>
            ) : null}
            {p.card?.conduct ? (
              <tr>
                <td className="border border-neutral-700 px-2 py-1">Conduct</td>
                <td className="border border-neutral-700 px-2 py-1 text-center font-bold" colSpan={3}>{p.card.conduct}</td>
              </tr>
            ) : null}
          </tbody>
        </table>
        <table className="border-collapse">
          <tbody>
            <tr>
              <td className="border border-neutral-700 px-2 py-1">Promotion</td>
              <td className="border border-neutral-700 px-2 py-1 text-center font-bold" colSpan={3}>
                {p.card?.promoted === true ? "Promoted" : p.card?.promoted === false ? "To be repeated" : "Pending"}
              </td>
            </tr>
            <tr>
              <td className="border border-neutral-700 px-2 py-1">Class teacher</td>
              <td className="border border-neutral-700 px-2 py-1 text-center" colSpan={3}>{p.classTeacher ?? "—"}</td>
            </tr>
          </tbody>
        </table>
      </div>

      {/* conducts grid (legacy c1..c15 checkbox grid) */}
      <div className="mt-2">
        <p className="text-[11px] font-semibold italic underline">Conducts of {name}</p>
        <div className="mt-1 border border-neutral-700 p-2">
          {conductNames.length ? (
            <ul className="grid gap-x-3 gap-y-1 text-[10.5px]" style={{ gridTemplateColumns: "repeat(auto-fill, minmax(150px, 1fr))" }}>
              {conductNames.map((n) => <li key={n}>&#10003; {n}</li>)}
            </ul>
          ) : <p className="text-[10.5px] text-neutral-600">No conduct traits recorded for this learner.</p>}
          {conducts.length - conductNames.length > 0 ? (
            <p className="mt-1 text-[9.5px] text-neutral-600">{conducts.length - conductNames.length} of {conducts.length} school traits not ticked.</p>
          ) : null}
        </div>
      </div>

      {/* remarks (legacy Class Teacher's Remark / Principal's Remark) */}
      <table className="mt-2 w-full border-collapse text-[11px]">
        <tbody>
          <tr>
            <td className="w-44 border border-neutral-700 bg-neutral-200 px-2 py-1 font-semibold">CLASS TEACHER&apos;S REMARKS</td>
            <td className="border border-neutral-700 px-2 py-1"><em><strong>{p.card?.teacherRemark ?? "—"}</strong></em></td>
          </tr>
          <tr>
            <td className="border border-neutral-700 bg-neutral-200 px-2 py-1 font-semibold">PRINCIPAL&apos;S REMARKS</td>
            <td className="border border-neutral-700 px-2 py-1"><em><strong>{p.card?.headRemark ?? "—"}</strong></em></td>
          </tr>
        </tbody>
      </table>

      {/* accounts block (legacy arrears_tb) */}
      {billing ? <div className="mt-2"><AccountsBlock billing={billing} /></div> : null}

      {/* signatures (legacy Class Teacher / Principal / Parent) */}
      <div className="mt-5 grid grid-cols-3 gap-4 text-[10px] text-neutral-700">
        <div className="border-t border-neutral-700 pt-1 text-center">Class Teacher{p.classTeacher ? ` (${p.classTeacher})` : ""}</div>
        <div className="border-t border-neutral-700 pt-1 text-center">Head Teacher</div>
        <div className="border-t border-neutral-700 pt-1 text-center">Parent / Guardian</div>
      </div>
      <p className="mt-3 text-center text-[9px] text-neutral-500">
        Generated {new Date().toLocaleDateString("en-GB", { day: "numeric", month: "long", year: "numeric" })} · {p.school.name}{p.school.code ? ` (${p.school.code})` : ""} · Nyansapo School Management System
      </p>
    </div>
  );
}

/* ────────────── MODERN — legacy student_marksheet_print_view_2 ────────────── */

/* KPI stat tile for the modern sheet — module-level so it never gets
   re-created during render (react-hooks/static-components) */
function KpiTile({ label, value, tone }: { label: string; value: string; tone?: string }) {
  return (
    <div className="flex-1 rounded-lg border border-neutral-300 bg-neutral-50 px-3 py-2 text-center">
      <p className="text-[9px] font-bold uppercase tracking-wider text-neutral-500">{label}</p>
      <p className={cn("text-lg font-bold leading-tight tabular-nums", tone)}>{value}</p>
    </div>
  );
}

export function MarksheetModernSheet({ p, billing }: { p: MarksheetPayload; billing?: MarksheetBilling | null }) {
  const conductNames = (p.card?.conductIds ?? []).map((id) => p.conductItems.find((c) => c.id === id)?.name).filter(Boolean) as string[];
  const interestName = p.conductItems.find((c) => c.kind === "INTEREST" && c.id === p.card?.interestId)?.name ?? null;
  const daysOpened = p.card?.daysOpened ?? (p.attendance.daysTaken || null);
  const daysPresent = p.card?.daysPresent ?? (p.attendance.daysAttended || null);
  const attendPct = daysOpened ? Math.round(((daysPresent ?? 0) / daysOpened) * 100) : null;
  const name = fullName(p.student);

  return (
    <div className="print-sheet break-inside-avoid mx-auto max-w-[820px] overflow-hidden rounded-xl border border-neutral-300 bg-white text-black">
      <KenteStrip className="h-1.5 w-full" />
      {/* letterhead */}
      <div className="flex items-center gap-4 px-6 pt-4">
        <LogoMark size={44} />
        <div className="flex-1">
          <h1 className="text-lg font-bold uppercase tracking-wide leading-tight">{p.school.name}</h1>
          <p className="text-[10px] text-neutral-600">{[p.school.town, p.school.region].filter(Boolean).join(", ")}{p.school.phone ? ` · ${p.school.phone}` : ""}</p>
        </div>
        <div className="text-right">
          <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-neutral-500">Terminal Marksheet</p>
          <p className="text-[11px] font-semibold">{p.term.name} · {p.term.yearName}</p>
        </div>
      </div>
      <div className="mx-6 mt-2 border-b border-neutral-200 pb-2" />

      {/* particulars chips */}
      <div className="flex flex-wrap items-center gap-x-4 gap-y-1 px-6 pt-3 text-[11.5px]">
        <span className="font-semibold">{name}</span>
        {p.student.nyansapoIndex ? <Badge variant="outline" className="font-mono text-[10px]">{p.student.nyansapoIndex}</Badge> : null}
        <Badge variant="secondary" className="text-[10px]">{p.classroom?.name ?? "—"}</Badge>
        {p.student.gender ? <Badge variant="outline" className="text-[10px]">{p.student.gender}</Badge> : null}
        {p.card?.classPosition ? <Badge className="text-[10px]">{ordinal(p.card.classPosition)} of {p.card.classSize ?? "—"}</Badge> : null}
        {p.card?.promoted === true ? <Badge className="bg-emerald-600 text-[10px]">Promoted</Badge> : p.card?.promoted === false ? <Badge className="bg-rose-600 text-[10px]">To be repeated</Badge> : null}
      </div>

      {/* KPI strip */}
      <div className="mt-3 flex gap-2 px-6">
        <KpiTile label="Total score" value={`${fmt1(p.card?.totalScore)}`} />
        <KpiTile label="Average" value={p.card?.average !== null && p.card?.average !== undefined ? `${fmt1(p.card?.average)}%` : "—"} tone="text-emerald-700" />
        <KpiTile label="Aggregate" value={p.card?.aggregate !== null && p.card?.aggregate !== undefined ? fmt1(p.card.aggregate) : "—"} />
        <KpiTile label="Attendance" value={attendPct !== null ? `${attendPct}%` : "—"} tone={attendPct !== null && attendPct < 80 ? "text-rose-600" : undefined} />
      </div>

      {/* marks table with colour-coded grade chips */}
      <div className="mt-3 px-6">
        <table className="w-full border-collapse text-[11.5px]">
          <thead>
            <tr className="bg-neutral-800 text-white">
              <th className="px-2 py-1.5 text-left font-semibold">Subject</th>
              <th className="px-2 py-1.5 text-right font-semibold">Class (30)</th>
              <th className="px-2 py-1.5 text-right font-semibold">Exam (70)</th>
              <th className="px-2 py-1.5 text-right font-semibold">Total</th>
              <th className="px-2 py-1.5 text-center font-semibold">Grade</th>
              <th className="px-2 py-1.5 text-center font-semibold">Position</th>
            </tr>
          </thead>
          <tbody>
            {p.rows.map((r) => (
              <tr key={r.subjectId} className="border-b border-neutral-200">
                <td className="px-2 py-1">{r.subjectName}</td>
                <td className="px-2 py-1 text-right tabular-nums text-neutral-700">{r.classScore ?? "—"}</td>
                <td className="px-2 py-1 text-right tabular-nums text-neutral-700">{r.examScore ?? "—"}</td>
                <td className="px-2 py-1 text-right tabular-nums font-semibold">{r.total ?? "—"}</td>
                <td className="px-2 py-1 text-center">
                  {r.grade ? <span className={cn("inline-block rounded px-1.5 py-0.5 text-[10px] font-bold", GRADE_CHIPS[gradeChipIdx(p.school.curriculum, r.grade)])}>{r.grade}</span> : "—"}
                </td>
                <td className="px-2 py-1 text-center tabular-nums">{r.position ? ordinal(r.position) : "—"}</td>
              </tr>
            ))}
            <tr className="bg-neutral-100 font-bold">
              <td className="px-2 py-1.5">TOTAL</td>
              <td className="px-2 py-1.5 text-right tabular-nums">{p.totals.classScore || "—"}</td>
              <td className="px-2 py-1.5 text-right tabular-nums">{p.totals.examScore || "—"}</td>
              <td className="px-2 py-1.5 text-right tabular-nums">{p.totals.total || "—"}</td>
              <td className="px-2 py-1.5 text-center" colSpan={2}>Avg {fmt1(p.card?.average)}%</td>
            </tr>
          </tbody>
        </table>
      </div>

      {/* chart + conduct side by side (legacy graph view) */}
      <div className="mt-3 grid grid-cols-[1fr_240px] gap-4 px-6">
        <SubjectBars rows={p.rows} />
        <div className="grid gap-2 text-[10px]">
          <div className="rounded-lg border border-neutral-300 p-2">
            <p className="mb-1 font-bold uppercase tracking-wide">Conduct</p>
            {conductNames.length ? (
              <ul className="grid gap-0.5">
                {conductNames.map((n) => <li key={n}>&#10003; {n}</li>)}
              </ul>
            ) : <p className="text-neutral-500">—</p>}
            {interestName ? <p className="mt-1"><span className="font-semibold">Interest:</span> {interestName}</p> : null}
          </div>
          <div className="rounded-lg border border-neutral-300 p-2">
            <p className="mb-1 font-bold uppercase tracking-wide">Attendance</p>
            <p>{daysPresent ?? "—"} of {daysOpened ?? "—"} days present</p>
            {billing ? <p className="mt-1"><span className="font-semibold">Arrears:</span> {cedi(billing.arrearsCents ?? 0)}</p> : null}
          </div>
        </div>
      </div>

      {/* remarks */}
      <div className="mt-3 grid grid-cols-2 gap-4 px-6 text-[11px]">
        <div className="rounded-lg bg-neutral-50 p-3">
          <p className="text-[9px] font-bold uppercase tracking-wider text-neutral-500">Class teacher&apos;s remarks</p>
          <p className="mt-0.5">{p.card?.teacherRemark ?? "—"}</p>
        </div>
        <div className="rounded-lg bg-neutral-50 p-3">
          <p className="text-[9px] font-bold uppercase tracking-wider text-neutral-500">Head teacher&apos;s remarks</p>
          <p className="mt-0.5">{p.card?.headRemark ?? "—"}</p>
        </div>
      </div>

      {/* signatures */}
      <div className="mt-4 grid grid-cols-3 gap-4 px-6 pb-4 text-[10px] text-neutral-600">
        <div className="border-t border-neutral-400 pt-1 text-center">Class Teacher</div>
        <div className="border-t border-neutral-400 pt-1 text-center">Head Teacher</div>
        <div className="border-t border-neutral-400 pt-1 text-center">Parent / Guardian</div>
      </div>
    </div>
  );
}

/* ───────────── COMPACT — legacy student_marksheet_print_view_3 (A5) ───────────── */

export function MarksheetCompactSheet({ p }: { p: MarksheetPayload }) {
  const conductNames = (p.card?.conductIds ?? []).map((id) => p.conductItems.find((c) => c.id === id)?.name).filter(Boolean) as string[];
  const daysOpened = p.card?.daysOpened ?? (p.attendance.daysTaken || null);
  const daysPresent = p.card?.daysPresent ?? (p.attendance.daysAttended || null);
  const name = fullName(p.student);
  const bands = gradingBands(p.school.curriculum).bands;

  return (
    <div className="print-sheet break-inside-avoid mx-auto max-w-[560px] bg-white p-4 text-black" style={{ fontFamily: "Georgia, 'Times New Roman', serif", fontSize: "11px" }}>
      <div className="border-b border-black pb-1 text-center">
        <h1 className="text-[15px] font-bold uppercase tracking-wide">{p.school.name}</h1>
        <p className="text-[9px]">{[p.school.town, p.school.region].filter(Boolean).join(", ")}</p>
        <p className="text-[9.5px] font-bold uppercase tracking-[0.2em]">Terminal Marksheet</p>
        <p className="text-[9px]">{p.term.name} · {p.term.yearName} · {p.classroom?.name ?? "—"}</p>
      </div>

      <table className="mt-1.5 w-full border-collapse text-[10px]">
        <tbody>
          <tr>
            <td className="border border-neutral-700 px-1 py-0.5 w-20 font-semibold">Name</td>
            <td className="border border-neutral-700 px-1 py-0.5">{name}</td>
            <td className="border border-neutral-700 px-1 py-0.5 w-14 font-semibold">Index</td>
            <td className="border border-neutral-700 px-1 py-0.5">{p.student.nyansapoIndex || "—"}</td>
          </tr>
          <tr>
            <td className="border border-neutral-700 px-1 py-0.5 font-semibold">Position</td>
            <td className="border border-neutral-700 px-1 py-0.5">{p.card?.classPosition ? `${ordinal(p.card.classPosition)} of ${p.card.classSize ?? "—"}` : "—"}</td>
            <td className="border border-neutral-700 px-1 py-0.5 font-semibold">Average</td>
            <td className="border border-neutral-700 px-1 py-0.5 tabular-nums">{p.card?.average !== null && p.card?.average !== undefined ? `${fmt1(p.card.average)}%` : "—"}</td>
          </tr>
        </tbody>
      </table>

      <table className="mt-1.5 w-full border-collapse text-[10px]">
        <thead>
          <tr className="bg-neutral-200">
            <th className="border border-neutral-700 px-1 py-0.5 text-left">SUBJECT</th>
            <th className="border border-neutral-700 px-1 py-0.5">CLASS</th>
            <th className="border border-neutral-700 px-1 py-0.5">EXAM</th>
            <th className="border border-neutral-700 px-1 py-0.5">TOTAL</th>
            <th className="border border-neutral-700 px-1 py-0.5">GRADE</th>
            <th className="border border-neutral-700 px-1 py-0.5">POS.</th>
          </tr>
        </thead>
        <tbody>
          {p.rows.map((r) => (
            <tr key={r.subjectId}>
              <td className="border border-neutral-700 px-1 py-0.5">{r.subjectName.toUpperCase()}</td>
              <td className="border border-neutral-700 px-1 py-0.5 text-center tabular-nums">{r.classScore ?? "—"}</td>
              <td className="border border-neutral-700 px-1 py-0.5 text-center tabular-nums">{r.examScore ?? "—"}</td>
              <td className="border border-neutral-700 px-1 py-0.5 text-center tabular-nums font-semibold">{r.total ?? "—"}</td>
              <td className="border border-neutral-700 px-1 py-0.5 text-center">{r.grade ?? "—"}</td>
              <td className="border border-neutral-700 px-1 py-0.5 text-center">{r.position ? ordinal(r.position) : "—"}</td>
            </tr>
          ))}
          <tr className="font-bold">
            <td className="border border-neutral-700 px-1 py-0.5">TOTAL</td>
            <td className="border border-neutral-700 px-1 py-0.5 text-center tabular-nums">{p.totals.classScore || "—"}</td>
            <td className="border border-neutral-700 px-1 py-0.5 text-center tabular-nums">{p.totals.examScore || "—"}</td>
            <td className="border border-neutral-700 px-1 py-0.5 text-center tabular-nums">{p.totals.total || "—"}</td>
            <td className="border border-neutral-700 px-1 py-0.5 text-center" colSpan={2}>
              {p.card?.aggregate !== null && p.card?.aggregate !== undefined ? `Agg. ${fmt1(p.card.aggregate)}` : `${bands.length} -band scale`}
            </td>
          </tr>
        </tbody>
      </table>

      <div className="mt-1.5 grid grid-cols-3 gap-2 text-[9.5px]">
        <div className="border border-neutral-700 p-1.5">
          <p className="font-bold uppercase">Attendance</p>
          <p>{daysPresent ?? "—"} / {daysOpened ?? "—"} days</p>
        </div>
        <div className="border border-neutral-700 p-1.5">
          <p className="font-bold uppercase">Promotion</p>
          <p>{p.card?.promoted === true ? "Promoted" : p.card?.promoted === false ? "To be repeated" : "Pending"}</p>
        </div>
        <div className="border border-neutral-700 p-1.5">
          <p className="font-bold uppercase">Conduct</p>
          <p>{conductNames.length ? conductNames.slice(0, 3).join(", ") : "—"}</p>
        </div>
      </div>

      <table className="mt-1.5 w-full border-collapse text-[10px]">
        <tbody>
          <tr>
            <td className="w-28 border border-neutral-700 bg-neutral-200 px-1 py-0.5 font-semibold">TEACHER&apos;S REMARKS</td>
            <td className="border border-neutral-700 px-1 py-0.5">{p.card?.teacherRemark ?? "—"}</td>
          </tr>
          <tr>
            <td className="border border-neutral-700 bg-neutral-200 px-1 py-0.5 font-semibold">HEAD&apos;S REMARKS</td>
            <td className="border border-neutral-700 px-1 py-0.5">{p.card?.headRemark ?? "—"}</td>
          </tr>
        </tbody>
      </table>

      <div className="mt-3 grid grid-cols-2 gap-4 text-[9px] text-neutral-700">
        <div className="border-t border-neutral-700 pt-0.5 text-center">Class Teacher</div>
        <div className="border-t border-neutral-700 pt-0.5 text-center">Parent / Guardian</div>
      </div>
      <p className="mt-1.5 text-center text-[8px] text-neutral-500">{p.school.name} · {p.term.name} {p.term.yearName} · Nyansapo SMS</p>
    </div>
  );
}

/* ──────────── EARLY-YEARS — legacy student_marksheet_print_view_creche ──────────── */

export function MarksheetCrecheSheet({ p, grid, billing }: { p: MarksheetPayload; grid: CrecheGridData | null; billing?: MarksheetBilling | null }) {
  const name = fullName(p.student);
  const age = studentAge(p.student);
  const domains = (grid?.domains ?? []).filter((d) => d.active);
  const scale = grid?.scale ?? [];
  const domRow = (domName: string) => p.rows.find((r) => r.subjectName === domName);
  const daysOpened = p.card?.daysOpened ?? (p.attendance.daysTaken || null);
  const daysPresent = p.card?.daysPresent ?? (p.attendance.daysAttended || null);

  return (
    <div className="print-sheet break-inside-avoid mx-auto max-w-[560px] bg-white p-4 text-black" style={{ fontFamily: "Georgia, 'Times New Roman', serif", fontSize: "11.5px" }}>
      {/* letterhead (legacy creche: school + GES + PLAY GROUP ASSESSMENT) */}
      <div className="border-b-2 border-black pb-1.5 text-center">
        <h1 className="text-[17px] font-bold uppercase tracking-[0.1em] leading-tight">{p.school.name}</h1>
        <p className="text-[9.5px]">{[p.school.town, p.school.region].filter(Boolean).join(", ")}{p.school.phone ? ` · ${p.school.phone}` : ""}</p>
        <p className="text-[10px] font-semibold uppercase tracking-wide underline">Ghana Education Service</p>
        <p className="mt-1 text-[12px] font-bold uppercase tracking-[0.15em]">Terminal Report</p>
        <p className="text-[9.5px]">Early-Years Progress Record · {p.term.name} · {p.term.yearName}</p>
      </div>

      {/* particulars incl. computed age (legacy dob date_diff parity) */}
      <table className="mt-2 w-full border-collapse text-[10.5px]">
        <tbody>
          <tr>
            <td className="border border-neutral-700 px-1.5 py-0.5 w-24 font-semibold">Name of pupil</td>
            <td className="border border-neutral-700 px-1.5 py-0.5" colSpan={3}>{name}</td>
          </tr>
          <tr>
            <td className="border border-neutral-700 px-1.5 py-0.5 font-semibold">Class</td>
            <td className="border border-neutral-700 px-1.5 py-0.5">{p.classroom?.name ?? "—"}</td>
            <td className="border border-neutral-700 px-1.5 py-0.5 w-14 font-semibold">Gender</td>
            <td className="border border-neutral-700 px-1.5 py-0.5">{p.student.gender ? p.student.gender.charAt(0).toUpperCase() + p.student.gender.slice(1).toLowerCase() : "—"}</td>
          </tr>
          <tr>
            <td className="border border-neutral-700 px-1.5 py-0.5 font-semibold">Age</td>
            <td className="border border-neutral-700 px-1.5 py-0.5">{age ?? "—"}</td>
            <td className="border border-neutral-700 px-1.5 py-0.5 font-semibold">Index</td>
            <td className="border border-neutral-700 px-1.5 py-0.5">{p.student.nyansapoIndex || "—"}</td>
          </tr>
        </tbody>
      </table>

      {/* grading system (legacy grade_creche abbrev → meaning) */}
      {scale.length > 0 ? (
        <table className="mt-2 w-full border-collapse text-[10px]">
          <thead>
            <tr className="bg-neutral-200">
              <th className="border border-neutral-700 px-1.5 py-0.5 text-left" colSpan={Math.min(4, scale.length) || 1}>GRADING SYSTEM</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              {scale.map((s) => (
                <td key={s.code} className="border border-neutral-700 px-1.5 py-0.5"><span className="font-bold">{s.code}</span> — {s.label}</td>
              ))}
            </tr>
          </tbody>
        </table>
      ) : null}

      {/* developmental domains (legacy subject_category_creche + subject_creche with GRADING letters) */}
      {domains.map((dom) => {
        const row = domRow(dom.name);
        return (
          <table key={dom.id} className="mt-2 w-full border-collapse text-[10.5px]">
            <thead>
              <tr className="bg-neutral-200">
                <th className="border border-neutral-700 px-1.5 py-0.5 text-left">{dom.name}</th>
                <th className="border border-neutral-700 px-1.5 py-0.5 w-24">GRADING</th>
              </tr>
            </thead>
            <tbody>
              {dom.skills.map((sk) => (
                <tr key={sk.id}>
                  <td className="border border-neutral-700 px-1.5 py-0.5">{sk.name}</td>
                  <td className="border border-neutral-700 px-1.5 py-0.5 text-center font-bold tracking-[0.3em]">
                    {grid?.ratings?.[`${p.student.id}_${sk.id}`] ?? "—"}
                  </td>
                </tr>
              ))}
              {row?.grade ? (
                <tr className="font-semibold">
                  <td className="border border-neutral-700 bg-neutral-100 px-1.5 py-0.5">Domain summary — dominant rating</td>
                  <td className="border border-neutral-700 bg-neutral-100 px-1.5 py-0.5 text-center">{row.grade}</td>
                </tr>
              ) : null}
              {row?.remark ? (
                <tr>
                  <td className="border border-neutral-700 px-1.5 py-0.5 italic" colSpan={2}>{row.remark}</td>
                </tr>
              ) : null}
            </tbody>
          </table>
        );
      })}

      {/* attendance (legacy Record of Attendance per trimester) */}
      <table className="mt-2 w-full border-collapse text-[10.5px]">
        <caption className="text-left text-[11px] font-bold underline">Record of attendance</caption>
        <tbody>
          <tr>
            <td className="border border-neutral-700 px-1.5 py-0.5">Days present</td>
            <td className="border border-neutral-700 px-1.5 py-0.5 text-center font-bold">{daysPresent ?? "—"}</td>
            <td className="border border-neutral-700 px-1.5 py-0.5">out of</td>
            <td className="border border-neutral-700 px-1.5 py-0.5 text-center font-bold">{daysOpened ?? "—"}</td>
          </tr>
        </tbody>
      </table>

      {/* physical development (legacy blank measurement grid) */}
      <table className="mt-2 w-full border-collapse text-[10.5px]">
        <caption className="text-left text-[11px] font-bold underline">Pupil&apos;s physical development</caption>
        <tbody>
          {["Date", "Height (cm)", "Weight (kg)"].map((label) => (
            <tr key={label}>
              <td className="border border-neutral-700 px-1.5 py-0.5 w-28 font-semibold">{label}</td>
              {[1, 2, 3].map((i) => <td key={i} className="border border-neutral-700 px-1.5 py-1.5" />)}
            </tr>
          ))}
        </tbody>
      </table>

      {/* remarks */}
      <table className="mt-2 w-full border-collapse text-[10.5px]">
        <tbody>
          <tr>
            <td className="w-28 border border-neutral-700 bg-neutral-200 px-1.5 py-0.5 font-semibold">TEACHER&apos;S REMARKS</td>
            <td className="border border-neutral-700 px-1.5 py-0.5"><em>{p.card?.teacherRemark ?? "—"}</em></td>
          </tr>
          <tr>
            <td className="border border-neutral-700 bg-neutral-200 px-1.5 py-0.5 font-semibold">HEAD&apos;S REMARKS</td>
            <td className="border border-neutral-700 px-1.5 py-0.5"><em>{p.card?.headRemark ?? "—"}</em></td>
          </tr>
        </tbody>
      </table>

      {/* accounts and billing (legacy ACCOUNTS AND BILLING block) */}
      {billing ? <div className="mt-2"><AccountsBlock billing={billing} /></div> : null}

      {/* signatures */}
      <div className="mt-4 grid grid-cols-2 gap-4 text-[9.5px] text-neutral-700">
        <div className="border-t border-neutral-700 pt-0.5 text-center">Class Teacher</div>
        <div className="border-t border-neutral-700 pt-0.5 text-center">Parent / Guardian</div>
      </div>
      <p className="mt-1.5 text-center text-[8.5px] text-neutral-500">{p.school.name} · {p.term.name} {p.term.yearName} · Nyansapo SMS</p>
    </div>
  );
}

/* ────────────── CRECHE TEMPLATE 2 — checkmark grid ──────────────
   Legacy student_marksheet_bulk_print_view_creche_template2 parity:
   one column per rating-scale code; each skill row carries a single ✓
   in the column matching the learner's rating. Right rail: term box,
   rating-scale legend, attendance, promotion + facilitator signature. */
export function MarksheetCrecheGridSheet({ p, grid, billing }: { p: MarksheetPayload; grid: CrecheGridData | null; billing?: MarksheetBilling | null }) {
  const name = fullName(p.student);
  const domains = (grid?.domains ?? []).filter((d) => d.active);
  const scale = grid?.scale ?? [];
  const rating = (skId: string) => grid?.ratings?.[`${p.student.id}_${skId}`] ?? null;
  const daysOpened = p.card?.daysOpened ?? (p.attendance.daysTaken || null);
  const daysPresent = p.card?.daysPresent ?? (p.attendance.daysAttended || null);

  return (
    <div className="print-sheet break-inside-avoid mx-auto max-w-[820px] bg-white p-4 text-black" style={{ fontFamily: "Georgia, 'Times New Roman', serif", fontSize: "11px" }}>
      {/* letterhead (legacy template2: logo + school + TERMINAL REPORT) */}
      <div className="border-b-2 border-black pb-2 text-center">
        <div className="flex items-center justify-center gap-3">
          <LogoMark size={44} />
          <div>
            <h1 className="text-[20px] font-bold uppercase tracking-[0.1em] leading-tight">{p.school.name}</h1>
            <p className="text-[10px]">{[p.school.town, p.school.region].filter(Boolean).join(", ")}{p.school.phone ? ` · ${p.school.phone}` : ""}</p>
            <p className="mt-0.5 text-[10px] font-semibold uppercase tracking-wide underline">Ghana Education Service</p>
          </div>
          <div className="w-14" />
        </div>
        <p className="mt-1.5 text-[13px] font-bold uppercase tracking-[0.25em]">Terminal Report</p>
        <p className="text-[10px]">Early-Years Progress Record · {p.term.name} · {p.term.yearName}</p>
      </div>

      {/* two-column body (legacy template2: left skills grid + right rail) */}
      <div className="mt-2 grid grid-cols-1 md:grid-cols-[1fr_230px] gap-3">
        <div>
          <table className="w-full border-collapse text-[10.5px]">
            <tbody>
              <tr>
                <td className="border border-neutral-700 px-1.5 py-0.5 w-20 font-semibold">Name</td>
                <td className="border border-neutral-700 px-1.5 py-0.5">{name}</td>
                <td className="border border-neutral-700 px-1.5 py-0.5 w-14 font-semibold">Class</td>
                <td className="border border-neutral-700 px-1.5 py-0.5">{p.classroom?.name ?? "—"}</td>
              </tr>
            </tbody>
          </table>

          {/* per-domain checkmark grid — one column per rating code, ✓ on the rated code */}
          {domains.map((dom) => (
            <table key={dom.id} className="mt-2 w-full border-collapse text-[10px]">
              <thead>
                <tr className="bg-neutral-200">
                  <th className="border border-neutral-700 px-1.5 py-0.5 text-left">{dom.name}</th>
                  {scale.map((s) => (
                    <th key={s.code} className="border border-neutral-700 px-1 py-0.5 text-center w-10" title={s.label}>{s.code}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {dom.skills.map((sk, si) => {
                  const code = rating(sk.id);
                  return (
                    <tr key={sk.id}>
                      <td className="border border-neutral-700 px-1.5 py-0.5">{si + 1}. {sk.name}</td>
                      {scale.map((s) => (
                        <td key={s.code} className="border border-neutral-700 px-1 py-0.5 text-center">
                          {code === s.code ? <span className="font-bold text-emerald-700">&#10003;</span> : null}
                        </td>
                      ))}
                    </tr>
                  );
                })}
              </tbody>
            </table>
          ))}
          {!domains.length && (
            <p className="mt-2 text-[10px] text-neutral-600">No early-years domains configured — add them in Settings → Academics.</p>
          )}
        </div>

        {/* right rail (legacy template2: term box, rating scale legend, attendance, facilitator) */}
        <div>
          <table className="w-full border-collapse text-[10px]">
            <tbody>
              <tr className="bg-neutral-200 font-semibold">
                <td className="border border-neutral-700 px-1.5 py-0.5">TERM</td>
                <td className="border border-neutral-700 px-1.5 py-0.5 text-center">{p.term.sequence}</td>
              </tr>
              <tr>
                <td className="border border-neutral-700 px-1.5 py-0.5 font-semibold">YEAR</td>
                <td className="border border-neutral-700 px-1.5 py-0.5 text-center">{p.term.yearName}</td>
              </tr>
            </tbody>
          </table>
          {scale.length ? (
            <>
              <p className="mt-2 text-[10px] font-bold uppercase tracking-wide">Rating scale</p>
              <table className="w-full border-collapse text-[9.5px]">
                <tbody>
                  {scale.map((s) => (
                    <tr key={s.code}>
                      <td className="border border-neutral-700 px-1.5 py-0.5 w-8 text-center font-bold">{s.code}</td>
                      <td className="border border-neutral-700 px-1.5 py-0.5">{s.label}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
              <p className="mt-1 text-[9px] text-neutral-600">Learners are assessed based on the rating scale described above.</p>
            </>
          ) : null}
          <table className="mt-2 w-full border-collapse text-[10px]">
            <tbody>
              <tr>
                <td className="border border-neutral-700 px-1.5 py-0.5 font-semibold">ATTENDANCE</td>
                <td className="border border-neutral-700 px-1.5 py-0.5 text-center font-bold">{daysPresent ?? "—"} out of {daysOpened ?? "—"}</td>
              </tr>
              <tr>
                <td className="border border-neutral-700 px-1.5 py-0.5 font-semibold">PROMOTED TO</td>
                <td className="border border-neutral-700 px-1.5 py-0.5 text-center">{p.card?.promoted === true ? "next level" : p.card?.promoted === false ? "repeat level" : "—"}</td>
              </tr>
            </tbody>
          </table>
          <p className="mt-2 text-[10px] font-semibold">Facilitator: <span className="font-normal">{p.classTeacher ?? "—"}</span></p>
          <div className="mt-6 border-t border-neutral-700 pt-0.5 text-center text-[9px] text-neutral-600">Facilitator&apos;s signature</div>
        </div>
      </div>

      {/* general remarks + head signature (legacy template2 bottom) */}
      <table className="mt-2 w-full border-collapse text-[10px]">
        <tbody>
          <tr>
            <td className="w-28 border border-neutral-700 bg-neutral-200 px-1.5 py-0.5 font-semibold">GENERAL REMARKS</td>
            <td className="border border-neutral-700 px-1.5 py-1"><em>{p.card?.teacherRemark ?? ""}</em></td>
          </tr>
        </tbody>
      </table>
      {billing ? <div className="mt-2"><AccountsBlock billing={billing} /></div> : null}
      <div className="mt-4 flex items-end justify-between text-[9.5px] text-neutral-700">
        <span>Head Teacher&apos;s name: ..............................</span>
        <span className="border-t border-neutral-700 pt-0.5 px-8 text-center">Head Teacher&apos;s signature</span>
      </div>
      <p className="mt-1.5 text-center text-[8.5px] text-neutral-500">{p.school.name} · {p.term.name} {p.term.yearName} · Nyansapo School Management System</p>
    </div>
  );
}

/* ────────────── dispatcher — one entry point for every template ────────────── */

export function MarksheetSheet({
  payload, style, billing, grid,
}: {
  payload: MarksheetPayload;
  style: MarksheetStyle;
  billing?: MarksheetBilling | null;
  grid?: CrecheGridData | null;
}) {
  if (style === "creche") return <MarksheetCrecheSheet p={payload} grid={grid ?? null} billing={billing} />;
  if (style === "creche2") return <MarksheetCrecheGridSheet p={payload} grid={grid ?? null} billing={billing} />;
  if (style === "modern") return <MarksheetModernSheet p={payload} billing={billing} />;
  if (style === "compact") return <MarksheetCompactSheet p={payload} />;
  return <MarksheetClassicSheet p={payload} billing={billing} />;
}

/* style picker row — embedded in the print dialogs (reports.tsx StylePicker parity).
   mode: "academic" → classic/modern/compact · "kg" → all four (KG classes can
   print either doctrine) · "early" → the early-years sheet only. */
export function MarksheetStylePicker({
  value, set, mode = "academic",
}: {
  value: MarksheetStyle;
  set: (v: MarksheetStyle) => void;
  mode?: "academic" | "kg" | "early";
}) {
  const styles = MARKSHEET_STYLES.filter((s) =>
    mode === "early" ? (s.id === "creche" || s.id === "creche2") : mode === "kg" ? true : (s.id !== "creche" && s.id !== "creche2"));
  return (
    <div className="no-print flex flex-wrap gap-1.5">
      {styles.map((s) => (
        <button
          key={s.id}
          type="button"
          onClick={() => set(s.id)}
          title={s.blurb}
          className={cn(
            "rounded-md border px-2.5 py-1 text-[11px] font-medium transition-colors",
            value === s.id ? "border-primary bg-primary text-primary-foreground" : "border-border bg-background hover:bg-muted",
          )}
        >
          {s.label}
        </button>
      ))}
    </div>
  );
}

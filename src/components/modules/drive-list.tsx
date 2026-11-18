"use client";

import { useCallback, useEffect, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { StatCard, EmptyState, downloadCsv } from "@/components/ui-helpers";
import { fmtCedi } from "@/lib/format";
import { cn } from "@/lib/utils";
import { AlertTriangle, Bus, CheckCircle2, Download, Droplets, FileSpreadsheet, GraduationCap, HeartPulse, Printer, UtensilsCrossed, Users } from "lucide-react";

// ── DRIVE LIST (iEduPro `admin/drive_list_page` + `get_drive_list` parity)
//    The debtors board the bursar walks with every term: pick a fee stream,
//    see every class with learners owing, per-learner amounts and class
//    totals. Legacy fixed six wallet columns (invoices, feeding, classes,
//    transport, water, breakfast); here daily fees are services so the board
//    grows one card per service carrying arrears — invoices included.

export type DriveCard = {
  key: string;
  kind: "INVOICES" | "SERVICE";
  serviceId: string | null;
  label: string;
  category: string;
  arrearsCents: number;
  studentCount: number;
};

type DriveCardsPayload = { cards: DriveCard[]; classrooms: { id: string; name: string }[] };

type DriveListPayload = {
  type: string;
  label: string;
  category: string;
  groups: { classroom: string; classroomId: string; studentCount: number; totalCents: number; students: { studentId: string; name: string; indexNo: string | null; amountCents: number }[] }[];
  grandTotalCents: number;
  studentCount: number;
  classroomFilter: string | null;
};

const CATEGORY_STYLE: Record<string, { icon: React.ReactNode; tone: string; ring: string; chip: string }> = {
  INVOICE: { icon: <FileSpreadsheet className="size-5" />, tone: "bg-blue-600", ring: "border-blue-200 dark:border-blue-900", chip: "text-blue-700 dark:text-blue-300" },
  MEAL: { icon: <UtensilsCrossed className="size-5" />, tone: "bg-emerald-600", ring: "border-emerald-200 dark:border-emerald-900", chip: "text-emerald-700 dark:text-emerald-300" },
  CLASS: { icon: <GraduationCap className="size-5" />, tone: "bg-purple-600", ring: "border-purple-200 dark:border-purple-900", chip: "text-purple-700 dark:text-purple-300" },
  TRANSPORT: { icon: <Bus className="size-5" />, tone: "bg-orange-600", ring: "border-orange-200 dark:border-orange-900", chip: "text-orange-700 dark:text-orange-300" },
  DRINK: { icon: <Droplets className="size-5" />, tone: "bg-cyan-600", ring: "border-cyan-200 dark:border-cyan-900", chip: "text-cyan-700 dark:text-cyan-300" },
  CARE: { icon: <HeartPulse className="size-5" />, tone: "bg-pink-600", ring: "border-pink-200 dark:border-pink-900", chip: "text-pink-700 dark:text-pink-300" },
  OTHER: { icon: <AlertTriangle className="size-5" />, tone: "bg-amber-600", ring: "border-amber-200 dark:border-amber-900", chip: "text-amber-700 dark:text-amber-300" },
};

function styleOf(category: string) {
  return CATEGORY_STYLE[category] ?? CATEGORY_STYLE.OTHER;
}

export function DriveListTab() {
  const { currentSchoolId } = useStore();
  const [cards, setCards] = useState<DriveCardsPayload | null>(null);
  const [active, setActive] = useState<DriveCard | null>(null);
  const [list, setList] = useState<DriveListPayload | null>(null);
  const [loading, setLoading] = useState(false);
  const [classroom, setClassroom] = useState("all");

  useEffect(() => {
    if (!currentSchoolId) return;
    apiGet<DriveCardsPayload>(`/api/school/${currentSchoolId}/ops/drivelist?action=cards`)
      .then(setCards)
      .catch(() => setCards(null));
  }, [currentSchoolId]);

  const loadList = useCallback(
    (card: DriveCard, cls: string) => {
      if (!currentSchoolId) return;
      setLoading(true);
      const qs = `action=list&type=${encodeURIComponent(card.key)}${cls !== "all" ? `&classroom=${encodeURIComponent(cls)}` : ""}`;
      apiGet<DriveListPayload>(`/api/school/${currentSchoolId}/ops/drivelist?${qs}`)
        .then((d) => setList(d))
        .catch(() => setList(null))
        .finally(() => setLoading(false));
    },
    [currentSchoolId],
  );

  function pick(card: DriveCard) {
    setActive(card);
    setList(null);
    loadList(card, classroom);
  }

  function changeClassroom(cls: string) {
    setClassroom(cls);
    if (active) loadList(active, cls);
  }

  function exportCsv() {
    if (!list || !active) return;
    const rows: (string | number)[][] = [];
    for (const g of list.groups) {
      for (const s of g.students) rows.push([g.classroom, s.indexNo ?? "", s.name, s.amountCents / 100]);
      rows.push([g.classroom, "", "TOTAL", g.totalCents / 100]);
    }
    downloadCsv(`drive-list-${active.key.replace(/[^a-z0-9]+/gi, "-")}.csv`, ["Class", "Index", "Student", "Amount (GHS)"], rows);
  }

  const totalArrears = (cards?.cards ?? []).reduce((a, c) => a + c.arrearsCents, 0);
  const totalDebtors = (cards?.cards ?? []).reduce((a, c) => a + c.studentCount, 0);

  return (
    <div className="space-y-4">
      {/* board header + running totals */}
      <div className="hidden print:block mb-4">
        <h1 className="text-lg font-bold">Drive List — Students Owing{active ? ` · ${active.label}` : ""}</h1>
        <p className="text-xs">
          {list ? `${list.studentCount} learners · GHC ${(list.grandTotalCents / 100).toFixed(2)} total${classroom !== "all" ? ` · ${cards?.classrooms.find((c) => c.id === classroom)?.name}` : " · all classes"}` : ""}
        </p>
      </div>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 print:hidden">
        <StatCard icon={<AlertTriangle className="size-4" />} label="Drive (all streams)" value={fmtCedi(totalArrears, { compact: true })} tone="danger" sub={`${cards?.cards.length ?? 0} fee streams`} />
        <StatCard icon={<Users className="size-4" />} label="Debtors on record" value={String(totalDebtors)} sub="learners owing across streams" />
        <StatCard icon={<FileSpreadsheet className="size-4" />} label="Billed invoices owing" value={fmtCedi(cards?.cards.find((c) => c.kind === "INVOICES")?.arrearsCents ?? 0, { compact: true })} sub={`${cards?.cards.find((c) => c.kind === "INVOICES")?.studentCount ?? 0} learners`} />
        <StatCard icon={<CheckCircle2 className="size-4" />} label="Daily-fee streams owing" value={String((cards?.cards ?? []).filter((c) => c.kind === "SERVICE").length)} tone="gold" sub="feeding · classes · transport · water…" />
      </div>

      {/* fee-stream cards (CI3 drive_list_options fee-cards) */}
      <div className="flex flex-wrap gap-3 print:hidden">
        {(cards?.cards ?? []).map((c) => {
          const st = styleOf(c.category);
          const isActive = active?.key === c.key;
          return (
            <button
              key={c.key}
              onClick={() => pick(c)}
              aria-pressed={isActive}
              className={cn(
                "group w-[150px] flex-shrink-0 text-left rounded-xl border-2 p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg bg-card",
                st.ring,
                isActive && "scale-[1.03] border-current shadow-lg",
              )}
            >
              <div className="flex flex-col items-center gap-2 text-center">
                <div className={cn("w-12 h-12 rounded-full flex items-center justify-center text-white", st.tone, "transition-transform group-hover:scale-110")}>{st.icon}</div>
                <h4 className="text-sm font-bold leading-tight">{c.label}</h4>
                <p className={cn("text-xs tabular-nums font-semibold", st.chip)}>{fmtCedi(c.arrearsCents, { compact: true })}</p>
                <Badge variant="secondary" className="text-[10px] gap-1"><Users className="size-3" />{c.studentCount} owing</Badge>
              </div>
            </button>
          );
        })}
        {cards && cards.cards.length === 0 && <EmptyState icon={<CheckCircle2 className="size-5" />} title="No owing anywhere — the whole school is settled!" />}
        {!cards && <div className="w-full text-sm text-muted-foreground py-6 text-center">Loading drive board…</div>}
      </div>

      {/* class filter + actions + register (CI3 #drive-list-content) */}
      <div className="flex flex-wrap items-end gap-3 print:hidden">
        <div className="min-w-56">
          <label className="text-xs font-medium text-muted-foreground">Filter by class</label>
          <Select value={classroom} onValueChange={changeClassroom}>
            <SelectTrigger className="mt-1 h-10"><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All classes</SelectItem>
              {(cards?.classrooms ?? []).map((c) => (
                <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        {list && list.groups.length > 0 && (
          <div className="ml-auto flex gap-2">
            <Button variant="outline" className="gap-1.5" onClick={() => window.print()}><Printer className="size-4" /> Print</Button>
            <Button variant="outline" className="gap-1.5" onClick={exportCsv}><Download className="size-4" /> Excel (CSV)</Button>
          </div>
        )}
      </div>

      <div className="max-h-[620px] overflow-y-auto nice-scroll print:max-h-none print:overflow-visible">
        {loading && <div className="text-center py-10 text-sm text-muted-foreground">Loading debtors…</div>}
        {!loading && active && list && list.groups.length === 0 && (
          <EmptyState icon={<CheckCircle2 className="size-5" />} title={`No learners owing ${active.label.toLowerCase()}${classroom !== "all" ? " in this class" : ""}`} />
        )}
        {!loading && list?.groups.map((g) => (
          <Card key={g.classroomId} className="mb-4 overflow-hidden">
            <div className={cn("px-4 py-3 flex justify-between items-center", styleOf(list.category).tone)}>
              <h5 className="text-base font-bold text-white">{g.classroom}</h5>
              <span className="bg-white/90 text-foreground px-3 py-0.5 rounded-full text-xs font-bold">{g.studentCount} learners</span>
            </div>
            <CardContent className="p-0">
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead className="bg-secondary/60">
                    <tr className="text-left border-b">
                      <th className="px-4 py-2.5 font-medium w-10">#</th>
                      <th className="px-4 py-2.5 font-medium">Learner</th>
                      <th className="px-4 py-2.5 font-medium hidden sm:table-cell">Index</th>
                      <th className="px-4 py-2.5 font-medium text-right">Amount owed</th>
                    </tr>
                  </thead>
                  <tbody>
                    {g.students.map((s, i) => (
                      <tr key={s.studentId} className="border-b last:border-0 hover:bg-secondary/30">
                        <td className="px-4 py-2.5 text-muted-foreground">{i + 1}</td>
                        <td className="px-4 py-2.5 font-medium">{s.name}</td>
                        <td className="px-4 py-2.5 font-mono text-xs text-muted-foreground hidden sm:table-cell">{s.indexNo ?? "—"}</td>
                        <td className="px-4 py-2.5 text-right font-bold text-destructive tabular-nums">{fmtCedi(s.amountCents)}</td>
                      </tr>
                    ))}
                    <tr className="bg-secondary/40 font-bold">
                      <td colSpan={3} className="px-4 py-2.5 text-right">Class total:</td>
                      <td className="px-4 py-2.5 text-right tabular-nums">{fmtCedi(g.totalCents)}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        ))}
        {!loading && list && list.groups.length > 0 && (
          <div className="flex items-center justify-end gap-2 px-4 py-3 rounded-lg border font-bold">
            <span>Drive total — {list.studentCount} learners:</span>
            <span className="text-destructive tabular-nums">{fmtCedi(list.grandTotalCents)}</span>
          </div>
        )}
      </div>
    </div>
  );
}

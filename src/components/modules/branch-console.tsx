"use client";
import { useEffect, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { StatCard, ModuleHeader, LoadingBlock, StatusBadge } from "@/components/ui-helpers";
import { fmtCedi } from "@/lib/format";
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Legend } from "recharts";
import { Building2, School, Users, Banknote, Flag } from "lucide-react";

export function BranchConsole() {
  const { session, schools, setCurrentSchool, setModule } = useStore();
  const [data, setData] = useState<any | null>(null);

  useEffect(() => {
    const gid = session?.schoolGroupId;
    if (gid) apiGet<any>(`/api/group/${gid}`).then(setData).catch(() => setData(null));
  }, [session]);

  if (!data) return <LoadingBlock label="Loading branch network…" />;
  const b = data.branches;
  const totals = {
    students: b.reduce((s: number, x: any) => s + x.students, 0),
    collected: b.reduce((s: number, x: any) => s + x.collected, 0),
    arrears: b.reduce((s: number, x: any) => s + x.arrears, 0),
    flags: b.reduce((s: number, x: any) => s + x.flags, 0),
  };
  const chartData = b.map((x: any) => ({ name: x.code, Collected: Math.round(x.collected / 100), Arrears: Math.round(x.arrears / 100), Students: x.students }));

  return (
    <div>
      <ModuleHeader
        title={data.group.name}
        description={`Proprietor console — ${b.length} branches under one roof. Drill into any branch without leaving your desk.`}
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <StatCard icon={<Users className="size-4" />} label="Learners (all branches)" value={totals.students} tone="primary" />
        <StatCard icon={<Banknote className="size-4" />} label="Collected (term)" value={fmtCedi(totals.collected, { compact: true })} tone="gold" />
        <StatCard icon={<Banknote className="size-4" />} label="Arrears (term)" value={fmtCedi(totals.arrears, { compact: true })} tone="danger" />
        <StatCard icon={<Flag className="size-4" />} label="Active flags" value={totals.flags} />
      </div>

      <Card className="mt-4">
        <CardHeader className="pb-2"><CardTitle className="text-base">Collections vs arrears by branch (₵)</CardTitle></CardHeader>
        <CardContent className="h-64">
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={chartData} margin={{ top: 4, right: 8, left: -8, bottom: 0 }}>
              <XAxis dataKey="name" tick={{ fontSize: 11 }} stroke="#888" />
              <YAxis tick={{ fontSize: 11 }} stroke="#888" />
              <Tooltip formatter={(v: any) => `₵${Number(v).toLocaleString()}`} contentStyle={{ borderRadius: 10 }} />
              <Legend />
              <Bar dataKey="Collected" stackId="a" fill="#0e5f3f" radius={[0, 0, 0, 0]} />
              <Bar dataKey="Arrears" stackId="a" fill="#c05621" radius={[4, 4, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </CardContent>
      </Card>

      <Card className="mt-4">
        <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Building2 className="size-4" /> Branch comparison</CardTitle></CardHeader>
        <CardContent className="p-0">
          <div className="overflow-x-auto nice-scroll">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b bg-secondary/50 text-left">
                  <th className="px-3 py-2.5 font-medium">Branch</th>
                  <th className="px-3 py-2.5 font-medium">Zone</th>
                  <th className="px-3 py-2.5 font-medium text-right">Learners</th>
                  <th className="px-3 py-2.5 font-medium text-right hidden sm:table-cell">Staff</th>
                  <th className="px-3 py-2.5 font-medium text-right">Collection</th>
                  <th className="px-3 py-2.5 font-medium text-right hidden md:table-cell">Arrears</th>
                  <th className="px-3 py-2.5 font-medium hidden lg:table-cell">NaSIA</th>
                  <th className="px-3 py-2.5"></th>
                </tr>
              </thead>
              <tbody>
                {b.map((x: any) => (
                  <tr key={x.id} className="border-b last:border-0 hover:bg-secondary/30">
                    <td className="px-3 py-2.5">
                      <div className="font-medium flex items-center gap-2"><School className="size-4 text-muted-foreground" /> {x.name}</div>
                      <div className="text-xs text-muted-foreground">{x.town}{x.isMain && " · main campus"}</div>
                    </td>
                    <td className="px-3 py-2.5 text-muted-foreground">{x.zone}</td>
                    <td className="px-3 py-2.5 text-right tabular-nums font-semibold">{x.students}</td>
                    <td className="px-3 py-2.5 text-right tabular-nums hidden sm:table-cell">{x.staff}</td>
                    <td className="px-3 py-2.5 text-right">
                      <div className="font-semibold tabular-nums">{x.collectionRate}%</div>
                      <div className="text-[10px] text-muted-foreground">{fmtCedi(x.collected, { compact: true })} of {fmtCedi(x.billed, { compact: true })}</div>
                    </td>
                    <td className="px-3 py-2.5 text-right tabular-nums text-destructive hidden md:table-cell">{fmtCedi(x.arrears, { compact: true })}</td>
                    <td className="px-3 py-2.5 hidden lg:table-cell"><StatusBadge status={x.nasiaStatus} /></td>
                    <td className="px-3 py-2.5 text-right">
                      <Button size="sm" variant="outline" className="h-7" onClick={() => { setCurrentSchool(x.id); setModule("dashboard"); }}>Open</Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

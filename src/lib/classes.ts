// ── CI3 getFullClassList ordering — ported from iEduPro ──────────────
// scripts/ci3app/application/helpers/user_validation_helper.php:
//   CRECHE → NURSERY → KG → BASIC (1..6) → JHS (B7..B9), numeric asc
//   within each stage; international tracks follow in their own ladder.
// Applied EVERYWHERE classes populate — never mix orders.

export type OrderableClass = { gradeLevel: string; name: string };

/** CI3 class stage ladder — Creche first, then Nursery, KG, Basic, JHS. */
const STAGE_PATTERNS: [RegExp, number][] = [
  [/^CR\d*$/i, 0], // Creche
  [/^N\d$/i, 1], // Nursery 1/2
  [/^KG\d*$/i, 2], // Kindergarten
  [/^B[1-6]$/i, 3], // Basic 1–6 (Primary)
  [/^B[7-9]$/i, 4], // JHS 1–3 (B7–B9)
  [/^EYFS\d*$/i, 5], // Cambridge early years
  [/^Y(1[0-3]|[1-9])$/i, 6], // Cambridge Year 1–13
  [/^PYP[1-5]$/i, 7], // IB PYP
  [/^MYP[1-5]$/i, 8], // IB MYP
  [/^DP[1-2]$/i, 9], // IB DP
];

const NAME_STAGE_HINTS: [RegExp, number][] = [
  [/creche|crèche/i, 0],
  [/nursery/i, 1],
  [/\bkg\b|kindergarten/i, 2],
  [/jhs|junior high/i, 4],
  [/basic|primary/i, 3],
];

/** Rank of the stage this gradeLevel belongs to (lower = earlier). */
export function stageRank(gradeLevel: string, name?: string): number {
  const g = (gradeLevel ?? "").trim();
  for (const [re, rank] of STAGE_PATTERNS) if (re.test(g)) return rank;
  // fall back to parsing the display name
  const n = name ?? "";
  for (const [re, rank] of NAME_STAGE_HINTS) if (re.test(n)) return rank;
  return 99; // unknown — sorts last, alphabetically
}

/** Numeric part used to order within a stage (B4 → 4, KG2 → 2, Y10 → 10). */
export function gradeNumeric(gradeLevel: string, name?: string): number {
  const m = (gradeLevel ?? "").match(/(\d+)$/);
  if (m) return parseInt(m[1], 10);
  const nm = (name ?? "").match(/(\d+)/);
  return nm ? parseInt(nm[1], 10) : 0;
}

/** Full sort key: stage → numeric → name (deterministic tiebreak). */
export function classSortKey(c: OrderableClass): [number, number, string] {
  return [stageRank(c.gradeLevel, c.name), gradeNumeric(c.gradeLevel, c.name), c.name];
}

/** Sort a list of classrooms into CI3 order (returns a new array). */
export function sortClassrooms<T extends OrderableClass>(list: T[]): T[] {
  return [...list].sort((a, b) => {
    const ka = classSortKey(a);
    const kb = classSortKey(b);
    if (ka[0] !== kb[0]) return ka[0] - kb[0];
    if (ka[1] !== kb[1]) return ka[1] - kb[1];
    return ka[2].localeCompare(kb[2]);
  });
}

/** Human stage label for grouping ("Creche", "Basic", "JHS 1–3"…). */
export function stageLabelOf(gradeLevel: string, name?: string): string {
  switch (stageRank(gradeLevel, name)) {
    case 0: return "Creche";
    case 1: return "Nursery";
    case 2: return "Kindergarten";
    case 3: return "Primary (Basic 1–6)";
    case 4: return "Junior High (B7–B9)";
    case 5: return "Early Years";
    case 6: return "Cambridge";
    case 7: return "IB PYP";
    case 8: return "IB MYP";
    case 9: return "IB DP";
    default: return "Other";
  }
}

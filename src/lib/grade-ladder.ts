/* Wave 23 — the grade ladder: which gradeLevel follows which.

   Ghanaian private schools run KG1 → KG2 → Basic 1..9 (B1..B9), with
   JHS 3 (B9) as the exit grade into senior high. International schools
   on the file also carry PYP → MYP → DP and Cambridge Year 1..13
   patterns, so the ladder speaks all of them:

     KG1 → KG2 → B1 → B2 → … → B8 → B9 → (exit)
     PYP1..4 → next PYP, PYP5 → MYP1, MYP1..4 → next MYP, MYP5 → DP1,
     DP1 → DP2 → (exit)
     Y1..Y12 → next Year, Y13 → (exit)

   `nextGradeLevel(null-ish)` returns null meaning "top of the ladder —
   the learner graduates". When the next grade simply has no classroom in
   the school the board asks the head teacher to pick a destination or
   graduate the learner explicitly, so a missing class can never silently
   graduate a learner. */

export function nextGradeLevel(gradeLevel: string | null | undefined): string | null {
  if (!gradeLevel) return null;
  const g = gradeLevel.trim().toUpperCase();

  const m = /^([A-Z]+)(\d+)$/.exec(g);
  if (!m) return null;
  const [, stage, numStr] = m;
  const n = parseInt(numStr, 10);

  switch (stage) {
    case "KG":
      return n < 2 ? `KG${n + 1}` : "B1"; // KG2 graduates into Basic 1
    case "B":
      return n >= 1 && n <= 8 ? `B${n + 1}` : null; // B9 exits
    case "PYP":
      return n <= 4 ? `PYP${n + 1}` : "MYP1"; // PYP5 feeds Middle Years
    case "MYP":
      return n <= 4 ? `MYP${n + 1}` : "DP1"; // MYP5 feeds Diploma
    case "DP":
      return n < 2 ? `DP${n + 1}` : null; // DP2 exits
    case "Y":
      return n >= 1 && n <= 12 ? `Y${n + 1}` : null; // Year 13 exits
    default:
      return null;
  }
}

/** True when the learner sits in the final grade of their ladder —
 *  the promotion board proposes "graduate" for these. */
export function isExitGrade(gradeLevel: string | null | undefined): boolean {
  return gradeLevel ? nextGradeLevel(gradeLevel) === null : false;
}

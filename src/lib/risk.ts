// Fee-default & disengagement risk engine (deterministic, explainable).
export type RiskFactors = {
  arrearsRatio: number;      // outstanding / billed this term
  attendanceRate: number;    // 0..1
  activeFlags: number;
  academicAvg?: number;      // current-term mean %
  priorTransfers: number;
};

export function computeRiskScore(f: RiskFactors): { score: number; level: "LOW" | "MODERATE" | "HIGH" | "CRITICAL"; recommendation: string } {
  const feePart = Math.min(55, f.arrearsRatio * 55);
  const attPart = Math.min(25, (1 - f.attendanceRate) * 120);
  const flagPart = Math.min(15, f.activeFlags * 8);
  const acadPart = f.academicAvg !== undefined ? Math.min(5, Math.max(0, (60 - f.academicAvg) / 8)) : 2;
  const transferPart = Math.min(5, f.priorTransfers * 2.5);
  const score = Math.round(Math.min(100, feePart + attPart + flagPart + acadPart + transferPart));
  const level = score >= 75 ? "CRITICAL" : score >= 50 ? "HIGH" : score >= 25 ? "MODERATE" : "LOW";
  const recommendation =
    level === "CRITICAL"
      ? "Immediate guardian conference required; agree a signed payment plan and consider holding exam entry until the first instalment is met."
      : level === "HIGH"
      ? "Contact the guardian this week; monitor attendance daily and log all follow-ups."
      : level === "MODERATE"
      ? "Send a friendly fee reminder and watch attendance over the next two weeks."
      : "No action needed — keep encouraging the learner.";
  return { score, level, recommendation };
}

export const RISK_COLORS: Record<string, string> = {
  LOW: "#16a34a",
  MODERATE: "#d9a521",
  HIGH: "#ea580c",
  CRITICAL: "#dc2626",
};

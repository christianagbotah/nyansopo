// ── Staff attendance & duty shared core (wave 28) ──
// Entitlements + stat math shared by the ops/staffduty API route and the
// staff self-service portal (view=staff payload).

/** Ghana annual leave entitlements used for the balance summary (caps per type; null = no fixed cap). */
export const LEAVE_ENTITLEMENTS: Record<string, number | null> = {
  ANNUAL: 15,
  SICK: 10,
  MATERNITY: 90,
  PATERNITY: 10,
  STUDY: null,
  COMPASSIONATE: null,
  OTHER: null,
};

/** Attendance stats for a bag of status strings (LEAVE counts as excused from the rate). */
export function attStats(rows: { status: string }[]) {
  const present = rows.filter((r) => r.status === "PRESENT").length;
  const late = rows.filter((r) => r.status === "LATE").length;
  const absent = rows.filter((r) => r.status === "ABSENT").length;
  const leave = rows.filter((r) => r.status === "LEAVE").length;
  const marked = present + late + absent + leave;
  return {
    marked,
    present, late, absent, leave,
    rate: marked ? Math.round(((present + late) / marked) * 100) : null,
    punctuality: marked ? Math.round((present / marked) * 100) : null,
  };
}

/** Leave balances per staff over APPROVED requests. */
export function leaveBalances(rows: { type: string; status: string; days: number }[]) {
  const approved = rows.filter((r) => r.status === "APPROVED");
  return Object.entries(LEAVE_ENTITLEMENTS).map(([type, entitlement]) => {
    const used = approved.filter((r) => r.type === type).reduce((s, r) => s + r.days, 0);
    return { type, entitlement, used, remaining: entitlement == null ? null : Math.max(entitlement - used, 0) };
  });
}

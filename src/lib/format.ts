// Client-safe formatting helpers
export const fmtCedi = (cents: number, opts?: { compact?: boolean }) => {
  if (opts?.compact && Math.abs(cents) >= 100000) {
    return `GH₵${(cents / 100000).toFixed(0)}k`;
  }
  return `GH₵${(cents / 100).toLocaleString("en-GH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

export const fmtDate = (d: string | Date | null | undefined) => {
  if (!d) return "—";
  return new Date(d).toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
};

export const fmtDateShort = (d: string | Date | null | undefined) => {
  if (!d) return "—";
  return new Date(d).toLocaleDateString("en-GB", { day: "numeric", month: "short" });
};

export const fmtDateTime = (d: string | Date | null | undefined) => {
  if (!d) return "—";
  return new Date(d).toLocaleString("en-GB", { day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" });
};

export const PAYMENT_LABELS: Record<string, string> = {
  CASH: "Cash",
  MOMO_MTN: "MTN MoMo",
  MOMO_TELECEL: "Telecel Cash",
  MOMO_AT: "AT Money",
  BANK: "Bank Transfer",
  CHEQUE: "Cheque",
  CARD: "Card",
};

export const FLAG_LABELS: Record<string, string> = {
  FEE_ARREARS: "Fee Arrears",
  WITHDREW_OWING: "Withdrew Owing Fees",
  DISCIPLINE_SUSPENSION: "Discipline — Suspension",
  DISCIPLINE_EXPULSION: "Discipline — Expulsion",
  EXAM_MALPRACTICE: "Examination Malpractice",
  PROPERTY_UNRETURNED: "School Property Unreturned",
};

export const STATUS_COLORS: Record<string, string> = {
  PAID: "default", PARTIAL: "secondary", UNPAID: "destructive", OVERDUE: "destructive",
  ACTIVE: "destructive", RESOLVED: "default", CONTESTED: "secondary", WITHDRAWN: "outline",
  PENDING: "secondary", APPROVED: "default", DECLINED: "destructive", FULFILLED: "default",
  COMPLETED: "default", PENDING_RELEASE: "destructive", NEW: "secondary", MATCHED: "secondary",
  FLAGGED: "destructive", CLEARED: "default", ENROLLED: "default", WITHDREW: "outline",
  TRANSFERRED: "secondary", GRADUATED: "default", EXPELLED: "destructive",
  LICENSED: "default", PROVISIONAL: "secondary", PENDING: "destructive", EXPIRED: "destructive",
  VERIFIED: "default", LAPSED: "destructive",
};

export const SEVERITY_STYLES: Record<string, string> = {
  LOW: "bg-emerald-600",
  MEDIUM: "bg-amber-500",
  HIGH: "bg-orange-600",
  CRITICAL: "bg-red-600",
};

export const CURRICULUM_BADGE: Record<string, { label: string; className: string }> = {
  GES: { label: "GES · SBC/CCP", className: "bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200" },
  CAIE: { label: "Cambridge · CAIE", className: "bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200" },
  IB: { label: "IB World School", className: "bg-rose-100 text-rose-900 dark:bg-rose-950 dark:text-rose-200" },
};

export const initials = (name: string) =>
  name.split(" ").filter(Boolean).slice(0, 2).map((w) => w[0]?.toUpperCase()).join("");

export const avatarColor = (seed: string) => {
  const colors = ["#0e5f3f", "#c05621", "#9b2c2c", "#7c4a21", "#0e7490", "#6b3fa0", "#b45309"];
  let h = 0;
  for (const ch of seed) h = (h * 31 + ch.charCodeAt(0)) % 997;
  return colors[h % colors.length];
};

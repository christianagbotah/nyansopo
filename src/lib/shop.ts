// ── wave 77 — School-shop shared helpers ──────────────────────
import { db } from "@/lib/db";
import { Prisma } from "@prisma/client";

/** Per-school shop-order number SO-00001 — atomic counter row (first
 *  allocation creates, concurrent allocations increment on P2002). */
export async function nextOrderNo(schoolId: string): Promise<string> {
  const scope = `SHOPORD:${schoolId}`;
  try {
    const row = await db.codeCounter.create({ data: { scope, next: 1 } });
    return `SO-${String(row.next).padStart(5, "0")}`;
  } catch (e) {
    if (!(e instanceof Prisma.PrismaClientKnownRequestError && e.code === "P2002")) throw e;
  }
  const row = await db.codeCounter.update({ where: { scope }, data: { next: { increment: 1 } } });
  return `SO-${String(row.next).padStart(5, "0")}`;
}

export const SHOP_ORDER_STATUS: Record<string, { label: string; tone: string }> = {
  PENDING_PAYMENT: { label: "Awaiting payment", tone: "amber" },
  PAID: { label: "Paid — being packed", tone: "sky" },
  READY: { label: "Packed — out for delivery", tone: "violet" },
  DELIVERED: { label: "Delivered", tone: "green" },
  CANCELLED: { label: "Cancelled", tone: "red" },
};

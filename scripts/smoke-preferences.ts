/* Settings preferences smoke — branding uploads, receipt-style default,
   per-term "days opened", and the end-to-end wiring into new receipts.
   Run: DATABASE_URL=postgresql://… bun scripts/smoke-preferences.ts
   (needs `bun run dev` on :3000) */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();
const BASE = "http://localhost:3000";
let pass = 0, fail = 0;
const check = (label: string, cond: boolean, extra = "") => {
  if (cond) { pass++; console.log(`  ✅ ${label}`); }
  else { fail++; console.log(`  ❌ ${label} ${extra}`); }
};

// 1×1 transparent PNG
const TINY_PNG = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==";

async function main() {
  const user = await db.user.findUnique({ where: { email: "head@nyansapo.gh" } });
  const school = await db.school.findUnique({ where: { code: "GEA" } });
  if (!user || !school) { console.log("missing demo data — run the seed first"); return; }
  const H = { "content-type": "application/json", "x-user-id": user.id };
  const schoolId = school.id;
  const post = (body: any) => fetch(`${BASE}/api/school/${schoolId}/settings`, { method: "POST", headers: H, body: JSON.stringify(body) });
  const get = () => fetch(`${BASE}/api/school/${schoolId}/settings`, { headers: H }).then((r) => r.json());

  console.log("── (a) save branding + receipt style + days opened ──");
  const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
  if (!term) { console.log("no current term — aborting"); return; }
  const r1 = await post({ action: "update-preferences", logoUrl: TINY_PNG, signatureUrl: "javascript:alert(1)", receiptStyle: "THERMAL", daysOpened: { [term.id]: 62 } });
  check("update-preferences 200", r1.ok);
  const d1 = await get();
  check("logo persisted as a media asset, junk signature rejected", d1.branding?.logoUrl?.startsWith("/api/media/") === true && !d1.branding?.signatureUrl);
  check("receipt style saved (THERMAL)", d1.branding?.receiptStyle === "THERMAL");
  check("days opened saved for current term", d1.daysOpened?.[term.id] === 62, JSON.stringify(d1.daysOpened));

  console.log("\n── (b) validation guards ──");
  const bad1 = await post({ action: "update-preferences", receiptStyle: "DOTMATRIX" });
  check("unknown style → 400", bad1.status === 400, `got ${bad1.status}`);
  const bad2 = await post({ action: "update-preferences", daysOpened: { [term.id]: 9999 } });
  check("days opened > 400 → 400", bad2.status === 400, `got ${bad2.status}`);
  const otherTerm = await db.term.findFirst({ where: { academicYear: { schoolId: { not: schoolId } } } });
  if (otherTerm) {
    const bad3 = await post({ action: "update-preferences", daysOpened: { [otherTerm.id]: 50 } });
    check("foreign term → 404", bad3.status === 404, `got ${bad3.status}`);
  }

  console.log("\n── (c) new receipts default to the saved style ──");
  // pay an invoice through the normal path, then read the payload the API returns
  const inv = await db.invoice.findFirst({
    where: { schoolId, OR: [{ status: "UNPAID" }, { status: "PARTIAL" }], totalCents: { gt: 3000 } },
    orderBy: { invoiceNo: "asc" },
  });
  if (!inv) { console.log("no owing invoice — skipping receipt check"); }
  else {
    const snap = { paidCents: inv.paidCents, status: inv.status };
    const preReceipts = new Set((await db.payment.findMany({ where: { schoolId }, select: { id: true } })).map((p) => p.id));
    const amt = Math.min(3000, inv.totalCents - inv.discountCents - inv.paidCents);
    const payRes = await fetch(`${BASE}/api/school/${schoolId}/fees`, {
      method: "POST", headers: H,
      body: JSON.stringify({ action: "record-payment", invoiceId: inv.id, amountCents: amt, method: "CASH" }),
    });
    const payBody = await payRes.json();
    const style = payBody?.receipt?.style ?? payBody?.receipt?.data?.style;
    check("payment ok + receipt payload carries THERMAL default", payRes.ok && style === "THERMAL", JSON.stringify(payBody).slice(0, 160));
    const schoolBlock = payBody?.receipt?.school ?? payBody?.receipt?.data?.school;
    check("receipt school block carries logo for letterhead", !!schoolBlock?.logoUrl?.startsWith("/api/media/"));
    // cleanup
    const pay = await db.payment.findFirst({ where: { schoolId, id: { notIn: [...preReceipts] } } });
    if (pay) await db.payment.delete({ where: { id: pay.id } });
    await db.invoice.update({ where: { id: inv.id }, data: snap });
  }

  console.log("\n── (d) reset preferences ──");
  await post({ action: "update-preferences", logoUrl: null, signatureUrl: null, receiptStyle: "A5", daysOpened: { [term.id]: 0 } });
  const d2 = await get();
  check("reset to A5 + logo cleared", d2.branding?.receiptStyle === "A5" && !d2.branding?.logoUrl);
  // restore the days-opened key to its pre-test absence
  await db.schoolSetting.deleteMany({ where: { schoolId, key: `term.daysOpened:${term.id}` } });
  const preKeys = ["branding.logoUrl", "branding.signatureUrl", "receipts.style"];
  await db.schoolSetting.deleteMany({ where: { schoolId, key: { in: preKeys } } });
  // drop the media assets this run created (nothing references them now)
  await db.mediaAsset.deleteMany({ where: { schoolId, kind: { in: ["LOGO", "SIGNATURE"] } } });
  const d3 = await get();
  check("demo DB restored (branding cleared)", !d3.branding?.logoUrl && d3.branding?.receiptStyle === "A5" && d3.daysOpened?.[term.id] == null);

  console.log(`\n${pass} passed, ${fail} failed`);
  process.exit(fail ? 1 : 0);
}
main();

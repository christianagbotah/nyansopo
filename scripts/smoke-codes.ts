/* Sync-safe code generation smoke test — proves the guarantees the
   offline-sync architecture depends on (see src/lib/codes.ts):

   (1) ATOMICITY  — concurrent minters (several clerks + an offline flush
       racing in the same process, AND separate processes on separate
       connections) never receive the same number.
   (2) CONTINUITY — the counter seeds from the highest existing code of the
       kind (legacy/seeded data keeps its numbers) and is monotonic after.
   (3) IDEMPOTENCY— re-flushing the same offline op (clientId) after a crash
       mid-flush mints exactly ONE receipt and credits the invoice once.

   Plus: year-scope isolation (INV-2027 starts at 0001) and full cleanup —
   counters are restored and test rows removed, so the demo DB is untouched.

   Wave 41 — the offline code RESERVE (the heart of the project: offline /
   online toggling without a single duplicate code):
   (4) DISJOINTNESS — reserve blocks granted concurrently are pairwise
       disjoint, and single mints racing a grant never land inside any block.
   (5) ADOPTION     — the exact blackout scenario: work online → reserve a
       block → collect offline spending the block → more online mints →
       flush: the receipt printed offline is adopted EXACTLY, later online
       mints stay above the block, and the union of every code stays unique.
   (6) DURABILITY  — a re-flush of the same offline op (retry storm after a
       lost response) trips the unique syncClientId and is "already synced";
       a forged/foreign lease tag is refused (server mints its own);
       an exhausted reserve degrades to queue-then-mint — still unique.

   Run: DATABASE_URL=postgresql://… bun scripts/smoke-codes.ts
   (needs `bun run dev` on :3000 for parts 4–6) */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";
import { spawn } from "node:child_process";

const db = new PrismaClient();
const BASE = "http://localhost:3000";
let pass = 0, fail = 0;
const check = (label: string, cond: boolean, extra = "") => {
  if (cond) { pass++; console.log(`  ✅ ${label}`); }
  else { fail++; console.log(`  ❌ ${label} ${extra}`); }
};

// ── worker mode: mint `n` invoice numbers on this process's own connection ──
if (process.argv[2] === "worker") {
  const n = Number(process.argv[3] ?? 10);
  const { nextInvoiceNo } = await import("../src/lib/codes");
  const out: string[] = [];
  for (let i = 0; i < n; i++) out.push(await nextInvoiceNo());
  console.log(JSON.stringify(out));
  process.exit(0);
}

async function main() {
  const YEAR = new Date().getFullYear();
  console.log("══ 1. In-process concurrent minting (Promise.all, one pool) ══");
  // snapshot counter state for restore
  const preCounters = await db.codeCounter.findMany({ where: { scope: { in: [`INV:${YEAR}`, `RCP:${YEAR}`, `NYIDX:2016`] } } });
  const preMap = new Map(preCounters.map((c) => [c.scope, c.next]));
  const preInvCounter = preCounters.find((c) => c.scope === `INV:${YEAR}`);
  const maxInv = await db.invoice.findFirst({ where: { invoiceNo: { startsWith: `INV-${YEAR}-` } }, orderBy: { invoiceNo: "desc" }, select: { invoiceNo: true } });

  const [inv, rcp, ny] = await Promise.all([
    Promise.all(Array.from({ length: 25 }, () => import("../src/lib/codes").then((m) => m.nextInvoiceNo()))),
    Promise.all(Array.from({ length: 25 }, () => import("../src/lib/codes").then((m) => m.nextReceiptNo()))),
    Promise.all(Array.from({ length: 25 }, () => import("../src/lib/codes").then((m) => m.nextStudentIndex(2016)))),
  ]);
  check("25 concurrent invoice numbers — all unique", new Set(inv).size === 25, JSON.stringify(inv));
  check("25 concurrent receipt numbers — all unique", new Set(rcp).size === 25);
  check("25 concurrent student indexes — all unique", new Set(ny).size === 25);
  const invN = inv.map((c) => Number(c.slice(`INV-${YEAR}-`.length))).sort((a, b) => a - b);
  check("invoice numbers contiguous (no gaps, no dupes)", invN.every((n, i) => i === 0 || n === invN[i - 1] + 1), `got ${invN[0]}..${invN[24]}`);
  if (preInvCounter) {
    // counter already alive (earlier app runs) → continuity means: the burst
    // picks up exactly where the counter left off
    check(`counter continued from INV:${YEAR} next=${preInvCounter.next}`, invN[0] === preInvCounter.next + 1, `first minted ${invN[0]}, counter was ${preInvCounter.next}`);
  } else if (maxInv) {
    // fresh counter → seeds from the highest existing code of the kind
    const seed = Number(maxInv.invoiceNo.slice(`INV-${YEAR}-`.length));
    check(`counter seeded from existing max INV-${YEAR}-${seed}`, invN[0] === seed + 1, `first minted ${invN[0]}, max existing ${seed}`);
  }

  console.log("\n══ 2. Cross-process concurrent minting (3 devices, own connections) ══");
  const workers = await Promise.all([0, 1, 2].map(() => new Promise<string[]>((resolve, reject) => {
    const child = spawn(process.execPath, [process.argv[1]!, "worker", "10"], { env: process.env, stdio: ["ignore", "pipe", "inherit"] });
    let buf = "";
    child.stdout.on("data", (d) => (buf += d));
    child.on("close", (code) => {
      if (code !== 0) return reject(new Error(`worker exit ${code}`));
      // worker stdout also carries prisma:query logs (db.ts logs queries) —
      // the JSON array is the last line that starts with "["
      const line = buf.trim().split("\n").filter((l) => l.startsWith("[")).pop();
      try { resolve(JSON.parse(line ?? "")); } catch (e) { reject(new Error(`worker output not JSON: ${line?.slice(0, 80)}`)); }
    });
    child.on("error", reject);
  })));
  const all = workers.flat();
  check("30 numbers across 3 concurrent processes — all unique", new Set(all).size === 30, `unique=${new Set(all).size}`);
  const allN = all.map((c) => Number(c.slice(`INV-${YEAR}-`.length))).sort((a, b) => a - b);
  check("cross-process numbers contiguous", allN.every((n, i) => i === 0 || n === allN[i - 1] + 1), `${allN[0]}..${allN[29]}`);
  // continuation after the burst
  const { nextInvoiceNo } = await import("../src/lib/codes");
  const after = await nextInvoiceNo();
  check("next mint continues after the burst", Number(after.slice(`INV-${YEAR}-`.length)) === allN[29] + 1, after);

  console.log("\n══ 3. Year-scope isolation ══");
  const { nextSeq } = await import("../src/lib/codes");
  const fresh = await nextSeq("INV", YEAR + 1); // e.g. INV:2027 — no data yet
  check(`nextSeq(INV, ${YEAR + 1}) starts at 1 (fresh year scope)`, fresh === 1, `got ${fresh}`);
  await db.codeCounter.delete({ where: { scope: `INV:${YEAR + 1}` } }); // leave no trace

  console.log("\n══ 4. Offline flush idempotency (POST /api/sync ×2, same clientId) ══");
  const user = await db.user.findUnique({ where: { email: "head@nyansapo.gh" } });
  const school = await db.school.findUnique({ where: { code: "GEA" } });
  if (!user || !school) console.log("missing demo users — run the seed first");
  else {
    const inv2 = await db.invoice.findFirst({
      where: { schoolId: school.id, OR: [{ status: "UNPAID" }, { status: "PARTIAL" }, { status: "OVERDUE" }], totalCents: { gt: 5000 } },
      orderBy: { invoiceNo: "asc" },
    });
    if (!inv2) console.log("no owing invoice — skipping sync test");
    else {
      const snap = { paidCents: inv2.paidCents, status: inv2.status };
      const preReceipts = new Set((await db.payment.findMany({ where: { schoolId: school.id }, select: { id: true } })).map((p) => p.id));
      const amt = Math.min(5000, inv2.totalCents - inv2.discountCents - inv2.paidCents);
      const op = { clientId: `smoke-codes-${Date.now()}`, kind: "payment", payload: { schoolId: school.id, invoiceId: inv2.id, amountCents: amt, method: "CASH", category: "INVOICE" } };
      const H = { "content-type": "application/json", "x-user-id": user.id };
      const r1 = await (await fetch(`${BASE}/api/sync`, { method: "POST", headers: H, body: JSON.stringify({ ops: [op] }) })).json();
      const r2 = await (await fetch(`${BASE}/api/sync`, { method: "POST", headers: H, body: JSON.stringify({ ops: [op] }) })).json();
      check("first flush ok", r1.results?.[0]?.ok === true, JSON.stringify(r1.results?.[0]));
      check("re-flush recognises duplicate (idempotent)", r2.results?.[0]?.ok === true && r2.results?.[0]?.detail === "already synced", JSON.stringify(r2.results?.[0]));
      const synced = await db.payment.findMany({ where: { schoolId: school.id, reference: `SYNC:${op.clientId}` } });
      check("exactly ONE receipt minted for the clientId", synced.length === 1, `found ${synced.length}`);
      check("receipt carries a proper sequential RCP code", /^RCP-\d{4}-\d{5}$/.test(synced[0]?.receiptNo ?? ""), synced[0]?.receiptNo);
      const after2 = await db.invoice.findUnique({ where: { id: inv2.id } });
      check("invoice credited exactly once", after2!.paidCents === snap.paidCents + amt, `paid ${snap.paidCents}→${after2!.paidCents}, expected +${amt}`);
      // ── cleanup: remove test receipt, restore invoice + RCP counter ──
      await db.payment.delete({ where: { id: synced[0].id } });
      await db.invoice.update({ where: { id: inv2.id }, data: { paidCents: snap.paidCents, status: snap.status } });
      const rcpPre = preMap.get(`RCP:${YEAR}`);
      if (rcpPre !== undefined) await db.codeCounter.update({ where: { scope: `RCP:${YEAR}` }, data: { next: rcpPre } });
      else await db.codeCounter.deleteMany({ where: { scope: `RCP:${YEAR}` } });
      const postReceipts = new Set((await db.payment.findMany({ where: { schoolId: school.id }, select: { id: true } })).map((p) => p.id));
      check("cleanup — demo receipts untouched", [...postReceipts].every((id) => preReceipts.has(id)));
    }
  }

  console.log("\n══ 5. Offline student enrolment — idempotent re-flush + first-term billing ══");
  if (!user || !school) console.log("missing demo users — skipping student sync test");
  else {
    // pick a classroom that has a fee structure for the current term, so the
    // flush must auto-bill (online enrolment parity)
    const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId: school.id } } });
    const fsClassroom = term
      ? await db.classroom.findFirst({ where: { schoolId: school.id, feeStructures: { some: { termId: term.id } } } })
      : await db.classroom.findFirst({ where: { schoolId: school.id } });
    if (!fsClassroom || !term) console.log("no classroom/term — skipping student sync test");
    else {
      const preStudents = await db.student.count();
      const preSchool = await db.school.findUnique({ where: { id: school.id }, select: { studentsOnRoll: true } });
      const op2 = {
        clientId: `smoke-stu-${Date.now()}`,
        kind: "student",
        payload: { schoolId: school.id, classroomId: fsClassroom.id, firstName: "Sync", lastName: "Probe", gender: "M", dateOfBirth: null, guardianName: "Probe Guardian", guardianPhone: "+233500000001" },
      };
      const H2 = { "content-type": "application/json", "x-user-id": user.id };
      const s1 = await (await fetch(`${BASE}/api/sync`, { method: "POST", headers: H2, body: JSON.stringify({ ops: [op2] }) })).json();
      const s2 = await (await fetch(`${BASE}/api/sync`, { method: "POST", headers: H2, body: JSON.stringify({ ops: [op2] }) })).json();
      check("first flush ok + billed", s1.results?.[0]?.ok === true && /billed INV-/.test(s1.results?.[0]?.detail ?? ""), JSON.stringify(s1.results?.[0]));
      check("re-flush idempotent (no duplicate student)", s2.results?.[0]?.detail === "already synced", JSON.stringify(s2.results?.[0]));
      const probes = await db.student.findMany({ where: { syncClientId: op2.clientId } });
      check("exactly ONE student row for the clientId", probes.length === 1, `found ${probes.length}`);
      check("student carries a real NY- index (no SYNC- placeholder)", /^NY-\d{4}-\d{6}$/.test(probes[0]?.nyansapoIndex ?? ""), probes[0]?.nyansapoIndex);
      const probeInv = await db.invoice.findFirst({ where: { studentId: probes[0]?.id }, include: { items: true } });
      check("first-term invoice generated at flush (online parity)", !!probeInv && /^INV-\d{4}-\d{4}$/.test(probeInv.invoiceNo) && probeInv.items.length > 0, probeInv?.invoiceNo);
      const postStudents = await db.student.count();
      const postSchool = await db.school.findUnique({ where: { id: school.id }, select: { studentsOnRoll: true } });
      check("student count +1 (not +2)", postStudents === preStudents + 1, `${preStudents}→${postStudents}`);
      check("studentsOnRoll updated", postSchool!.studentsOnRoll === preSchool!.studentsOnRoll + 1, `${preSchool!.studentsOnRoll}→${postSchool!.studentsOnRoll}`);
      // ── cleanup: remove probe student chain + restore counters ──
      if (probeInv) { await db.invoiceItem.deleteMany({ where: { invoiceId: probeInv.id } }); await db.invoice.delete({ where: { id: probeInv.id } }); }
      await db.enrollment.deleteMany({ where: { studentId: probes[0]?.id } });
      await db.student.delete({ where: { id: probes[0]!.id } });
      await db.school.update({ where: { id: school.id }, data: { studentsOnRoll: preSchool!.studentsOnRoll } });
      const invPre = preMap.get(`INV:${YEAR}`);
      if (invPre !== undefined) await db.codeCounter.update({ where: { scope: `INV:${YEAR}` }, data: { next: invPre } });
      else await db.codeCounter.deleteMany({ where: { scope: `INV:${YEAR}` } });
      const nyPre = preMap.get(`NYIDX:${new Date().getFullYear()}`);
      if (nyPre !== undefined) await db.codeCounter.update({ where: { scope: `NYIDX:${new Date().getFullYear()}` }, data: { next: nyPre } });
      else await db.codeCounter.deleteMany({ where: { scope: `NYIDX:${new Date().getFullYear()}` } });
      const finalStudents = await db.student.count();
      check("cleanup — student count restored", finalStudents === preStudents, `${finalStudents} vs ${preStudents}`);
    }
  }

  // ── restore INV/NYIDX counters to pre-test state (numbers minted in parts
  //    1–2 created no rows, so rolling the counter back leaves zero trace) ──
  for (const scope of [`INV:${YEAR}`, `NYIDX:2016`]) {
    if (preMap.has(scope)) await db.codeCounter.update({ where: { scope }, data: { next: preMap.get(scope)! } });
    else await db.codeCounter.deleteMany({ where: { scope } });
  }

  console.log("\n══ 6. Offline code reserve (wave 41) — grant · spend · adopt · reject · exhaust ══");
  if (!user || !school) console.log("missing demo users — skipping reserve test");
  else {
    const inv3 = await db.invoice.findFirst({
      where: { schoolId: school.id, OR: [{ status: "UNPAID" }, { status: "PARTIAL" }, { status: "OVERDUE" }], totalCents: { gt: 5000 } },
      orderBy: { invoiceNo: "asc" },
    });
    if (!inv3) console.log("no owing invoice — skipping reserve test");
    else {
      const { nextReceiptNo, leaseSeqBlock } = await import("../src/lib/codes");
      const preRcp = preMap.get(`RCP:${YEAR}`);
      const snap = { paidCents: inv3.paidCents, status: inv3.status };
      const amt = Math.max(100, Math.min(200, inv3.totalCents - inv3.discountCents - inv3.paidCents));
      const H3 = { "content-type": "application/json", "x-user-id": user.id };
      const seqOf = (code: string) => Number(code.slice(`RCP-${YEAR}-`.length));

      // (a) DISJOINTNESS — blocks granted concurrently never overlap, and
      // single mints racing the grants never land inside any block
      const [b1, b2, b3, singles] = await Promise.all([
        leaseSeqBlock("RCP", 30, YEAR),
        leaseSeqBlock("RCP", 30, YEAR),
        leaseSeqBlock("RCP", 30, YEAR),
        Promise.all(Array.from({ length: 10 }, () => nextReceiptNo())),
      ]);
      const blocks = [b1, b2, b3];
      check("3 concurrently granted reserve blocks are pairwise disjoint",
        blocks.every((b, i) => blocks.every((o, j) => i === j || b.to < o.from || o.to < b.from)),
        JSON.stringify(blocks));
      check("single mints racing the grants stay outside every block",
        singles.every((s) => blocks.every((b) => seqOf(s) < b.from || seqOf(s) > b.to)),
        JSON.stringify(singles));

      // (b) ADOPTION — the exact blackout scenario:
      //     work ONLINE first (receipts already committed server-side) …
      const onlineBefore = [await nextReceiptNo(), await nextReceiptNo(), await nextReceiptNo()];
      //     … device RESERVES a block while still online …
      const leaseRes = await (await fetch(`${BASE}/api/sync`, {
        method: "POST", headers: H3,
        body: JSON.stringify({ action: "lease", schoolId: school.id, size: 25, device: { code: `SMOKE-${Date.now()}`, name: "smoke reserve probe", kind: "POS_TABLET" } }),
      })).json();
      check("reserve grant returns a 25-number block", !!leaseRes?.lease?.tag && leaseRes.lease.from > 0 && leaseRes.lease.to - leaseRes.lease.from + 1 === 25, JSON.stringify(leaseRes));
      const DEV = leaseRes.deviceCode as string;
      const block = leaseRes.lease as { tag: string; from: number; to: number };
      //     … network dies · collect OFFLINE spending the block (client cursor
      //     logic inline — numbers burn in order, never reused) …
      let cursor = block.from;
      const take = () => `RCP-${YEAR}-${String(cursor++).padStart(5, "0")}`;
      const offlineCodes = [take(), take(), take(), take(), take()];
      //     … another clerk keeps minting ONLINE meanwhile …
      const onlineAfter = await nextReceiptNo();
      check("online mints after the grant stay ABOVE the offline block", seqOf(onlineAfter) > block.to, `${onlineAfter} vs block ≤ ${block.to}`);
      //     … network returns → flush adopts the printed codes EXACTLY
      const opA = { clientId: `smoke-lease-a-${Date.now()}`, kind: "payment", payload: { schoolId: school.id, invoiceId: inv3.id, amountCents: amt, method: "CASH", category: "INVOICE", receiptNo: offlineCodes[0], leaseTag: block.tag, leaseSeq: block.from } };
      const fA = await (await fetch(`${BASE}/api/sync`, { method: "POST", headers: H3, body: JSON.stringify({ ops: [opA], device: { code: DEV, name: "smoke reserve probe", kind: "POS_TABLET" } }) })).json();
      check("flush adopts the receipt code printed offline", fA.results?.[0]?.ok === true && (fA.results?.[0]?.detail ?? "").includes(offlineCodes[0]), JSON.stringify(fA.results?.[0]));
      const adopted = await db.payment.findUnique({ where: { receiptNo: offlineCodes[0] } });
      check("payment row carries EXACTLY the offline-printed code", !!adopted && adopted.syncClientId === opA.clientId, adopted?.receiptNo);
      const leaseRow = await db.codeLease.findUnique({ where: { id: block.tag } });
      check("reserve spent counter advanced", leaseRow?.spentCount === 1, `spent=${leaseRow?.spentCount}`);

      // (c) DURABILITY — retry storm: re-flush the SAME op after a lost
      //     response → unique syncClientId trips → "already synced", one receipt
      const fA2 = await (await fetch(`${BASE}/api/sync`, { method: "POST", headers: H3, body: JSON.stringify({ ops: [opA], device: { code: DEV } }) })).json();
      check("re-flush of the offline op is idempotent", fA2.results?.[0]?.ok === true && fA2.results?.[0]?.detail === "already synced", JSON.stringify(fA2.results?.[0]));
      check("exactly ONE payment for the retried clientId", (await db.payment.findMany({ where: { syncClientId: opA.clientId } })).length === 1);

      // (d) a forged/foreign lease tag is refused — the server mints its own
      const opB = { clientId: `smoke-lease-b-${Date.now()}`, kind: "payment", payload: { schoolId: school.id, invoiceId: inv3.id, amountCents: amt, method: "CASH", category: "INVOICE", receiptNo: offlineCodes[1], leaseTag: "not-a-real-lease" } };
      const fB = await (await fetch(`${BASE}/api/sync`, { method: "POST", headers: H3, body: JSON.stringify({ ops: [opB], device: { code: DEV } }) })).json();
      const payB = await db.payment.findFirst({ where: { syncClientId: opB.clientId } });
      check("bogus lease tag → server mints its own code (adoption refused)", fB.results?.[0]?.ok === true && !!payB && payB.receiptNo !== offlineCodes[1], `${payB?.receiptNo} vs ${offlineCodes[1]}`);

      // (e) exhausted reserve → the POS queues with no preassigned code →
      //     flush mints a fresh server code — still never a duplicate
      cursor = block.to + 1; // burn the remainder (harmless gaps, never dupes)
      const opC = { clientId: `smoke-lease-c-${Date.now()}`, kind: "payment", payload: { schoolId: school.id, invoiceId: inv3.id, amountCents: amt, method: "CASH", category: "INVOICE" } };
      const fC = await (await fetch(`${BASE}/api/sync`, { method: "POST", headers: H3, body: JSON.stringify({ ops: [opC], device: { code: DEV } }) })).json();
      const payC = await db.payment.findFirst({ where: { syncClientId: opC.clientId } });
      check("exhausted reserve still yields a fresh unique server code", fC.results?.[0]?.ok === true && /^RCP-\d{4}-\d{5}$/.test(payC?.receiptNo ?? ""), payC?.receiptNo);

      // (f) the global invariant — every RCP code ever minted is unique
      const allRcp = await db.payment.findMany({ where: { receiptNo: { startsWith: `RCP-${YEAR}-` } }, select: { receiptNo: true } });
      check("every RCP code in the database is unique", new Set(allRcp.map((r) => r.receiptNo)).size === allRcp.length);
      const interleave = [...onlineBefore, ...offlineCodes, onlineAfter, payB!.receiptNo, payC!.receiptNo];
      check("online + offline interleaved codes are all distinct", new Set(interleave).size === interleave.length, JSON.stringify(interleave));

      // ── cleanup: remove probe payments/leases/device/journal, restore state ──
      await db.payment.deleteMany({ where: { syncClientId: { in: [opA.clientId, opB.clientId, opC.clientId] } } });
      await db.invoice.update({ where: { id: inv3.id }, data: { paidCents: snap.paidCents, status: snap.status } });
      await db.codeLease.deleteMany({ where: { deviceCode: DEV } });
      await db.syncEvent.deleteMany({ where: { deviceCode: DEV } });
      await db.syncDevice.deleteMany({ where: { code: DEV } });
      if (preRcp !== undefined) await db.codeCounter.update({ where: { scope: `RCP:${YEAR}` }, data: { next: preRcp } });
      else await db.codeCounter.deleteMany({ where: { scope: `RCP:${YEAR}` } });
      check("cleanup — probe receipts, leases and device removed",
        (await db.payment.findMany({ where: { syncClientId: { in: [opA.clientId, opB.clientId, opC.clientId] } } })).length === 0
        && (await db.codeLease.findMany({ where: { deviceCode: DEV } })).length === 0
        && (await db.syncDevice.findMany({ where: { code: DEV } })).length === 0);
    }
  }

  console.log("\n══ 7. Offline reserve for invoices + student indexes (wave 42) — INV · NYIDX ══");
  if (!user || !school) console.log("missing demo users — skipping wave-42 reserve test");
  else {
    const term7 = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId: school.id } } });
    const fsClassroom = term7
      ? await db.classroom.findFirst({ where: { schoolId: school.id, feeStructures: { some: { termId: term7.id } } } })
      : await db.classroom.findFirst({ where: { schoolId: school.id } });
    if (!fsClassroom || !term7) console.log("no classroom/term — skipping wave-42 reserve test");
    else {
      const { nextInvoiceNo, nextStudentIndex, leaseSeqBlock } = await import("../src/lib/codes");
      const H7 = { "content-type": "application/json", "x-user-id": user.id };
      const preStudents7 = await db.student.count();
      const preSchool7 = await db.school.findUnique({ where: { id: school.id }, select: { studentsOnRoll: true } });
      // snapshot the counters this section touches — restored in cleanup
      const scopes7 = [`INV:${YEAR}`, "NYIDX:2016", "NYIDX:2018"];
      const snap7 = new Map((await db.codeCounter.findMany({ where: { scope: { in: scopes7 } } })).map((c) => [c.scope, c.next]));

      // (a) INV disjointness — lease blocks vs racing single mints
      const [ib1, ib2, ib3, invSingles] = await Promise.all([
        leaseSeqBlock("INV", 20, YEAR),
        leaseSeqBlock("INV", 20, YEAR),
        leaseSeqBlock("INV", 20, YEAR),
        Promise.all(Array.from({ length: 6 }, () => nextInvoiceNo())),
      ]);
      const iblocks = [ib1, ib2, ib3];
      const invSeqOf = (code: string) => Number(code.slice(`INV-${YEAR}-`.length));
      check("3 concurrently granted INV blocks are pairwise disjoint",
        iblocks.every((b, i) => iblocks.every((o, j) => i === j || b.to < o.from || o.to < b.from)), JSON.stringify(iblocks));
      check("online invoice mints racing the grants stay outside every block",
        invSingles.every((s) => iblocks.every((b) => invSeqOf(s) < b.from || invSeqOf(s) > b.to)), JSON.stringify(invSingles));

      // (b) NYIDX cohort scoping — blocks within ONE birth-year cohort are
      //     carved from that cohort's own counter and never overlap; a
      //     different cohort (2018) can share the same seq range harmlessly
      //     because its codes carry the NY-2018- prefix (different scope).
      const [n2016a, n2016b, n2018, nSingles] = await Promise.all([
        leaseSeqBlock("NYIDX", 10, 2016),
        leaseSeqBlock("NYIDX", 10, 2016),
        leaseSeqBlock("NYIDX", 10, 2018),
        Promise.all(Array.from({ length: 4 }, () => nextStudentIndex(2016))),
      ]);
      check("two concurrently granted 2016-cohort blocks never overlap",
        n2016a.to < n2016b.from || n2016b.to < n2016a.from, `a:${n2016a.from}-${n2016a.to} b:${n2016b.from}-${n2016b.to}`);
      const nySeqOf = (code: string) => Number(code.slice("NY-2016-".length));
      check("online index mints (born 2016) stay outside the 2016 cohort blocks",
        nSingles.every((s) => [n2016a, n2016b].every((b) => nySeqOf(s) < b.from || nySeqOf(s) > b.to)), JSON.stringify(nSingles));
      void n2018; // cross-cohort seq ranges may coincide — prefixes differ, never a collision

      // (c) ADOPTION at enrolment flush — the blackout scenario for enrolments:
      //     device reserves a 2016 cohort block + an INV block while online …
      //     (the device registers on the lease call and the RESPONSE carries
      //     the minted DEV-NNNN code — every later call must use THAT code,
      //     exactly like the real client does)
      const leaseRes7 = await (await fetch(`${BASE}/api/sync`, {
        method: "POST", headers: H7,
        body: JSON.stringify({ action: "lease", schoolId: school.id, kind: "NYIDX", year: 2016, size: 15, device: { code: `SMOKE42-${Date.now()}`, name: "wave-42 probe", kind: "POS_TABLET" } }),
      })).json();
      const DEV42 = leaseRes7.deviceCode as string;
      const leaseRes7b = await (await fetch(`${BASE}/api/sync`, {
        method: "POST", headers: H7,
        body: JSON.stringify({ action: "lease", schoolId: school.id, kind: "INV", size: 20, device: { code: DEV42, name: "wave-42 probe", kind: "POS_TABLET" } }),
      })).json();
      check("lease action honours kind=NYIDX + birth-year cohort", leaseRes7?.lease?.kind === "NYIDX" && leaseRes7?.lease?.year === 2016 && leaseRes7.lease.to - leaseRes7.lease.from + 1 === 15, JSON.stringify(leaseRes7?.lease));
      check("lease action honours kind=INV", leaseRes7b?.lease?.kind === "INV" && leaseRes7b.lease.to - leaseRes7b.lease.from + 1 === 20, JSON.stringify(leaseRes7b?.lease));
      const idxBlock = leaseRes7.lease as { tag: string; from: number; to: number };
      const invBlock = leaseRes7b.lease as { tag: string; from: number; to: number };
      //     … network dies · enrol OFFLINE spending both blocks …
      let idxCursor = idxBlock.from;
      let invCursor = invBlock.from;
      const takeIdx = () => `NY-2016-${String(idxCursor++).padStart(6, "0")}`;
      const takeInv = () => `INV-${YEAR}-${String(invCursor++).padStart(4, "0")}`;
      const preIdx = takeIdx();
      const preInvNo = takeInv();
      const op42 = {
        clientId: `smoke-w42-${Date.now()}`,
        kind: "student",
        payload: { schoolId: school.id, classroomId: fsClassroom.id, classroomName: fsClassroom.name, firstName: "Wave", lastName: "FortyTwo", gender: "F", dateOfBirth: "2016-05-01", guardianName: "Probe Guardian", guardianPhone: "+233500000002", nyansapoIndex: preIdx, indexLeaseTag: idxBlock.tag, invoiceNo: preInvNo, invoiceLeaseTag: invBlock.tag },
      };
      const f42 = await (await fetch(`${BASE}/api/sync`, { method: "POST", headers: H7, body: JSON.stringify({ ops: [op42], device: { code: DEV42, name: "wave-42 probe", kind: "POS_TABLET" } }) })).json();
      check("enrolment flush adopts BOTH preassigned codes", f42.results?.[0]?.ok === true && (f42.results?.[0]?.detail ?? "").includes("(preassigned)"), JSON.stringify(f42.results?.[0]));
      const stu42 = await db.student.findFirst({ where: { syncClientId: op42.clientId } });
      check("student carries EXACTLY the offline-printed index", stu42?.nyansapoIndex === preIdx, `${stu42?.nyansapoIndex} vs ${preIdx}`);
      const inv42 = await db.invoice.findFirst({ where: { studentId: stu42?.id } });
      check("first-term bill carries EXACTLY the offline-printed INV number", inv42?.invoiceNo === preInvNo, `${inv42?.invoiceNo} vs ${preInvNo}`);
      const idxLeaseRow = await db.codeLease.findUnique({ where: { id: idxBlock.tag } });
      const invLeaseRow = await db.codeLease.findUnique({ where: { id: invBlock.tag } });
      check("both reserves' spent counters advanced", idxLeaseRow?.spentCount === 1 && invLeaseRow?.spentCount === 1, `idx=${idxLeaseRow?.spentCount} inv=${invLeaseRow?.spentCount}`);
      check("flush grants no new blocks while runways are healthy", !Array.isArray(f42.leases) || f42.leases.length === 0, JSON.stringify(f42.leases ?? []));

      // (d) DURABILITY — re-flush the same enrolment op → exactly one student
      const f42b = await (await fetch(`${BASE}/api/sync`, { method: "POST", headers: H7, body: JSON.stringify({ ops: [op42], device: { code: DEV42 } }) })).json();
      check("re-flush of the offline enrolment is idempotent", f42b.results?.[0]?.detail === "already synced", JSON.stringify(f42b.results?.[0]));
      check("exactly ONE student row for the retried clientId", (await db.student.findMany({ where: { syncClientId: op42.clientId } })).length === 1);

      // (d2) RUNWAY REPLENISHMENT — burn the 2016 cohort block below the
      //      runway threshold (spent ≥ 6 of 15), then a fresh offline
      //      enrolment (fallback mint) flushes → the server tops THAT cohort
      //      back up and the new block rides on the response
      await db.codeLease.update({ where: { id: idxBlock.tag }, data: { spentCount: 8 } }); // remaining 7 < 10
      const op42d = { clientId: `smoke-w42d-${Date.now()}`, kind: "student", payload: { schoolId: school.id, classroomId: fsClassroom.id, classroomName: fsClassroom.name, firstName: "Wave", lastName: "Runway", gender: "M", dateOfBirth: "2016-07-01", guardianName: "Probe Guardian", guardianPhone: "+233500000004" } };
      const f42d = await (await fetch(`${BASE}/api/sync`, { method: "POST", headers: H7, body: JSON.stringify({ ops: [op42d], device: { code: DEV42 } }) })).json();
      check("low cohort runway → flush auto-grants a fresh NYIDX block",
        f42d.results?.[0]?.ok === true && Array.isArray(f42d.leases) && f42d.leases.some((l: any) => l.kind === "NYIDX" && l.year === 2016), JSON.stringify(f42d.leases ?? []));
      const stu42d = await db.student.findFirst({ where: { syncClientId: op42d.clientId } });
      check("fallback-mint enrolment still carries a real unique index", /^NY-2016-\d{6}$/.test(stu42d?.nyansapoIndex ?? ""), stu42d?.nyansapoIndex);

      // (e) WRONG-COHORT REJECTION — a 2018-cohort lease tag vouching for a
      //     2016-coded index is refused; the server mints its own
      const lease18 = await (await fetch(`${BASE}/api/sync`, {
        method: "POST", headers: H7,
        body: JSON.stringify({ action: "lease", schoolId: school.id, kind: "NYIDX", year: 2018, size: 10, device: { code: DEV42, name: "wave-42 probe", kind: "POS_TABLET" } }),
      })).json();
      const forgedIdx = `NY-2016-${String(lease18.lease.from).padStart(6, "0")}`;
      const op42c = { clientId: `smoke-w42c-${Date.now()}`, kind: "student", payload: { schoolId: school.id, classroomId: fsClassroom.id, classroomName: fsClassroom.name, firstName: "Wave", lastName: "Forged", gender: "M", dateOfBirth: "2016-03-01", guardianName: "Probe Guardian", guardianPhone: "+233500000003", nyansapoIndex: forgedIdx, indexLeaseTag: lease18.lease.tag } };
      const f42c = await (await fetch(`${BASE}/api/sync`, { method: "POST", headers: H7, body: JSON.stringify({ ops: [op42c], device: { code: DEV42 } }) })).json();
      const stu42c = await db.student.findFirst({ where: { syncClientId: op42c.clientId } });
      check("wrong-cohort lease tag → adoption refused, server mints", f42c.results?.[0]?.ok === true && !!stu42c && stu42c.nyansapoIndex !== forgedIdx && /^NY-2016-\d{6}$/.test(stu42c.nyansapoIndex), `${stu42c?.nyansapoIndex} vs forged ${forgedIdx}`);

      // (f) the global invariant — every NY and INV code ever minted is unique
      const allNy = await db.student.findMany({ select: { nyansapoIndex: true } });
      check("every student index in the database is unique", new Set(allNy.map((s) => s.nyansapoIndex)).size === allNy.length);
      const allInv = await db.invoice.findMany({ select: { invoiceNo: true } });
      check("every invoice number in the database is unique", new Set(allInv.map((i) => i.invoiceNo)).size === allInv.length);

      // ── cleanup: remove probe students/invoices/leases/device, restore counters ──
      const probeIds = [stu42?.id, stu42c?.id, stu42d?.id].filter(Boolean) as string[];
      const probeInvs = await db.invoice.findMany({ where: { studentId: { in: probeIds } } });
      for (const pi of probeInvs) { await db.invoiceItem.deleteMany({ where: { invoiceId: pi.id } }); await db.invoice.delete({ where: { id: pi.id } }); }
      await db.enrollment.deleteMany({ where: { studentId: { in: probeIds } } });
      await db.student.deleteMany({ where: { id: { in: probeIds } } });
      await db.school.update({ where: { id: school.id }, data: { studentsOnRoll: preSchool7!.studentsOnRoll } });
      await db.codeLease.deleteMany({ where: { deviceCode: DEV42 } });
      await db.syncEvent.deleteMany({ where: { deviceCode: DEV42 } });
      await db.syncDevice.deleteMany({ where: { code: DEV42 } });
      for (const scope of scopes7) {
        if (snap7.has(scope)) await db.codeCounter.update({ where: { scope }, data: { next: snap7.get(scope)! } });
        else await db.codeCounter.deleteMany({ where: { scope } });
      }
      check("cleanup — probe students, invoices, leases and device removed",
        (await db.student.findMany({ where: { id: { in: probeIds } } })).length === 0
        && (await db.codeLease.findMany({ where: { deviceCode: DEV42 } })).length === 0
        && (await db.syncDevice.findMany({ where: { code: DEV42 } })).length === 0
        && (await db.student.count()) === preStudents7);
    }
  }

  console.log(`\n${pass} passed, ${fail} failed`);
  process.exit(fail ? 1 : 0);
}
main();

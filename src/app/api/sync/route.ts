import { NextRequest, NextResponse } from "next/server";
import { Prisma } from "@prisma/client";
import { db } from "@/lib/db";
import { getUser } from "@/lib/session";
import { nextInvoiceNo, nextReceiptNo, nextStudentIndex, leaseSeqBlock } from "@/lib/codes";
import { sanitizeCollectItems, collectDailyItems, markPresentForDate, applyTransportUsage, TRANSPORT_USAGES, type TransportUsage } from "@/lib/daily-billing";
import { sendAutomated, smsEventEnabled } from "@/lib/sms";

// POST /api/sync — the offline pipeline's write side.
//
// wave 39: the flush is a supervised first-class citizen of the Sync Center
// (CI3 sync push endpoint parity):
//   · the client identifies its device ({ code?, name, kind }); unknown codes
//     auto-register (register_device parity) and the minted code is returned
//   · a REVOKED device is refused outright (403) — the Sync Center kill switch
//   · per-kind sync policy (SchoolSetting sync.policy) gates each op — a
//     disabled kind is refused and journaled FAILED (sync_table_management parity)
//   · marks/attendance replays that collide with newer server state file a
//     SyncConflict (both sides frozen) instead of silently clobbering
//   · every flush lands in the sync journal (SyncEvent) with counts + duration
//
// wave 41 — collision-free codes across offline/online toggling (the project's
// load-bearing guarantee). Every minting path is now backed by the database,
// not by check-then-create:
//   · payment/attendance rows carry the queue clientId as syncClientId
//     (@unique) — a retry storm (request lands, response lost, client retries)
//     trips P2002 and is recognised as "already synced", never double-charging
//   · offline receipts may carry a PREASSIGNED receiptNo minted from the
//     device's offline code reserve (CodeLease block). The flush validates
//     ownership — the seq must fall inside a block granted to THIS device —
//     before adopting it; a receiptNo unique violation (pathological tab race)
//     falls back to a fresh server mint
//   · a flush that included payment ops tops the device's reserve back up
//     automatically when its runway is low, returning the new block so the
//     client keeps printing real receipt numbers offline
//
// wave 42 — the reserve generalises beyond receipts (the client asked for
// "student id, invoice id, payment id … every code, offline-safe"):
//   · CodeLease.kind now spans RCP receipts, INV invoice numbers and NYIDX
//     national student indexes — each from its own atomic counter scope
//   · an offline ENROLMENT op may preassign the student's NY index (from a
//     birth-year cohort block — the index is registry-scoped) AND the first-
//     term bill's INV number; the flush adopts them under the same ownership
//     validation as receipts, with the @unique constraints + server re-mint
//     as the final backstop
//   · a flush that included enrolment ops tops the NYIDX + INV reserves back
//     up too; the response carries `leases` (array) alongside the legacy
//     single `lease` (RCP) for older clients
type FlushOp = { clientId: string; kind: string; payload: any; label?: string; queuedAt?: number };

type LeaseKind = "RCP" | "INV" | "NYIDX";
const LEASE_KINDS: LeaseKind[] = ["RCP", "INV", "NYIDX"];
const KIND_LABEL: Record<LeaseKind, string> = { RCP: "receipt", INV: "invoice", NYIDX: "student-index" };

/** Canonical string form of a sequence number for each code kind —
 *  RCP-YYYY-NNNNN · INV-YYYY-NNNN · NY-YYYY-NNNNNN. */
function formatCode(kind: LeaseKind, year: number, seq: number): string {
  if (kind === "INV") return `INV-${year}-${String(seq).padStart(4, "0")}`;
  if (kind === "NYIDX") return `NY-${year}-${String(seq).padStart(6, "0")}`;
  return `RCP-${year}-${String(seq).padStart(5, "0")}`;
}

function codeRegex(kind: LeaseKind): RegExp {
  if (kind === "INV") return /^INV-(\d{4})-(\d{4,6})$/;
  if (kind === "NYIDX") return /^NY-(\d{4})-(\d{6})$/;
  return /^RCP-(\d{4})-(\d{5})$/;
}

async function syncPolicy(schoolId: string): Promise<Record<string, boolean>> {
  const row = await db.schoolSetting.findUnique({ where: { schoolId_key: { schoolId, key: "sync.policy" } } });
  if (!row) return { payment: true, attendance: true, marks: true, student: true };
  const parsed = JSON.parse(row.value) as Record<string, boolean>;
  return { payment: true, attendance: true, marks: true, student: true, ...parsed };
}

/** Journal a data-pack pull (read-side sync). Looks the device up, minting it on
 *  its first pull — a read-mostly tablet registers here rather than at flush.
 *  Best-effort: failures return null, never breaking the caller. */
async function journalPull(
  req: NextRequest,
  user: { id: string; fullName: string; schoolIds: string[] },
  schoolId: string,
  device: { code?: string; name?: string; kind?: string } | undefined,
  stats: { sections: number; durationMs: number }
): Promise<string | null> {
  try {
    let row = device?.code ? await db.syncDevice.findFirst({ where: { code: device.code, schoolId } }) : null;
    if (!row) {
      const count = await db.syncDevice.count({ where: { schoolId } });
      const code = `DEV-${String(count + 1).padStart(4, "0")}`;
      const kind = ["POS_TABLET", "SCANNER", "FRONT_DESK", "BROWSER", "OTHER"].includes(device?.kind ?? "") ? device!.kind! : "BROWSER";
      row = await db.syncDevice.create({ data: { schoolId, code, name: device?.name ?? "Unnamed device", kind } });
    }
    const ip = (req.headers.get("x-forwarded-for") ?? "").split(",")[0].trim() || null;
    await db.syncEvent.create({
      data: {
        schoolId, deviceCode: row.code, userName: user.fullName, kind: "PULL",
        detail: stats.sections
          ? `${stats.sections} data-pack section(s) refreshed`
          : "data pack warmed (no sections yet)",
        durationMs: stats.durationMs || null,
      },
    });
    await db.syncDevice.update({
      where: { id: row.id },
      data: { lastSeenAt: new Date(), lastIP: ip, lastUser: user.fullName, ...(stats.sections ? { lastSyncAt: new Date() } : {}) },
    });
    return row.code;
  } catch {
    return null;
  }
}

/** Resolve (or self-register) the flushing/reserving device for a school.
 *  Returns the SyncDevice row, or null when the device is REVOKED (caller
 *  refuses) or the school is out of scope. */
async function resolveDevice(
  user: { fullName: string; schoolIds: string[] },
  schoolId: string,
  device: { code?: string; name?: string; kind?: string } | undefined,
): Promise<{ row: { id: string; code: string; status: string; schoolId: string } | null; revoked?: string; minted?: boolean }> {
  if (!schoolId || !user.schoolIds.includes(schoolId)) return { row: null };
  if (device?.code) {
    const found = await db.syncDevice.findFirst({ where: { code: device.code, schoolId } });
    if (found && found.status === "REVOKED") return { row: null, revoked: found.code };
    if (found) return { row: { id: found.id, code: found.code, status: found.status, schoolId } };
  }
  // first flush from this browser: register it (CI3 register_device parity).
  // Mint a DEV-NNNN code. Count-based numbering is NOT deletion-safe (a
  // revoked-and-removed device frees a number that was already taken, and
  // parallel registrations race), so derive from the HIGHEST existing code
  // and retry the rare P2002 — a device registration must never 500 the
  // flush that triggered it.
  const kind = ["POS_TABLET", "SCANNER", "FRONT_DESK", "BROWSER", "OTHER"].includes(device?.kind ?? "") ? device!.kind! : "BROWSER";
  const name = device?.name ?? "Unnamed device";
  const existing = await db.syncDevice.findMany({ select: { code: true } });
  const maxNum = existing.reduce((m, d) => {
    const t = /^DEV-(\d+)$/.exec(d.code);
    return t ? Math.max(m, Number(t[1])) : m;
  }, 0);
  let created: { id: string; code: string; status: string; name: string } | null = null;
  let code = "";
  for (let attempt = 0; attempt < 5 && !created; attempt++) {
    code = `DEV-${String(maxNum + 1 + attempt).padStart(4, "0")}`;
    try {
      created = await db.syncDevice.create({ data: { schoolId, code, name, kind } });
    } catch (e) {
      if (!(e instanceof Prisma.PrismaClientKnownRequestError && e.code === "P2002" && p2002Target(e).includes("code"))) throw e;
      // another registration took that number — try the next one
    }
  }
  if (!created) {
    // pathological (five concurrent races): a collision-proof suffix
    code = `DEV-X${Date.now().toString(36).toUpperCase().slice(-6)}`;
    created = await db.syncDevice.create({ data: { schoolId, code, name, kind } });
  }
  await db.syncEvent.create({ data: { schoolId, deviceCode: code, userName: user.fullName, kind: "REGISTER", detail: `${created.name} (${kind}) self-registered on first flush` } });
  return { row: { id: created.id, code, status: created.status, schoolId }, minted: true };
}

/** Which unique constraint tripped a P2002? (Prisma reports the target as a
 *  string array or a field-keyed object depending on the engine.) */
function p2002Target(e: unknown): string {
  return JSON.stringify((e as { meta?: { target?: unknown } })?.meta?.target ?? "");
}

/** Reserve a block of real codes (receipts, invoices or student indexes —
 *  wave 42) for one device (CodeLease) and journal the grant. The atomic
 *  counter of that kind's scope jumps past the whole block, so online
 *  minters and other devices' reserves can never overlap it. NYIDX blocks
 *  are carved per BIRTH-YEAR cohort (registry semantics) — pass `year` for
 *  a cohort other than the current calendar year. */
async function grantLease(
  schoolId: string,
  deviceCode: string,
  userName: string,
  size: number,
  kind: LeaseKind = "RCP",
  year = new Date().getFullYear(),
): Promise<{ tag: string; kind: LeaseKind; year: number; from: number; to: number; size: number }> {
  const { from, to } = await leaseSeqBlock(kind, size, year);
  const lease = await db.codeLease.create({ data: { schoolId, deviceCode, kind, year, fromSeq: from, toSeq: to } });
  try {
    await db.syncEvent.create({
      data: { schoolId, deviceCode, userName, kind: "LEASE", detail: `offline ${KIND_LABEL[kind]} reserve ${formatCode(kind, year, from)} … ${formatCode(kind, year, to)} (${to - from + 1} numbers) granted` },
    });
  } catch { /* journaling is best-effort */ }
  return { tag: lease.id, kind, year, from, to, size: to - from + 1 };
}

/** Validate + adopt a code an offline op preassigned from one of this
 *  device's reserve blocks (receipt, invoice or student index — wave 42).
 *  Returns null unless the code is well-formed AND falls inside a block
 *  granted to THIS device for THIS school IN THE CODE'S OWN YEAR — anything
 *  else (forged, stale, cross-device, wrong cohort) is ignored and the
 *  server mints. `field`/`tagField` name the payload keys carrying the
 *  preassigned code and the lease tag that vouches for it. */
async function resolvePreassignedCode(
  op: FlushOp,
  deviceCode: string | null,
  schoolId: string,
  kind: LeaseKind,
  field: string,
  tagField: string,
): Promise<{ code: string; seq: number; leaseId: string } | null> {
  const raw = op.payload?.[field];
  if (typeof raw !== "string" || !deviceCode) return null;
  const m = codeRegex(kind).exec(raw);
  if (!m) return null;
  const year = Number(m[1]);
  const seq = Number(m[2]);
  const tag = typeof op.payload?.[tagField] === "string" ? op.payload[tagField] : null;
  if (!tag) return null;
  const lease = await db.codeLease.findFirst({
    where: { id: tag, schoolId, deviceCode, kind, year, fromSeq: { lte: seq }, toSeq: { gte: seq } },
  });
  if (!lease) return null;
  return { code: raw, seq, leaseId: lease.id };
}

/** Create a payment row with durable idempotency + receipt collision fallback:
 *  P2002 on syncClientId = a racing re-flush already landed it; P2002 on
 *  receiptNo (two tabs racing one reserve) = re-mint server-side and retry. */
async function createPaymentSafely(data: Prisma.PaymentUncheckedCreateInput): Promise<{ payment: any; alreadySynced: boolean }> {
  try {
    return { payment: await db.payment.create({ data }), alreadySynced: false };
  } catch (e) {
    const target = p2002Target(e);
    if (e instanceof Prisma.PrismaClientKnownRequestError && e.code === "P2002" && target.includes("receiptNo")) {
      return { payment: await db.payment.create({ data: { ...data, receiptNo: await nextReceiptNo() } }), alreadySynced: false };
    }
    throw e;
  }
}

export async function POST(req: NextRequest) {
  const started = Date.now();
  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });
  const body = (await req.json()) as {
    ops?: FlushOp[];
    device?: { code?: string; name?: string; kind?: string };
    action?: string;
    schoolId?: string;
    sections?: number;
    durationMs?: number;
    size?: number;
    kind?: string;
    year?: number;
  };

  // ── read-side sync (wave 40): a device warmed its offline data pack — the
  // pull twin of the flush journal (CI3 sync_metrics logged both directions).
  // A pull never carries ops; it just refreshes server→device data freshness.
  if (body.action === "pull") {
    const schoolId = body.schoolId && user.schoolIds.includes(body.schoolId) ? body.schoolId : user.schoolIds[0];
    if (!schoolId) return NextResponse.json({ error: "No school scope" }, { status: 400 });
    const deviceCode = await journalPull(req, user, schoolId, body.device, {
      sections: Number(body.sections ?? 0) || 0,
      durationMs: Number(body.durationMs ?? 0) || 0,
    });
    return NextResponse.json({ ok: true, deviceCode });
  }

  // ── offline code reserve (wave 41 receipts, wave 42 invoices + indexes):
  // reserve a block of REAL codes for this device while it is online — a POS
  // tablet going to a network-dead counter, sports day or field trip keeps
  // printing final RCP/INV codes (and enrolling with real NY indexes). Each
  // kind's counter jumps past the block atomically, so no online mint or
  // other device's reserve can ever collide with the numbers it hands out
  // offline. NYIDX reserves are carved per birth-year cohort.
  if (body.action === "lease") {
    const schoolId = body.schoolId && user.schoolIds.includes(body.schoolId) ? body.schoolId : user.schoolIds[0];
    if (!schoolId) return NextResponse.json({ error: "No school scope" }, { status: 400 });
    const size = Math.min(200, Math.max(10, Number(body.size ?? 50) || 50));
    const kind: LeaseKind = LEASE_KINDS.includes(body.kind as LeaseKind) ? (body.kind as LeaseKind) : "RCP";
    const thisYear = new Date().getFullYear();
    const year = kind === "NYIDX" && Number(body.year) >= 1995 && Number(body.year) <= thisYear ? Math.floor(Number(body.year)) : thisYear;
    const resolved = await resolveDevice(user, schoolId, body.device);
    if (resolved.revoked) {
      return NextResponse.json({ error: `Device ${resolved.revoked} is revoked — reserve refused. Contact the administrator.`, deviceCode: resolved.revoked }, { status: 403 });
    }
    if (!resolved.row) return NextResponse.json({ error: "No school scope" }, { status: 400 });
    const lease = await grantLease(schoolId, resolved.row.code, user.fullName, size, kind, year);
    try {
      await db.syncDevice.update({
        where: { id: resolved.row.id },
        data: { lastSeenAt: new Date(), lastUser: user.fullName },
      });
    } catch { /* best-effort */ }
    return NextResponse.json({ ok: true, deviceCode: resolved.row.code, lease });
  }

  const ops = body.ops;
  if (!Array.isArray(ops)) return NextResponse.json({ error: "ops[] required" }, { status: 400 });
  if (!ops.length) return NextResponse.json({ results: [], syncedAt: new Date().toISOString() });

  const device = body.device;

  // ── device identity: auto-register, or refuse when revoked ──
  const firstSchoolId = ops[0].payload?.schoolId ?? user.schoolIds[0];
  let deviceCode: string | null = null;
  let deviceRow: { id: string; code: string; status: string; schoolId: string } | null = null;
  if (firstSchoolId && user.schoolIds.includes(firstSchoolId)) {
    const resolved = await resolveDevice(user, firstSchoolId, device);
    if (resolved.revoked) {
      return NextResponse.json({ error: `Device ${resolved.revoked} is revoked — flush rejected. Contact the administrator.`, deviceCode: resolved.revoked }, { status: 403 });
    }
    deviceRow = resolved.row;
    deviceCode = deviceRow?.code ?? null;
  }

  // policies per school (a flush may span schools under one operator)
  const policies = new Map<string, Record<string, boolean>>();
  for (const sid of new Set(ops.map((o) => o.payload?.schoolId).filter(Boolean) as string[])) {
    if (user.schoolIds.includes(sid)) policies.set(sid, await syncPolicy(sid));
  }

  const results: { clientId: string; ok: boolean; detail?: string }[] = [];
  let conflictsFiled = 0;
  let paymentOpsOk = 0;
  let studentOpsOk = 0;
  const idxYearsSeen = new Set<number>(); // birth-year cohorts the enrolments actually drew from

  for (const op of ops) {
    try {
      const policy = policies.get(op.payload?.schoolId);
      if (policy && !policy[op.kind]) {
        results.push({ clientId: op.clientId, ok: false, detail: `sync disabled for ${op.kind} ops` });
        continue;
      }
      if (op.kind === "payment") {
        const { schoolId, invoiceId, amountCents, method, reference, paidAt, category, studentId } = op.payload;
        if (!user.schoolIds.includes(schoolId)) throw new Error("forbidden");
        // durable idempotency (wave 41): the clientId lives on the payment row
        // as a UNIQUE syncClientId, so a re-flush — or two flushes racing
        // after a lost response — can never double-charge. The legacy
        // reference marker covers rows minted before wave 41.
        const dup = await db.payment.findFirst({ where: { OR: [{ syncClientId: op.clientId }, { reference: `SYNC:${op.clientId}` }] } });
        if (dup) { results.push({ clientId: op.clientId, ok: true, detail: "already synced" }); continue; }

        // preassigned receipt code from the device's offline reserve — adopted
        // only when it provably belongs to this device's block
        const preassigned = await resolvePreassignedCode(op, deviceCode, schoolId, "RCP", "receiptNo", "leaseTag");
        const receiptNo: string | null = preassigned?.code ?? null;
        const syncFields = {
          syncClientId: op.clientId,
          reference: `SYNC:${op.clientId}`,
        };

        if (category === "DAILY") {
          // per-item offline collection (wave 5): the POS dialog queues `items[]`
          // (one entry per fee item); replay through the SAME core as the online
          // counter — per-item FIFO settlement, excess → that item's balance as
          // advance (iEduPro §apply_payment). Legacy single-amount entries from
          // older queued clients keep the FIFO-across-items path below.
          if (Array.isArray(op.payload.items) && op.payload.items.length) {
            const sanitized = sanitizeCollectItems(op.payload.items);
            if (!sanitized.items) throw new Error(sanitized.error ?? "invalid items[]");
            const svcRows = await db.dailyService.findMany({ where: { schoolId, id: { in: sanitized.items.map((i) => i.serviceId) } } });
            if (svcRows.length !== sanitized.items.length) throw new Error("One of the fee items does not belong to this school");
            const learner = await db.student.findUnique({ where: { id: studentId } });
            if (!learner) throw new Error("learner missing");
            const day = new Date(op.payload.paidAt ?? op.payload.date ?? Date.now());
            // transport usage decides the day's fare BEFORE settlement (online parity)
            if (op.payload.transportUsage && (TRANSPORT_USAGES as readonly string[]).includes(op.payload.transportUsage)) {
              const appliedUsage = await applyTransportUsage(db, schoolId, studentId, day, op.payload.transportUsage as TransportUsage, { performedByName: `${user.fullName} (offline)`, performedByRole: user.role });
              if (op.payload.transportUsage !== "NOT_USING" && !appliedUsage) throw new Error("no active route assignment — transport fare follows the bus route");
            }
            const collected = await collectDailyItems(db, {
              schoolId, studentId, items: sanitized.items,
              services: svcRows.map((s) => ({ id: s.id, name: s.name })),
              method: op.payload.method ?? "CASH",
              reference: `SYNC:${op.clientId}`,
              receivedByName: `${user.fullName} (offline)`,
              performedByRole: user.role,
              paidAt: day, offlineQueued: true, syncedAt: new Date(),
              syncClientId: op.clientId,
              ...(receiptNo ? { receiptNo } : {}),
            });
            if (preassigned) {
              try { await db.codeLease.update({ where: { id: preassigned.leaseId }, data: { spentCount: { increment: 1 } } }); } catch { /* advisory */ }
            }
            const present = op.payload.markPresent ? await markPresentForDate(db, { schoolId, studentId, date: day }) : null;
            // auto receipt SMS — same trigger as the counter, fires when the queue flushes
            // gated by the daily-fee payment event (evtDailyFeesPaid)
            const setting = await db.smsSetting.findUnique({ where: { schoolId } });
            if (setting?.autoPaymentReceipts && (await smsEventEnabled(db, schoolId, "DAILY_FEES_PAID"))) {
              await sendAutomated({
                db, schoolId, trigger: "PAYMENT_RECEIVED",
                defaultBody: `We have received ₵${(collected.payment.amountCents / 100).toFixed(2)} for ${learner.firstName} (${collected.payment.receiptNo}) towards daily services. Thank you.`,
                vars: { student: learner.firstName, amount: `₵${(collected.payment.amountCents / 100).toFixed(2)}`, receipt: collected.payment.receiptNo, date: new Date().toLocaleDateString("en-GB") },
                studentId, recipientName: learner.guardianName, recipientPhone: learner.guardianPhone, sentByName: `${user.fullName} (offline)`,
              });
            }
            results.push({ clientId: op.clientId, ok: true, detail: `daily receipt ${collected.payment.receiptNo} (${collected.payment.paymentType})${present ? ` · present ${present}` : ""}` });
            paymentOpsOk++;
            continue;
          }

          // POS collection on daily services (canteen, water, classes) — legacy shape
          const owed = await db.dailyCharge.aggregate({ where: { studentId, status: "OWED" }, _sum: { amountCents: true } });
          const owedCents = owed._sum.amountCents ?? 0;
          if (owedCents <= 0) throw new Error("no daily balance");
          const amt = Math.max(0, Math.min(Number(amountCents), owedCents));
          const created = await createPaymentSafely({
            receiptNo: receiptNo ?? await nextReceiptNo(),
            studentId, category: "DAILY", schoolId,
            amountCents: amt, method, ...syncFields,
            receivedByName: `${user.fullName} (offline)`,
            paidAt: paidAt ? new Date(paidAt) : new Date(), offlineQueued: true, syncedAt: new Date(),
          });
          if (preassigned) {
            try { await db.codeLease.update({ where: { id: preassigned.leaseId }, data: { spentCount: { increment: 1 } } }); } catch { /* advisory */ }
          }
          const payment = created.payment;
          let remaining = amt;
          const owedCharges = await db.dailyCharge.findMany({ where: { studentId, status: "OWED" }, orderBy: { date: "asc" } });
          for (const c of owedCharges) {
            if (remaining <= 0) break;
            if (remaining >= c.amountCents) {
              await db.dailyCharge.update({ where: { id: c.id }, data: { status: "PAID", paymentId: payment.id } });
              remaining -= c.amountCents;
            } else {
              const paidPart = remaining;
              await db.dailyCharge.update({ where: { id: c.id }, data: { status: "PAID", paymentId: payment.id, amountCents: paidPart } });
              await db.dailyCharge.create({ data: { serviceId: c.serviceId, studentId, schoolId, date: c.date, amountCents: c.amountCents - paidPart, status: "OWED" } });
              remaining = 0;
            }
          }
          results.push({ clientId: op.clientId, ok: true, detail: `daily receipt ${payment.receiptNo} issued` });
          paymentOpsOk++;
          continue;
        }

        const inv = await db.invoice.findUnique({ where: { id: invoiceId } });
        if (!inv) throw new Error("invoice missing");
        const amt = Math.max(0, Math.min(Number(amountCents), inv.totalCents - inv.discountCents - inv.paidCents));
        const created = await createPaymentSafely({
          receiptNo: receiptNo ?? await nextReceiptNo(),
          invoiceId, studentId: inv.studentId, schoolId,
          amountCents: amt, method, ...syncFields,
          receivedByName: `${user.fullName} (offline)`,
          paidAt: paidAt ? new Date(paidAt) : new Date(), offlineQueued: true, syncedAt: new Date(),
        });
        if (preassigned) {
          try { await db.codeLease.update({ where: { id: preassigned.leaseId }, data: { spentCount: { increment: 1 } } }); } catch { /* advisory */ }
        }
        const paid = inv.paidCents + amt;
        await db.invoice.update({ where: { id: invoiceId }, data: { paidCents: paid, status: paid >= inv.totalCents - inv.discountCents ? "PAID" : "PARTIAL" } });
        results.push({ clientId: op.clientId, ok: true, detail: `receipt ${created.payment.receiptNo} issued` });
        paymentOpsOk++;
      } else if (op.kind === "attendance") {
        const { schoolId, classroomId, date, records } = op.payload;
        if (!user.schoolIds.includes(schoolId)) throw new Error("forbidden");
        // durable idempotency: syncClientId on the session (wave 41) + the
        // legacy takenByName marker for pre-wave-41 rows
        const dup = await db.attendanceSession.findFirst({ where: { OR: [{ syncClientId: op.clientId }, { takenByName: { contains: `SYNC:${op.clientId}` } }] } });
        if (dup) { results.push({ clientId: op.clientId, ok: true, detail: "already synced" }); continue; }
        // wave 39 conflict gate: a session for this class + date was already
        // taken online (or by another flush) after this op was queued — replaying
        // would mint a SECOND session for the same register. File it instead.
        const clash = await db.attendanceSession.findFirst({
          where: { classroomId, date: new Date(date) },
          include: { records: { select: { studentId: true, status: true, excusedReason: true } } },
        });
        if (clash) {
          const classroom = await db.classroom.findUnique({ where: { id: classroomId }, select: { name: true } });
          await db.syncConflict.create({ data: {
            schoolId,
            deviceCode,
            localUser: user.fullName,
            table: "attendance",
            recordKey: `${classroom?.name ?? "Class"} · ${new Date(date).toDateString()}`,
            localState: JSON.stringify({ classroomId, date, records }),
            remoteState: JSON.stringify({ sessionId: clash.id, takenByName: clash.takenByName, records: clash.records }),
            localAt: new Date(op.queuedAt ?? Date.now()),
            remoteAt: clash.date ?? new Date(),
          } });
          conflictsFiled++;
          results.push({ clientId: op.clientId, ok: true, detail: `conflict filed — register already taken online (${clash.records.length} on server)` });
          continue;
        }
        const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
        const session = await db.attendanceSession.create({ data: { classroomId, schoolId, termId: term!.id, date: new Date(date), takenByName: `${user.fullName} (SYNC:${op.clientId})`, syncClientId: op.clientId } });
        for (const r of records) await db.attendanceRecord.create({ data: { sessionId: session.id, studentId: r.studentId, status: r.status, excusedReason: r.excusedReason ?? null, markedOffline: true } });
        results.push({ clientId: op.clientId, ok: true, detail: `${records.length} marked` });
      } else if (op.kind === "marks") {
        const { schoolId, assessmentId, marks } = op.payload;
        if (!user.schoolIds.includes(schoolId)) throw new Error("forbidden");
        // wave 39 conflict gate: the server's marks for this assessment were
        // re-entered after this op was queued (another teacher / an online
        // correction). Replaying would silently clobber them — file instead.
        const existing = await db.mark.findMany({ where: { assessmentId }, select: { studentId: true, score: true, enteredOffline: true } });
        const queued = new Map((marks as { studentId: string; score: number | null | string }[]).map((m) => [m.studentId, (m.score === "" || m.score === undefined ? null : m.score) as number | null]));
        const differs = (existing.some((e) => (queued.get(e.studentId) ?? null) !== (e.score ?? null)) || [...queued.keys()].some((sid) => !existing.some((e) => e.studentId === sid)));
        if (existing.length && differs) {
          await db.syncConflict.create({ data: {
            schoolId,
            deviceCode,
            localUser: user.fullName,
            table: "marks",
            recordKey: op.label ?? `Assessment ${assessmentId.slice(-6).toUpperCase()}`,
            localState: JSON.stringify({ schoolId, assessmentId, marks }),
            remoteState: JSON.stringify({ marks: existing }),
            localAt: new Date(op.queuedAt ?? Date.now()),
            remoteAt: new Date(),
          } });
          conflictsFiled++;
          results.push({ clientId: op.clientId, ok: true, detail: `conflict filed — marks re-entered online (${existing.length} server rows)` });
          continue;
        }
        await db.mark.deleteMany({ where: { assessmentId } });
        for (const m of marks) if (m.score !== null && m.score !== "") await db.mark.create({ data: { assessmentId, studentId: m.studentId, score: Number(m.score), enteredOffline: true } });
        results.push({ clientId: op.clientId, ok: true, detail: "marks saved" });
      } else if (op.kind === "student") {
        // enrollment captured offline — replayed through the same logic as the online path
        const p = op.payload;
        if (!user.schoolIds.includes(p.schoolId)) throw new Error("forbidden");
        // durable idempotency: the clientId lives on the student row itself, so
        // a re-flush (or two flushes racing) can never create a duplicate — the
        // earlier SYNC-<clientId> index trick broke the moment the index was
        // upgraded to NY-…, letting a re-flush mint a second student.
        const dup = await db.student.findFirst({ where: { syncClientId: op.clientId } });
        if (dup) { results.push({ clientId: op.clientId, ok: true, detail: "already synced" }); continue; }
        const birthYear = p.dateOfBirth ? new Date(p.dateOfBirth).getFullYear() : 2016;
        // wave 42: the op may carry a PREASSIGNED national index (from a
        // birth-year cohort block of this device's offline reserve) and the
        // first-term bill's INV number (from an invoice reserve) — adopted
        // under the same ownership validation as receipts; anything else is
        // ignored and the server mints at flush, exactly as before.
        const preIdx = await resolvePreassignedCode(op, deviceCode, p.schoolId, "NYIDX", "nyansapoIndex", "indexLeaseTag");
        const preInv = await resolvePreassignedCode(op, deviceCode, p.schoolId, "INV", "invoiceNo", "invoiceLeaseTag");
        const studentData = { syncClientId: op.clientId, firstName: p.firstName, lastName: p.lastName, gender: p.gender ?? "M", dateOfBirth: p.dateOfBirth ? new Date(p.dateOfBirth) : null, guardianName: p.guardianName, guardianPhone: p.guardianPhone, status: "ACTIVE", previousSchool: p.previousSchool ?? "Registered offline" };
        let student;
        try {
          student = await db.student.create({ data: { nyansapoIndex: preIdx?.code ?? await nextStudentIndex(birthYear), ...studentData } });
        } catch (e) {
          if (e instanceof Prisma.PrismaClientKnownRequestError && e.code === "P2002") {
            if (preIdx && p2002Target(e).includes("nyansapoIndex")) {
              // pathological: the preassigned index collided (cross-school
              // replay of one lease) — mint server-side instead; still exactly
              // one student, never a duplicate index
              student = await db.student.create({ data: { nyansapoIndex: await nextStudentIndex(birthYear), ...studentData } });
            } else throw e;
          } else throw e;
        }
        if (preIdx) {
          try { await db.codeLease.update({ where: { id: preIdx.leaseId }, data: { spentCount: { increment: 1 } } }); } catch { /* advisory */ }
        }
        idxYearsSeen.add(preIdx ? Number(preIdx.code.slice(3, 7)) : birthYear);
        studentOpsOk++;
        const roll = (await db.enrollment.count({ where: { classroomId: p.classroomId } })) + 1;
        await db.enrollment.create({ data: { studentId: student.id, classroomId: p.classroomId, schoolId: p.schoolId, rollNo: roll, admissionDate: new Date(), status: "ACTIVE" } });
        // auto-bill for the current term — same as the online enrolment path
        const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId: p.schoolId } } });
        let billed: string | null = null;
        if (term) {
          const fs = await db.feeStructure.findFirst({ where: { classroomId: p.classroomId, termId: term.id }, include: { items: true } });
          if (fs) {
            const invData = { studentId: student.id, schoolId: p.schoolId, termId: term.id, classroomName: p.classroomName ?? "", totalCents: fs.totalCents, status: "UNPAID", dueDate: new Date(Date.now() + 14 * 864e5) };
            let inv;
            try {
              inv = await db.invoice.create({ data: { invoiceNo: preInv?.code ?? await nextInvoiceNo(), ...invData } });
            } catch (e) {
              if (e instanceof Prisma.PrismaClientKnownRequestError && e.code === "P2002" && preInv && p2002Target(e).includes("invoiceNo")) {
                inv = await db.invoice.create({ data: { invoiceNo: await nextInvoiceNo(), ...invData } });
              } else throw e;
            }
            if (preInv) {
              try { await db.codeLease.update({ where: { id: preInv.leaseId }, data: { spentCount: { increment: 1 } } }); } catch { /* advisory */ }
            }
            for (const it of fs.items.filter((i) => !i.isOptional)) await db.invoiceItem.create({ data: { invoiceId: inv.id, name: it.name, amountCents: it.amountCents } });
            billed = inv.invoiceNo;
          }
        }
        const onRoll = await db.enrollment.count({ where: { schoolId: p.schoolId, status: "ACTIVE" } });
        await db.school.update({ where: { id: p.schoolId }, data: { studentsOnRoll: onRoll } });
        results.push({ clientId: op.clientId, ok: true, detail: billed ? `student enrolled · index ${student.nyansapoIndex}${preIdx ? " (preassigned)" : ""} · billed ${billed}${preInv ? " (preassigned)" : ""}` : `student enrolled · index ${student.nyansapoIndex}${preIdx ? " (preassigned)" : ""}` });
      } else {
        throw new Error(`unknown op kind ${op.kind}`);
      }
    } catch (e: any) {
      // P2002 racing a concurrent re-flush on the durable idempotency column:
      // the row already exists — the op IS synced, not failed
      if (e instanceof Prisma.PrismaClientKnownRequestError && e.code === "P2002" && p2002Target(e).includes("syncClientId")) {
        results.push({ clientId: op.clientId, ok: true, detail: "already synced" });
        continue;
      }
      results.push({ clientId: op.clientId, ok: false, detail: e?.message ?? "failed" });
    }
  }

  // ── journal the flush (CI3 sync_audit_log + sync_metrics parity): one
  // SyncEvent per school involved, device counters stamped, duration recorded.
  // Journaling must never break the flush itself — swallow its failures.
  const ip = (req.headers.get("x-forwarded-for") ?? "").split(",")[0].trim() || null;
  try {
    const okCount = results.filter((r) => r.ok).length;
    const failCount = results.length - okCount;
    const bySchool = new Map<string, { ok: number; failed: number }>();
    const detail = results.map((r) => r.detail ?? "").filter(Boolean).slice(0, 5).join(" · ");
    for (const r of results) {
      const op = ops.find((o) => o.clientId === r.clientId);
      const sid = op?.payload?.schoolId;
      if (!sid) continue;
      const cur = bySchool.get(sid) ?? { ok: 0, failed: 0 };
      if (r.ok) cur.ok++; else cur.failed++;
      bySchool.set(sid, cur);
    }
    for (const [sid, counts] of bySchool) {
      await db.syncEvent.create({ data: { schoolId: sid, deviceCode, userName: user.fullName, kind: "FLUSH", opsSynced: counts.ok, opsFailed: counts.failed, conflicts: conflictsFiled, durationMs: Date.now() - started, detail: detail || null } });
    }
    if (deviceRow) {
      await db.syncDevice.update({ where: { id: deviceRow.id }, data: {
        lastSyncAt: okCount ? new Date() : undefined,
        lastSeenAt: new Date(), lastIP: ip, lastUser: user.fullName,
        opsSynced: { increment: okCount }, opsFailed: { increment: failCount },
      } });
    }
  } catch { /* journaling is best-effort */ }

  // ── reserve replenishment (wave 41 receipts; wave 42 invoices + indexes):
  // a device that just flushed payment or enrolment ops is a device that
  // works while offline — top its reserves back up when the runway is low so
  // the next blackout still prints real codes. The new blocks ride on the
  // flush response; the client adopts them locally. The response carries
  // `leases` (array, all kinds); the legacy single `lease` (RCP) rides along
  // for older clients.
  const grants: { tag: string; kind: LeaseKind; year: number; from: number; to: number; size: number }[] = [];
  if (deviceRow && firstSchoolId && user.schoolIds.includes(firstSchoolId)) {
    const year = new Date().getFullYear();
    const want: { kind: LeaseKind; year: number }[] = [];
    if (paymentOpsOk > 0) want.push({ kind: "RCP", year });
    if (studentOpsOk > 0) {
      want.push({ kind: "INV", year });
      // replenish the cohorts the enrolments actually drew from (registry
      // semantics — an offline enrolment of a 2016-born learner spends the
      // 2016 cohort block, not the current calendar year's)
      for (const y of [...idxYearsSeen].slice(0, 3)) want.push({ kind: "NYIDX", year: y });
    }
    for (const w of want) {
      try {
        const active = await db.codeLease.findFirst({
          where: { schoolId: firstSchoolId, deviceCode: deviceRow.code, kind: w.kind, year: w.year },
          orderBy: { grantedAt: "desc" },
        });
        const remaining = active ? active.toSeq - active.fromSeq + 1 - active.spentCount : 0;
        if (!active || remaining < 10) grants.push(await grantLease(firstSchoolId, deviceRow.code, user.fullName, 50, w.kind, w.year));
      } catch { /* replenishment is best-effort */ }
    }
  }
  const rcpGrant = grants.find((g) => g.kind === "RCP");

  return NextResponse.json({ results, syncedAt: new Date().toISOString(), deviceCode, conflicts: conflictsFiled, ...(rcpGrant ? { lease: rcpGrant } : {}), ...(grants.length ? { leases: grants } : {}) });
}

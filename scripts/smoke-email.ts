/* Email channel (CI3 Invoice_email + Email_model parity) smoke test —
   (a) teacher GET/POST 403 (email.send gate), (b) bursar GET shape with
   settings null, (c) save-settings validation, (d) save persists,
   (e) password stored + never echoed, (f) blank password keeps the stored
   one, (g) test-send SIMULATED while disabled (defaults to own address),
   (h) send-invoice to a guardian email, (i) missing guardian email → 400,
   (j) bulk-send-invoices UNPAID with rows created, (k) log GET lists them.
   Creates its own state and CLEANS UP afterwards (restores whatever
   EmailSetting / EmailMessage / guardianEmail existed before the run).
   Run: DATABASE_URL=postgresql://… bun scripts/smoke-email.ts */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();
const BASE = "http://localhost:3000";
let pass = 0, fail = 0;

async function check(label: string, cond: boolean, extra = "") {
  if (cond) { pass++; console.log(`  ✅ ${label}`); }
  else { fail++; console.log(`  ❌ ${label} ${extra}`); }
}

async function main() {
  const [bursar, teacher] = await Promise.all([
    db.user.findUnique({ where: { email: "bursar@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "teacher@nyansapo.gh" } }),
  ]);
  if (!bursar || !teacher) { console.log("missing demo users — run the seed first"); return; }
  const school = await db.school.findUnique({ where: { code: "GEA" } });
  if (!school) { console.log("GEA school missing"); return; }
  const schoolId = school.id;
  const URL = `/api/school/${schoolId}/ops/email`;
  const H = (u: typeof bursar) => ({ "content-type": "application/json", "x-user-id": u.id });
  const get = async (u: typeof bursar) => fetch(BASE + URL, { headers: H(u) });
  const post = async (u: typeof bursar, body: any) => fetch(BASE + URL, { method: "POST", headers: H(u), body: JSON.stringify(body) });

  // ── snapshot + clean slate: no EmailSetting / EmailMessage rows ──
  const preSetting = await db.emailSetting.findUnique({ where: { schoolId } });
  const preMessages = await db.emailMessage.findMany({ where: { schoolId } });
  await db.emailSetting.deleteMany({ where: { schoolId } });
  await db.emailMessage.deleteMany({ where: { schoolId } });

  // pick a current-term invoice (unpaid preferred so bulk UNPAID covers it)
  const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
  let invoiceId: string | null = null;
  let studentId: string | null = null;
  let origEmail: string | null = null;
  let pickedUnpaid = false;
  if (term) {
    const invoices = await db.invoice.findMany({ where: { schoolId, termId: term.id }, include: { student: true }, orderBy: { invoiceNo: "asc" } });
    const unpaid = invoices.find((i) => i.totalCents - i.discountCents - i.paidCents > 0);
    const pick = unpaid ?? invoices[0];
    if (pick) { invoiceId = pick.id; studentId = pick.studentId; origEmail = pick.student.guardianEmail; pickedUnpaid = !!unpaid; }
  }
  const TEST_EMAIL = "guardian.smoke@nyansapo.gh";

  try {
    // (a) teacher — no email.send, GET (strict) and POST both blocked
    const a1 = await get(teacher);
    check("teacher GET blocked (403 email.send strict)", a1.status === 403, `got ${a1.status}`);
    const a2 = await post(teacher, { action: "test-send" });
    check("teacher POST blocked (403 email.send)", a2.status === 403, `got ${a2.status}`);

    // (b) bursar GET — 200, null settings, full shape
    const b1 = await get(bursar);
    const gb = await b1.json();
    check("bursar GET 200 with settings null on a clean slate", b1.status === 200 && gb.setting === null, JSON.stringify(gb).slice(0, 160));
    check("GET shape: messages + stats + invoiceCounts", Array.isArray(gb.messages) && !!gb.stats && ["total", "sent", "failed", "simulated"].every((k) => k in gb.stats) && !!gb.invoiceCounts && "emailable" in gb.invoiceCounts && "withoutEmail" in gb.invoiceCounts);

    // (c) validation — empty host with enabled, then port out of range
    const c1 = await post(bursar, { action: "save-settings", enabled: true, host: "", port: 587 });
    check("empty host while enabled rejected (400)", c1.status === 400, `got ${c1.status}`);
    const c2 = await post(bursar, { action: "save-settings", enabled: false, host: "smtp.test.example", port: 99999 });
    check("port 99999 rejected (400)", c2.status === 400, `got ${c2.status}`);

    // (d) save ok — row persists, hasPassword false
    const d1 = await post(bursar, { action: "save-settings", enabled: false, host: "smtp.gmail.com", port: 587, secure: false, username: "bursar@nyansapo.gh", fromName: "GEA Bursary", fromAddress: "fees@gea.edu.gh" });
    check("save-settings → 200", d1.status === 200, `got ${d1.status}`);
    const rowD = await db.emailSetting.findUnique({ where: { schoolId } });
    check("EmailSetting row persisted", !!rowD && rowD.host === "smtp.gmail.com" && rowD.port === 587 && rowD.fromAddress === "fees@gea.edu.gh" && rowD.password === null, JSON.stringify(rowD ?? {}));
    const gd = await (await get(bursar)).json();
    check("GET shows hasPassword false", gd.setting?.hasPassword === false, JSON.stringify(gd.setting ?? {}));

    // (e) store a password — hasPassword true, value never echoed
    const e1 = await post(bursar, { action: "save-settings", enabled: false, host: "smtp.gmail.com", port: 587, secure: true, username: "bursar@nyansapo.gh", password: "smtp-app-secret-123", fromName: "GEA Bursary", fromAddress: "fees@gea.edu.gh" });
    check("save with password → 200", e1.status === 200, `got ${e1.status}`);
    const rawG = await (await get(bursar)).text();
    const g2 = JSON.parse(rawG);
    check("GET hasPassword true after storing", g2.setting?.hasPassword === true, rawG.slice(0, 160));
    check("password value never appears in the payload", !rawG.includes("smtp-app-secret-123") && !("password" in (g2.setting ?? {})));

    // (f) re-save with a blank password keeps the stored secret
    const f1 = await post(bursar, { action: "save-settings", enabled: false, host: "smtp.mail.example", port: 465, secure: true, username: "bursar@nyansapo.gh", password: "", fromName: "GEA Bursary", fromAddress: "fees@gea.edu.gh" });
    check("re-save with blank password → 200", f1.status === 200, `got ${f1.status}`);
    const rowF = await db.emailSetting.findUnique({ where: { schoolId } });
    check("blank password preserved the stored secret", rowF?.password === "smtp-app-secret-123" && rowF?.host === "smtp.mail.example", JSON.stringify(rowF ?? {}));

    // (g) test-send while disabled → SIMULATED, addressed to the sender by default
    const g3 = await post(bursar, { action: "test-send" });
    const j3 = await g3.json();
    check("test-send while disabled → 200 SIMULATED", g3.status === 200 && j3.message?.status === "SIMULATED", JSON.stringify(j3));
    check("test-send defaulted to the sender's own email (category TEST)", j3.message?.toAddress === "bursar@nyansapo.gh" && j3.message?.category === "TEST", JSON.stringify(j3.message ?? {}));

    if (invoiceId && studentId) {
      // (h) send-invoice for an invoice whose guardian has an email
      await db.student.update({ where: { id: studentId }, data: { guardianEmail: TEST_EMAIL } });
      const h1 = await post(bursar, { action: "send-invoice", invoiceId });
      const jh = await h1.json();
      check("send-invoice → 200 with INVOICE row to the guardian", h1.status === 200 && jh.message?.category === "INVOICE" && jh.message?.toAddress === TEST_EMAIL, JSON.stringify(jh));
      check("send-invoice logged SIMULATED while channel disabled", jh.message?.status === "SIMULATED", JSON.stringify(jh.message ?? {}));

      // (i) guardian without an email → clear 400
      await db.student.update({ where: { id: studentId }, data: { guardianEmail: null } });
      const i1 = await post(bursar, { action: "send-invoice", invoiceId });
      const ji = await i1.json();
      check("send-invoice without guardian email → 400", i1.status === 400, `got ${i1.status}`);
      check("400 explains the missing guardian email", /no email address on file/i.test(ji.error ?? ""), JSON.stringify(ji));

      // (j) bulk UNPAID — student re-emailable so at least one row is created
      await db.student.update({ where: { id: studentId }, data: { guardianEmail: TEST_EMAIL } });
      const startRows = await db.emailMessage.count({ where: { schoolId } });
      const j1 = await post(bursar, { action: "bulk-send-invoices", scope: "UNPAID" });
      const jj = await j1.json();
      check("bulk-send UNPAID → 200 with counts ≥ 0", j1.status === 200 && (jj.sent ?? -1) >= 0 && (jj.skipped ?? -1) >= 0 && (jj.failed ?? -1) >= 0, JSON.stringify(jj));
      const afterRows = await db.emailMessage.count({ where: { schoolId } });
      check(pickedUnpaid ? "bulk run created EmailMessage rows" : "bulk run left a row trail (no unpaid seed invoices — count only)",
        pickedUnpaid ? afterRows > startRows : afterRows >= startRows, `${startRows} → ${afterRows}`);

      // (k) the log lists the run's messages, newest first, never the body
      const gk = await (await get(bursar)).json();
      const msgs: any[] = gk.messages ?? [];
      check("log GET lists the invoice emails", msgs.some((m) => m.toAddress === TEST_EMAIL && m.category === "INVOICE"), JSON.stringify(msgs.slice(0, 2)));
      check("log rows carry no body field", msgs.every((m) => !("body" in m)));
      check("log newest first", msgs.length < 2 || new Date(msgs[0].sentAt).getTime() >= new Date(msgs[msgs.length - 1].sentAt).getTime());
    } else {
      check("current-term invoice found for send-invoice tests", false, "seed a current-term invoice first");
    }
  } finally {
    // ── cleanup: restore whatever existed before the run ──
    if (studentId) await db.student.update({ where: { id: studentId }, data: { guardianEmail: origEmail } }).catch(() => null);
    await db.emailSetting.deleteMany({ where: { schoolId } });
    await db.emailMessage.deleteMany({ where: { schoolId } });
    if (preSetting) {
      await db.emailSetting.create({
        data: {
          schoolId, enabled: preSetting.enabled, host: preSetting.host, port: preSetting.port, secure: preSetting.secure,
          username: preSetting.username, password: preSetting.password, fromName: preSetting.fromName, fromAddress: preSetting.fromAddress,
        },
      });
    }
    for (const m of preMessages) {
      await db.emailMessage.create({
        data: {
          schoolId: m.schoolId, toAddress: m.toAddress, toName: m.toName, subject: m.subject, body: m.body,
          category: m.category, status: m.status, errorReason: m.errorReason, sentByName: m.sentByName, studentId: m.studentId, sentAt: m.sentAt,
        },
      });
    }
  }

  console.log(`\nsmoke-email: ${pass} passed, ${fail} failed`);
  if (fail > 0) process.exit(1);
}

main().finally(() => db.$disconnect());

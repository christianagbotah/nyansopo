/* SMS gateway smoke test — ARKESEL / TERMII adapters, phone normalisation,
   provider dispatch through sendSms (debit-on-success), config status API.
   Run: bun scripts/smoke-sms-gateway.ts (dev server + seeded DB required).
   The real gateways are replaced by an in-process mock server — no live
   messages leave the machine. */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";
import { toGhanaIntl, arkeselSend, termiiSend } from "../src/lib/sms-gateway";
import { sendSms } from "../src/lib/sms";

const db = new PrismaClient();
const BASE = "http://localhost:3000";
let pass = 0, fail = 0;
const check = (label: string, cond: boolean, extra = "") => {
  if (cond) { pass++; console.log(`  ✅ ${label}`); }
  else { fail++; console.log(`  ❌ ${label} ${extra}`); }
};

async function main() {
  console.log("\n── A. Ghana phone normalisation ──");
  {
    check("0244123456 → 233244123456", toGhanaIntl("0244123456") === "233244123456", String(toGhanaIntl("0244123456")));
    check("+233244123456 → 233244123456", toGhanaIntl("+233244123456") === "233244123456");
    check("233244123456 passthrough", toGhanaIntl("233244123456") === "233244123456");
    check("spaces/dashes stripped", toGhanaIntl("0244-123 456") === "233244123456");
    check("short number → null", toGhanaIntl("12345") === null);
    check("10-digit local → null", toGhanaIntl("02441234567") === null);
    check("foreign number → null", toGhanaIntl("+2348012345678") === null);
    check("empty → null", toGhanaIntl("") === null);
  }

  // ── in-process mock gateway ──
  let mockMode: "ok" | "fail" = "ok";
  const seen: { path: string; apiKeyHeader?: string | null; body: any }[] = [];
  const mock = Bun.serve({
    port: 0,
    fetch: async (req) => {
      const url = new URL(req.url);
      const body = await req.json().catch(() => ({}));
      seen.push({ path: url.pathname, apiKeyHeader: req.headers.get("api-key"), body });
      if (url.pathname === "/api/v2/sms/send") {
        if (mockMode === "fail") return Response.json({ status: "error", message: "Insufficient balance" }, { status: 200 });
        return Response.json({ status: "success", data: [{ id: `ark-${seen.length}` }] });
      }
      if (url.pathname === "/api/sms/send") {
        if (mockMode === "fail") return Response.json({ message: "Invalid sender id" }, { status: 200 });
        return Response.json({ message_id: `term-${seen.length}`, message: "Successfully Sent" });
      }
      return new Response("not found", { status: 404 });
    },
  });
  const MOCK = `http://localhost:${mock.port}`;

  console.log("\n── B. Arkesel adapter (against mock) ──");
  {
    process.env.SMS_ARKESEL_BASE_URL = MOCK; // never touch the real gateway from a test
    seen.length = 0; mockMode = "ok";
    const r = await arkeselSend({ apiKey: "AK-TEST", senderId: "GEA-MADINA", message: "hi", recipient: "233244123456" });
    check("success → ok:true + ref", r.ok && r.ref === "ark-1", JSON.stringify(r));
    const req0 = seen[0];
    check("POST to /api/v2/sms/send", req0?.path === "/api/v2/sms/send", req0?.path ?? "no call");
    check("api-key header sent", req0?.apiKeyHeader === "AK-TEST", String(req0?.apiKeyHeader));
    check("body {sender, message, recipients}", req0?.body?.sender === "GEA-MADINA" && req0?.body?.message === "hi" && Array.isArray(req0?.body?.recipients) && req0?.body.recipients[0] === "233244123456", JSON.stringify(req0?.body));
    mockMode = "fail";
    const f = await arkeselSend({ apiKey: "AK-TEST", senderId: "GEA-MADINA", message: "hi", recipient: "233244123456" });
    check("provider error → ok:false + reason", !f.ok && /Insufficient/i.test(f.error ?? ""), JSON.stringify(f));
    process.env.SMS_ARKESEL_BASE_URL = "http://127.0.0.1:1"; // unreachable port
    const net = await arkeselSend({ apiKey: "AK-TEST", senderId: "X", message: "hi", recipient: "233244123456" });
    check("network failure → ok:false + friendly error", !net.ok && /failed|timeout|error/i.test(net.error ?? ""), JSON.stringify(net));
  }

  console.log("\n── C. Termii adapter (against mock) ──");
  {
    process.env.SMS_TERMII_BASE_URL = MOCK; // never touch the real gateway from a test
    seen.length = 0; mockMode = "ok";
    const r = await termiiSend({ apiKey: "TM-TEST", senderId: "GEA-MADINA", message: "hi", recipient: "233244123456" });
    check("success → ok:true + ref", r.ok && r.ref === "term-1", JSON.stringify(r));
    const req0 = seen[0];
    check("POST to /api/sms/send", req0?.path === "/api/sms/send", req0?.path ?? "no call");
    check("body {api_key, to, from, sms, type, channel}", req0?.body?.api_key === "TM-TEST" && req0?.body?.to === "233244123456" && req0?.body?.from === "GEA-MADINA" && req0?.body?.sms === "hi" && req0?.body?.type === "plain" && req0?.body?.channel === "generic", JSON.stringify(req0?.body));
    mockMode = "fail";
    const f = await termiiSend({ apiKey: "TM-TEST", senderId: "GEA-MADINA", message: "hi", recipient: "233244123456" });
    check("provider error → ok:false + reason", !f.ok && /sender id/i.test(f.error ?? ""), JSON.stringify(f));
    process.env.SMS_TERMII_BASE_URL = MOCK;
  }

  console.log("\n── D. sendSms full path with db (mock gateways) ──");
  const user = await db.user.findUnique({ where: { email: "head@nyansapo.gh" } });
  const school = await db.school.findUnique({ where: { code: "GEA" } });
  if (!user || !school) { console.log("missing demo data"); mock.stop(true); return; }
  const schoolId = school.id;
  const orig = await db.smsSetting.findUnique({ where: { schoolId } });
  const testStart = new Date();
  const msgs = () => db.smsMessage.findMany({ where: { schoolId, sentAt: { gte: testStart } }, orderBy: { sentAt: "asc" } });
  const setProvider = async (provider: string, credits = 100) =>
    db.smsSetting.upsert({ where: { schoolId }, update: { provider, credits }, create: { schoolId, provider, credits } });
  const credits = async () => (await db.smsSetting.findUnique({ where: { schoolId } }))!.credits;
  {
    // ARKESEL happy path
    process.env.SMS_ARKESEL_API_KEY = "AK-TEST";
    process.env.SMS_ARKESEL_BASE_URL = MOCK;
    seen.length = 0; mockMode = "ok";
    await setProvider("ARKESEL", 100);
    let m = await sendSms({ db, schoolId, recipientName: "Arkesel Probe", recipientPhone: "0244123456", body: "gateway probe", category: "GENERAL", sentByName: "smoke" });
    check("ARKESEL send → SENT", m.status === "SENT", m.status);
    check("provider + providerRef recorded", m.provider === "ARKESEL" && !!m.providerRef?.startsWith("ark-"), `${m.provider}/${m.providerRef}`);
    check("credit debited once", (await credits()) === 99, String(await credits()));
    check("mock received exactly one call", seen.length === 1, String(seen.length));
    check("recipient normalised to 233… in the wire body", seen[0]?.body?.recipients?.[0] === "233244123456", JSON.stringify(seen[0]?.body?.recipients));

    // ARKESEL provider-side failure → FAILED, no debit
    mockMode = "fail";
    m = await sendSms({ db, schoolId, recipientName: "Arkesel Probe", recipientPhone: "0244123456", body: "gateway probe", category: "GENERAL" });
    check("provider failure → FAILED + reason", m.status === "FAILED" && /Insufficient/i.test(m.errorReason ?? ""), `${m.status}/${m.errorReason}`);
    check("failed send costs no credit", (await credits()) === 99 && m.creditsUsed === 0, `${await credits()}/${m.creditsUsed}`);

    // ARKESEL invalid phone → rejected before any HTTP call
    seen.length = 0; mockMode = "ok";
    m = await sendSms({ db, schoolId, recipientName: "Arkesel Probe", recipientPhone: "12345", body: "gateway probe", category: "GENERAL" });
    check("invalid phone → FAILED, gateway never called", m.status === "FAILED" && seen.length === 0, `${m.status}/${seen.length}`);
    check("invalid phone costs no credit", (await credits()) === 99, String(await credits()));

    // TERMII happy path
    process.env.SMS_TERMII_API_KEY = "TM-TEST";
    process.env.SMS_TERMII_BASE_URL = MOCK;
    await setProvider("TERMII", 100);
    m = await sendSms({ db, schoolId, recipientName: "Termii Probe", recipientPhone: "+233244123456", body: "gateway probe", category: "GENERAL" });
    check("TERMII send → SENT + ref", m.status === "SENT" && m.provider === "TERMII" && !!m.providerRef?.startsWith("term-"), `${m.status}/${m.provider}/${m.providerRef}`);
    check("TERMII debit", (await credits()) === 99, String(await credits()));

    // missing API key → FAILED, no debit, no call
    seen.length = 0;
    delete process.env.SMS_TERMII_API_KEY;
    m = await sendSms({ db, schoolId, recipientName: "Termii Probe", recipientPhone: "0244123456", body: "gateway probe", category: "GENERAL" });
    check("missing API key → FAILED with setup hint", m.status === "FAILED" && /API key not configured/.test(m.errorReason ?? ""), `${m.status}/${m.errorReason}`);
    check("missing key costs no credit + no call", (await credits()) === 99 && seen.length === 0, `${await credits()}/${seen.length}`);

    // SIMULATED debit-only-on-success regression
    await setProvider("SIMULATED", 100);
    m = await sendSms({ db, schoolId, recipientName: "Sim Probe", recipientPhone: "12345", body: "sim probe", category: "GENERAL" });
    check("SIMULATED junk phone → FAILED, no debit (regression fix)", m.status === "FAILED" && m.creditsUsed === 0 && (await credits()) === 100, `${m.status}/${m.creditsUsed}/${await credits()}`);
    m = await sendSms({ db, schoolId, recipientName: "Sim Probe", recipientPhone: "0244123456", body: "sim probe", category: "GENERAL" });
    check("SIMULATED valid phone → SENT + debit", m.status === "SENT" && m.creditsUsed === 1 && (await credits()) === 99, `${m.status}/${(await credits())}`);

    // out of credits → FAILED before dispatch
    await setProvider("SIMULATED", 0);
    seen.length = 0;
    m = await sendSms({ db, schoolId, recipientName: "Broke Probe", recipientPhone: "0244123456", body: "broke probe", category: "GENERAL" });
    check("zero credits → FAILED with top-up reason", m.status === "FAILED" && /credits/i.test(m.errorReason ?? ""), `${m.status}/${m.errorReason}`);
    check("zero-credit attempt debits nothing", (await credits()) === 0 && m.creditsUsed === 0);
  }

  console.log("\n── E. API surface ──");
  {
    const H = { "content-type": "application/json", "x-user-id": user.id };
    const res = await fetch(`${BASE}/api/school/${schoolId}/ops/sms`, { headers: H });
    const data = await res.json().catch(() => ({}));
    check("GET /ops/sms 200", res.status === 200, String(res.status));
    check("gateway config status exposed", typeof data.gateway?.arkesel === "boolean" && typeof data.gateway?.termii === "boolean", JSON.stringify(data.gateway));
    // provider round-trip through the settings UI action
    await fetch(`${BASE}/api/school/${schoolId}/ops/sms`, { method: "POST", headers: H, body: JSON.stringify({ action: "save-settings", provider: "ARKESEL", senderId: orig?.senderId ?? "NYANSAPO" }) });
    let setting = await db.smsSetting.findUnique({ where: { schoolId } });
    check("save-settings persists ARKESEL", setting?.provider === "ARKESEL", setting?.provider ?? "none");
    await fetch(`${BASE}/api/school/${schoolId}/ops/sms`, { method: "POST", headers: H, body: JSON.stringify({ action: "save-settings", provider: "SIMULATED", senderId: orig?.senderId ?? "NYANSAPO" }) });
    setting = await db.smsSetting.findUnique({ where: { schoolId } });
    check("restored to SIMULATED", setting?.provider === "SIMULATED", setting?.provider ?? "none");
  }

  console.log("\n── cleanup ──");
  await db.smsMessage.deleteMany({ where: { schoolId, sentAt: { gte: testStart }, recipientName: { in: ["Arkesel Probe", "Termii Probe", "Sim Probe", "Broke Probe"] } } });
  if (orig) await db.smsSetting.update({ where: { schoolId }, data: { provider: orig.provider, credits: orig.credits } });
  else await db.smsSetting.deleteMany({ where: { schoolId } });
  delete process.env.SMS_ARKESEL_API_KEY; delete process.env.SMS_ARKESEL_BASE_URL;
  delete process.env.SMS_TERMII_API_KEY; delete process.env.SMS_TERMII_BASE_URL;
  mock.stop(true);
  console.log(`\n${pass}/${pass + fail} checks passed${fail ? " — FIX THE FAILURES" : ""}`);
  if (fail) process.exit(1);
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

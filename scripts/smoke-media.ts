/* Media blob-storage smoke test — admission portraits & branding uploads
   stored as MediaAsset rows behind /api/media/{id} references.
   Run: bun scripts/smoke-media.ts (dev server + seeded DB required) */
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

// 1×1 transparent PNG (70 bytes) — a real, decodable image
const TINY_PNG = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==";
const TINY_PNG_BYTES = Buffer.from(TINY_PNG.split(",")[1], "base64").length;

async function main() {
  const user = await db.user.findUnique({ where: { email: "head@nyansapo.gh" } });
  const school = await db.school.findUnique({ where: { code: "GEA" } });
  if (!user || !school) { console.log("missing demo data — run the seed first"); return; }
  const H = { "content-type": "application/json", "x-user-id": user.id };
  const post = (url: string, body: any) => fetch(BASE + url, { method: "POST", headers: H, body: JSON.stringify(body) });
  const get = (url: string) => fetch(BASE + url, { headers: H });

  const classroom = await db.classroom.findFirst({ where: { schoolId: school.id } });
  if (!classroom) { console.log("no classroom — aborting"); return; }
  // pre-sweep orphaned branding assets (unreferenced by any setting) so the
  // final count check is stable even when earlier suites crashed mid-run
  {
    const referenced = new Set(
      (await db.schoolSetting.findMany({ where: { key: { in: ["branding.logoUrl", "branding.signatureUrl"] } } }))
        .map((r) => { try { const v = JSON.parse(r.value); return typeof v === "string" ? v.split("/").pop() : null; } catch { return null; } })
        .filter(Boolean),
    );
    const orphans = await db.mediaAsset.findMany({ where: { schoolId: school.id, kind: { in: ["LOGO", "SIGNATURE"] } } });
    for (const o of orphans) if (!referenced.has(o.id)) await db.mediaAsset.delete({ where: { id: o.id } }).catch(() => {});
  }
  const preAssets = await db.mediaAsset.count({ where: { schoolId: school.id } });
  const admit = (first: string, last: string, photoUrl?: string) =>
    post(`/api/school/${school.id}/ops/admissions`, {
      action: "admit", firstName: first, lastName: last, gender: "F",
      classroomId: classroom.id, guardianName: "Media Probe", guardianPhone: "0249990700", relationship: "Mother",
      ...(photoUrl !== undefined ? { photoUrl } : {}),
    });
  const cleanupStudent = async (id: string) => {
    await db.smsMessage.deleteMany({ where: { studentId: id } }).catch(() => {});
    await db.enrollment.deleteMany({ where: { studentId: id } });
    await db.student.delete({ where: { id } });
  };

  console.log("\n── A. admit with a valid portrait → stored as a media asset ──");
  let studentA: string | undefined;
  {
    const res = await admit("Media", "Probe-A", TINY_PNG);
    const body = ((await res.json().catch(() => ({}))) ?? {});
    studentA = body.student?.id;
    check("admit 200", res.status === 200, String(res.status));
    const st = await db.student.findUnique({ where: { id: studentA } });
    check("photoUrl is a /api/media/ reference", !!st?.photoUrl?.startsWith("/api/media/"), st?.photoUrl ?? "null");
    check("photoSeed null (real photo present)", st?.photoSeed === null, String(st?.photoSeed));
    const asset = st?.photoUrl ? await db.mediaAsset.findUnique({ where: { id: st.photoUrl.split("/").pop()! } }) : null;
    check("MediaAsset row exists", !!asset);
    check("asset kind PORTRAIT + school-scoped", asset?.kind === "PORTRAIT" && asset?.schoolId === school.id, `${asset?.kind}/${asset?.schoolId === school.id}`);
    check("asset mime + size correct", asset?.mimeType === "image/png" && asset?.sizeBytes === TINY_PNG_BYTES, `${asset?.mimeType} ${asset?.sizeBytes}`);
  }

  console.log("\n── B. GET /api/media/{id} streams the bytes, school-scoped ──");
  {
    const st = await db.student.findUnique({ where: { id: studentA } });
    const url = st!.photoUrl!;
    const res = await get(url);
    check("authed GET 200", res.status === 200, String(res.status));
    check("content-type matches asset", (res.headers.get("content-type") ?? "") === "image/png", res.headers.get("content-type") ?? "");
    check("immutable cache header", (res.headers.get("cache-control") ?? "").includes("immutable"), res.headers.get("cache-control") ?? "");
    const bytes = Buffer.from(await res.arrayBuffer());
    check("bytes match the stored asset", bytes.length === TINY_PNG_BYTES, String(bytes.length));
    const noAuth = await fetch(BASE + url);
    check("unauthenticated GET 401", noAuth.status === 401, String(noAuth.status));
    const junk = await get("/api/media/not-a-real-cuid-id");
    check("junk id 404", junk.status === 404, String(junk.status));
    const forged = await get("/api/media/..%2F..%2Fschool");
    check("path traversal rejected", [400, 404].includes(forged.status), String(forged.status));
  }

  console.log("\n── C. junk / oversized portraits degrade to no photo ──");
  {
    const junkRes = await admit("Media", "Probe-B", "javascript:alert(1)");
    const junkBody = ((await junkRes.json().catch(() => ({}))) ?? {});
    const stB = await db.student.findUnique({ where: { id: junkBody.student?.id } });
    check("junk photo → photoUrl null", !stB?.photoUrl, String(stB?.photoUrl));
    check("junk photo → photoSeed fallback set", !!stB?.photoSeed, String(stB?.photoSeed));
    // base64 payload that decodes over the 700KB cap: header valid, body huge
    const huge = "data:image/png;base64," + "A".repeat(800_000);
    const hugeRes = await admit("Media", "Probe-C", huge);
    const hugeBody = ((await hugeRes.json().catch(() => ({}))) ?? {});
    const stC = await db.student.findUnique({ where: { id: hugeBody.student?.id } });
    check("oversized photo → rejected, admit still 200", hugeRes.status === 200 && !stC?.photoUrl, `${hugeRes.status}/${stC?.photoUrl}`);
    const afterAssets = await db.mediaAsset.count({ where: { schoolId: school.id } });
    check("no assets created for junk inputs", afterAssets === preAssets + 1, `${afterAssets} vs ${preAssets + 1}`);
    await cleanupStudent(junkBody.student?.id); await cleanupStudent(hugeBody.student?.id);
  }

  console.log("\n── D. branding uploads flow through the settings API ──");
  {
    const origLogo = await db.schoolSetting.findUnique({ where: { schoolId_key: { schoolId: school.id, key: "branding.logoUrl" } } });
    const origSig = await db.schoolSetting.findUnique({ where: { schoolId_key: { schoolId: school.id, key: "branding.signatureUrl" } } });
    const up = await post(`/api/school/${school.id}/settings`, { action: "update-preferences", logoUrl: TINY_PNG, signatureUrl: TINY_PNG });
    check("update-preferences 200", up.status === 200, String(up.status));
    const getRes = await get(`/api/school/${school.id}/settings`);
    const data = ((await getRes.json().catch(() => ({}))) ?? {});
    check("logoUrl is a media reference", typeof data.branding?.logoUrl === "string" && data.branding.logoUrl.startsWith("/api/media/"), String(data.branding?.logoUrl));
    check("signatureUrl is a media reference", typeof data.branding?.signatureUrl === "string" && data.branding.signatureUrl.startsWith("/api/media/"), String(data.branding?.signatureUrl));
    const [logoAsset, sigAsset] = await Promise.all([
      db.mediaAsset.findUnique({ where: { id: String(data.branding.logoUrl).split("/").pop() } }),
      db.mediaAsset.findUnique({ where: { id: String(data.branding.signatureUrl).split("/").pop() } }),
    ]);
    check("LOGO + SIGNATURE assets stored", logoAsset?.kind === "LOGO" && sigAsset?.kind === "SIGNATURE", `${logoAsset?.kind}/${sigAsset?.kind}`);
    const stream = await get(data.branding.logoUrl);
    check("logo streams via /api/media", stream.status === 200 && (stream.headers.get("content-type") ?? "") === "image/png", String(stream.status));

    // re-save exactly as the client does (round-trips the /api/media URL) → no duplicate assets
    const assetsBefore = await db.mediaAsset.count({ where: { schoolId: school.id } });
    const resave = await post(`/api/school/${school.id}/settings`, { action: "update-preferences", logoUrl: data.branding.logoUrl, signatureUrl: data.branding.signatureUrl, receiptStyle: "A5" });
    check("re-save with media URLs 200", resave.status === 200, String(resave.status));
    const assetsAfter = await db.mediaAsset.count({ where: { schoolId: school.id } });
    check("re-save creates no duplicate assets", assetsAfter === assetsBefore, `${assetsBefore} → ${assetsAfter}`);
    const stillLogo = await get(`/api/school/${school.id}/settings`).then((r) => r.json());
    check("logo reference survives re-save", stillLogo.branding?.logoUrl === data.branding.logoUrl, String(stillLogo.branding?.logoUrl));

    // explicit null clears
    await post(`/api/school/${school.id}/settings`, { action: "update-preferences", logoUrl: null, signatureUrl: null });
    const cleared = await get(`/api/school/${school.id}/settings`).then((r) => r.json());
    check("explicit null clears branding", cleared.branding?.logoUrl === null && cleared.branding?.signatureUrl === null, `${cleared.branding?.logoUrl}/${cleared.branding?.signatureUrl}`);

    // restore pre-test state
    for (const [key, row] of [["branding.logoUrl", origLogo], ["branding.signatureUrl", origSig]] as const) {
      if (row) await db.schoolSetting.update({ where: { id: row.id }, data: { value: row.value } });
      else await db.schoolSetting.deleteMany({ where: { schoolId: school.id, key } });
    }
  }

  console.log("\n── cleanup ──");
  if (studentA) {
    const st = await db.student.findUnique({ where: { id: studentA } });
    if (st?.photoUrl) await db.mediaAsset.deleteMany({ where: { id: st.photoUrl.split("/").pop()! } });
    await cleanupStudent(studentA);
  }
  // drop test-created branding assets (kinds LOGO/SIGNATURE created above are re-created each run; remove ones not referenced by any setting)
  const referenced = new Set(
    (await db.schoolSetting.findMany({ where: { key: { in: ["branding.logoUrl", "branding.signatureUrl"] } } }))
      .map((r) => { try { const v = JSON.parse(r.value); return typeof v === "string" ? v.split("/").pop() : null; } catch { return null; } })
      .filter(Boolean),
  );
  const orphans = await db.mediaAsset.findMany({ where: { schoolId: school.id, kind: { in: ["LOGO", "SIGNATURE"] } } });
  for (const o of orphans) if (!referenced.has(o.id)) await db.mediaAsset.delete({ where: { id: o.id } }).catch(() => {});
  const finalAssets = await db.mediaAsset.count({ where: { schoolId: school.id } });
  check("asset count back to pre-test state", finalAssets === preAssets, `${finalAssets} vs ${preAssets}`);

  console.log(`\n${pass}/${pass + fail} checks passed${fail ? " — FIX THE FAILURES" : ""}`);
  if (fail) process.exit(1);
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

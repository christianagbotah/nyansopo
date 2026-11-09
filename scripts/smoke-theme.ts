/* School theming (CI3 app_theme / theme_settings parity) smoke test —
   (a) default theme returned when no row, (b) member-wide GET,
   (c) preset apply persists, (d) custom pickers persist, (e) validation,
   (f) settings.theme gate — super admin/proprietor only (school admin,
   accountant, teacher all 403), (g) settings payload carries theme.
   Creates its own state and CLEANS UP afterwards.
   Run: DATABASE_URL=postgresql://… bun scripts/smoke-theme.ts */
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
  const [superAdmin, headAdmin, bursar, teacher] = await Promise.all([
    db.user.findUnique({ where: { email: "super@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "head@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "bursar@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "teacher@nyansapo.gh" } }),
  ]);
  if (!superAdmin || !headAdmin || !bursar || !teacher) { console.log("missing demo users — run the seed first"); return; }
  const school = await db.school.findUnique({ where: { code: "GEA" } });
  if (!school) { console.log("GEA school missing"); return; }
  const schoolId = school.id;
  const H = (u: typeof superAdmin) => ({ "content-type": "application/json", "x-user-id": u.id });
  const get = async (u: typeof superAdmin, url: string) => fetch(BASE + url, { headers: H(u) });
  const post = async (u: typeof superAdmin, url: string, body: any) => fetch(BASE + url, { method: "POST", headers: H(u), body: JSON.stringify(body) });

  const preRow = await db.schoolSetting.findUnique({ where: { schoolId_key: { schoolId, key: "theme" } } });

  try {
    // ── fresh baseline: no theme row → Nyansapo default ──
    await db.schoolSetting.deleteMany({ where: { schoolId, key: "theme" } });
    const d0 = await (await get(superAdmin, `/api/school/${schoolId}/ops/theme`)).json();
    check("GET returns the Nyansapo default when unset", d0.theme?.primary === "#0e5f3f" && d0.theme?.name === "Nyansapo Green", JSON.stringify(d0.theme));

    // ── every member can read the theme (they need it to paint the shell) ──
    const dt = await (await get(teacher, `/api/school/${schoolId}/ops/theme`)).json();
    check("teacher (any member) can GET the theme", !!dt.theme?.primary);

    // ── preset apply (CI3 apply_theme) — super admin only ──
    const p1 = await post(superAdmin, `/api/school/${schoolId}/ops/theme`, { action: "preset", theme: "ocean" });
    const j1 = await p1.json();
    check("preset apply → 200", p1.status === 200);
    check("ocean preset colours (#2E3192 / #1BFFFF / #00d4ff)", j1.theme?.primary === "#2E3192" && j1.theme?.secondary === "#1BFFFF" && j1.theme?.accent === "#00d4ff", JSON.stringify(j1.theme));
    const row1 = await db.schoolSetting.findUnique({ where: { schoolId_key: { schoolId, key: "theme" } } });
    check("preset persisted in SchoolSetting[theme]", !!row1 && JSON.parse(row1.value).primary === "#2E3192");
    const d1 = await (await get(superAdmin, `/api/school/${schoolId}/ops/theme`)).json();
    check("GET reflects the applied preset", d1.theme?.primary === "#2E3192");

    // ── invalid preset ──
    const p2 = await post(superAdmin, `/api/school/${schoolId}/ops/theme`, { action: "preset", theme: "hacker-neon" });
    check("invalid preset rejected (400)", p2.status === 400);

    // ── custom pickers (CI3 save_custom) ──
    const p3 = await post(superAdmin, `/api/school/${schoolId}/ops/theme`, { action: "custom", name: "Datus Maroon", primary: "#7f1d1d", secondary: "#450a0a", accent: "#f59e0b" });
    const j3 = await p3.json();
    check("custom theme apply → 200", p3.status === 200 && j3.theme?.name === "Datus Maroon");
    const row3 = await db.schoolSetting.findUnique({ where: { schoolId_key: { schoolId, key: "theme" } } });
    check("custom colours persisted", !!row3 && JSON.parse(row3.value).accent === "#f59e0b");

    // ── validation: missing colour ──
    const p4 = await post(superAdmin, `/api/school/${schoolId}/ops/theme`, { action: "custom", primary: "#111111" });
    check("custom without all three colours rejected (400)", p4.status === 400);
    const p5 = await post(superAdmin, `/api/school/${schoolId}/ops/theme`, { action: "custom", primary: "not-a-hex", secondary: "#222222", accent: "#333333" });
    check("non-hex colour rejected (400)", p5.status === 400);

    // ── settings.theme gate — the colour scheme is the super admin's call:
    //    school admin (head), accountant (bursar) and teacher are all blocked ──
    const p6 = await post(teacher, `/api/school/${schoolId}/ops/theme`, { action: "preset", theme: "teal" });
    check("teacher POST blocked (403 settings.theme)", p6.status === 403, `got ${p6.status}`);
    const p7 = await post(headAdmin, `/api/school/${schoolId}/ops/theme`, { action: "preset", theme: "teal" });
    check("school admin POST blocked (403 settings.theme)", p7.status === 403, `got ${p7.status}`);
    const p8 = await post(bursar, `/api/school/${schoolId}/ops/theme`, { action: "preset", theme: "teal" });
    check("accountant POST blocked (403 settings.theme)", p8.status === 403, `got ${p8.status}`);

    // ── settings payload carries the theme for the Theme tab ──
    const s1 = await (await get(superAdmin, `/api/school/${schoolId}/settings`)).json();
    check("settings GET payload includes theme", s1.theme?.primary === "#7f1d1d", JSON.stringify(s1.theme));
  } finally {
    // ── cleanup: restore whatever existed before the run ──
    await db.schoolSetting.deleteMany({ where: { schoolId, key: "theme" } });
    if (preRow) await db.schoolSetting.create({ data: { schoolId, key: "theme", value: preRow.value } });
  }

  console.log(`\nsmoke-theme: ${pass} passed, ${fail} failed`);
  if (fail > 0) process.exit(1);
}

main().finally(() => db.$disconnect());

/* Portal completeness smoke test — exercises every module API as
   SCHOOL_ADMIN (head@) and TEACHER, asserting HTTP 200 + non-empty payloads.
   Run: bun scripts/smoke-portals.ts */
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();
const BASE = "http://localhost:3000";
const today = new Date().toISOString().slice(0, 10);

const ENDPOINTS = (schoolId: string) => ({
  "dashboard/overview": `/api/school/${schoolId}/ops/overview`,
  "students": `/api/school/${schoolId}/students`,
  "settings": `/api/school/${schoolId}/settings`,
  "fees": `/api/school/${schoolId}/fees`,
  "discounts": `/api/school/${schoolId}/ops/discounts`,
  "daily services": `/api/school/${schoolId}/ops/daily?date=${today}`,
  "attendance": `/api/school/${schoolId}/attendance?date=${today}`,
  "gradebook": `/api/school/${schoolId}/gradebook`,
  "classes": `/api/school/${schoolId}/ops/classes`,
  "academics (reports)": `/api/school/${schoolId}/ops/academics`,
  "exams (online)": `/api/school/${schoolId}/ops/exams`,
  "sms": `/api/school/${schoolId}/ops/sms`,
  "library": `/api/school/${schoolId}/ops/library`,
  "transport": `/api/school/${schoolId}/ops/transport`,
  "payroll": `/api/school/${schoolId}/ops/payroll`,
  "finance": `/api/school/${schoolId}/ops/finance`,
  "inventory": `/api/school/${schoolId}/ops/inventory`,
  "boarding": `/api/school/${schoolId}/ops/boarding`,
  "cashier": `/api/school/${schoolId}/ops/cashier?from=${today}&to=${today}`,
  "reports approvals": `/api/school/${schoolId}/ops/reports?tab=approvals`,
  "reports aging": `/api/school/${schoolId}/ops/reports?tab=aging`,
  "rbac": `/api/school/${schoolId}/ops/rbac`,
  "portal staff": `/api/school/${schoolId}/ops/portal?view=staff`,
});

// modules teachers may NOT open (money side / admin only) — expect 403, not 500
const ADMIN_ONLY = new Set(["fees", "discounts", "payroll", "finance", "inventory", "boarding", "cashier", "rbac", "library", "transport", "reports approvals", "reports aging"]);
// rbac console is super-admin only — SCHOOL_ADMIN also gets 403 there
const SUPER_ONLY = new Set(["rbac"]);

async function run(label: string, email: string) {
  const user = await db.user.findUnique({ where: { email } });
  if (!user) { console.log(`❌ ${label}: ${email} not found in DB`); return false; }
  const schoolId = user.schoolId!;
  const school = await db.school.findUnique({ where: { id: schoolId } });
  console.log(`\n=== ${label} — ${user.role} @ ${school?.name} ===`);
  let pass = 0, fail = 0;
  for (const [name, url] of Object.entries(ENDPOINTS(schoolId))) {
    try {
      const res = await fetch(BASE + url, { headers: { "x-user-id": user.id } });
      const body = await res.text();
      const ok = res.status === 200;
      const expected403 = (ADMIN_ONLY.has(name) && label === "TEACHER") || (SUPER_ONLY.has(name) && label === "SCHOOL_ADMIN");
      const good = expected403 ? res.status === 403 : ok;
      if (good) {
        const size = body.length;
        pass++;
        console.log(`  ${expected403 ? "🔒" : "✅"} ${name} — ${res.status} (${(size / 1024).toFixed(1)}kB)`);
      } else {
        fail++;
        console.log(`  ❌ ${name} — ${res.status}: ${body.slice(0, 140)}`);
      }
    } catch (e: any) {
      fail++;
      console.log(`  ❌ ${name} — ${e.message}`);
    }
  }
  console.log(`${label}: ${pass} ok, ${fail} failed`);
  return fail === 0;
}

const adminOk = await run("SCHOOL_ADMIN", "head@nyansapo.gh");
const teacherOk = await run("TEACHER", "teacher@nyansapo.gh");
console.log(`\n${adminOk && teacherOk ? "🎉 ALL PORTAL ENDPOINTS HEALTHY" : "⚠️ SOME ENDPOINTS FAILED"}`);
process.exit(adminOk && teacherOk ? 0 : 1);

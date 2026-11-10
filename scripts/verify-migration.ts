/* Verify PostgreSQL row counts match the legacy SQLite database. */
import { PrismaClient as Pg } from "@prisma/client";
import Database from "better-sqlite3";

const pg = new Pg();
const lite = new Database("/home/z/my-project/db/legacy-sqlite-backup.db", { readonly: true });

const tables = lite.prepare("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name NOT LIKE '_prisma%'").all().map(t => t.name);

let ok = 0, bad = 0;
for (const t of tables) {
  const n = lite.prepare(`SELECT COUNT(*) n FROM "${t}"`).get().n;
  const p = await (pg as any)[t].count();
  const match = n === p;
  if (match) ok++; else bad++;
  console.log(`${match ? "✓" : "❌"} ${t}: sqlite=${n} pg=${p}`);
}
console.log(`\n${ok} tables match, ${bad} mismatch`);
await pg.$disconnect();

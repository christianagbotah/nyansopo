import Database from 'better-sqlite3';
const db = new Database('/home/z/my-project/db/custom.db', { readonly: true });
const tables = db.prepare("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name NOT LIKE '_prisma%'").all();
let total = 0;
for (const t of tables) {
  const c = db.prepare(`SELECT COUNT(*) as n FROM "${t.name}"`).get();
  if (c.n > 0) { console.log(`${t.name}: ${c.n}`); total += c.n; }
}
console.log(`TOTAL ROWS: ${total}`);

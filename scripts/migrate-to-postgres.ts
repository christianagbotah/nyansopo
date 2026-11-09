/* Migrate all data from legacy SQLite (db/custom.db) to PostgreSQL.
   - Parses prisma/schema.prisma to build the FK dependency graph
   - Topologically sorts models (errors loudly if a cycle needs manual handling)
   - Row-level topo sort for self-referencing models (AssociationNode.parentId)
   - Uses Prisma clients on both sides so Date/bool/int conversions are exact
   Run: bun scripts/migrate-to-postgres.ts
*/
import { PrismaClient } from "../generated/sqlite-client";
import { PrismaClient as Pg } from "@prisma/client";
import * as fs from "fs";

const lite = new PrismaClient();
const pg = new Pg();

const schema = fs.readFileSync("prisma/schema.prisma", "utf8");

// ── parse models ──────────────────────────────────────────────
const modelBlocks = schema.match(/^model (\w+) \{[^}]*\}/gm) ?? [];
const models: Record<string, string[]> = {}; // model -> deps
const selfRefFields: Record<string, string[]> = {}; // model -> self-ref FK fields

for (const block of modelBlocks) {
  const name = block.match(/^model (\w+)/)![1];
  const deps = new Set<string>();
  const rels = block.matchAll(/@relation(?:\([^)]*?fields:\s*\[(\w+)\][^)]*\))?/g);
  for (const relMatch of rels) {
    const fkField = relMatch[1];
    if (!fkField) continue;
    // find the line containing this relation to get the target type
    const line = block.split("\n").find(l => l.includes(`fields: [${fkField}]`));
    if (!line) continue;
    const typeName = (line.match(/^\s*(\w+)\s+([\w?]+)/) ?? [])[2]?.replace("?", "");
    if (!typeName) continue;
    if (typeName === name) {
      (selfRefFields[name] ??= []).push(fkField);
    } else if (modelBlocks.some(b => b.startsWith(`model ${typeName} `))) {
      deps.add(typeName);
    }
  }
  models[name] = [...deps];
}

// ── topological sort ──────────────────────────────────────────
const order: string[] = [];
const done = new Set<string>();
let progress = true;
while (progress) {
  progress = false;
  for (const [m, deps] of Object.entries(models)) {
    if (done.has(m)) continue;
    if (deps.every(d => done.has(d) || d === m)) { order.push(m); done.add(m); progress = true; }
  }
}
const leftovers = Object.keys(models).filter(m => !done.has(m));
if (leftovers.length) {
  console.error("❌ Unresolvable model dependencies (cycles):", leftovers);
  process.exit(1);
}
console.log(`Models: ${order.length}, insert order resolved.`);

// ── row-level topo sort for self-referencing models ───────────
function sortRows<T extends Record<string, unknown>>(rows: T[], parentField: string): T[] {
  const byId = new Map(rows.map(r => [r.id as string, r]));
  const sorted: T[] = [];
  const seen = new Set<string>();
  const visit = (row: T) => {
    const id = row.id as string;
    if (seen.has(id)) return;
    seen.add(id);
    const pid = row[parentField] as string | null;
    if (pid && byId.has(pid) && !seen.has(pid)) visit(byId.get(pid)!);
    sorted.push(row);
  };
  rows.forEach(visit);
  return sorted;
}

// ── migrate ───────────────────────────────────────────────────
const CHUNK = 300;
let total = 0;
const counts: Record<string, number> = {};

for (const model of order) {
  const rows = await (lite as any)[model].findMany();
  if (!rows.length) { counts[model] = 0; continue; }

  if (selfRefFields[model]) {
    // insert one-by-one in parent-first order (self-ref FKs are row-dependent)
    const sorted = sortRows(rows, selfRefFields[model][0]);
    for (const row of sorted) await (pg as any)[model].create({ data: row });
  } else {
    for (let i = 0; i < rows.length; i += CHUNK) {
      await (pg as any)[model].createMany({ data: rows.slice(i, i + CHUNK), skipDuplicates: false });
    }
  }
  counts[model] = rows.length;
  total += rows.length;
  console.log(`✓ ${model}: ${rows.length}`);
}

console.log(`\n🎉 Migrated ${total} rows across ${Object.keys(counts).length} models.`);
await lite.$disconnect();
await pg.$disconnect();

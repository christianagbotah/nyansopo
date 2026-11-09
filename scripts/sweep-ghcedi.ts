// One-off: strip 'GH₵' → '₵' in live DB text columns (post-currency-sweep fix).
import { PrismaClient } from "@prisma/client";
const p = new PrismaClient();

const TARGETS: [string, string][] = [
  ["FlagVerification", "note"],
  ["TransferRecord", "reason"],
  ["TransferRecord", "notes"],
  ["AdmissionInquiry", "registrySummary"],
  ["Announcement", "body"],
  ["StudentFlag", "description"],
  ["StudentFlag", "resolutionNote"],
  ["SmsMessage", "body"],
  ["QuestionPaper", "body"],
  ["ModificationRequest", "reason"],
];

const q = (ident: string) => `"${ident}"`;

async function main() {
  let total = 0;
  for (const [table, col] of TARGETS) {
    const sql = `UPDATE ${q(table)} SET ${q(col)} = REPLACE(${q(col)}, 'GH₵', '₵') WHERE ${q(col)} LIKE '%GH₵%' RETURNING 1`;
    const r = await p.$queryRawUnsafe(sql);
    if (r.length) {
      console.log(`${table}.${col}: ${r.length} rows updated`);
      total += r.length;
    }
  }
  console.log(`TOTAL: ${total} rows swept`);
  await p.$disconnect();
}

main().catch((e) => {
  console.error(e.message);
  process.exit(1);
});

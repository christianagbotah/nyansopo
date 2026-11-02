import { db } from "@/lib/db";
async function main() {
  const users = await db.user.count();
  const students = await db.student.count();
  console.log(`users=${users} students=${students}`);
  await db.$disconnect();
}
main().catch(e => { console.error(e.message); process.exit(1); });

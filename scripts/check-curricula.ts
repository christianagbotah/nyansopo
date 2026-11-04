import { PrismaClient } from "@prisma/client";
const db = new PrismaClient();
const schools = await db.school.findMany({ select: { code: true, curriculum: true, curricula: true }, orderBy: { code: "asc" } });
for (const s of schools) console.log(s.code, s.curriculum, s.curricula);
await db.$disconnect();

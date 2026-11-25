import { PrismaClient } from '@prisma/client'

const globalForPrisma = globalThis as unknown as {
  prisma: PrismaClient | undefined
}

// wave 59: query-level logging removed — the log stream was the top memory
// driver of the dev server (OOM-killed at ~2.3GB RSS → gateway 502s). Errors
// still surface through Next's route handlers; the audit trail covers ops.
export const db =
  globalForPrisma.prisma ??
  new PrismaClient({
    log: ['error', 'warn'],
  })

if (process.env.NODE_ENV !== 'production') globalForPrisma.prisma = db
# Task 3-a — Internal messaging (work record)

## Scope delivered
CI3 `message_thread`/`message` parity (iEduPro private messaging) in the Nyansapo Next.js 16 +
TS strict + Prisma/PostgreSQL app at `/home/z/my-project`: staff two-pane inbox (thread list ↔
conversation), find-or-create threads per **unordered** user pair, replies, mark-read-on-open,
≤4MB MediaAsset attachments (upload + authenticated download), delete-thread (cascade), compose
dialog with role-grouped recipient picker, unread-badge header + notification-bell item.
Wave 12 / Backlog B — **staff messaging only** (PARENT/STUDENT excluded, no `messages.use`).

## Files changed (only these five)
1. `src/app/api/school/[id]/ops/messages/route.ts` — **new**; GET inbox + POST action dispatch
   (perms `messages.use`; GET uses `strict: true` — messaging is personal, association officers
   get no thread list).
2. `src/components/modules/messages.tsx` — replaced the wave-12 placeholder with the full module.
3. `src/app/api/notifications/route.ts` — added item **6 "messages-unread"** (see below). No
   other items touched.
4. `src/components/notification-bell.tsx` — one line: `messages: MessageSquare` in `KIND_ICON`.
5. `scripts/smoke-messages.ts` — **new**; 41-check suite (a)–(l), self-cleaning.

Untouched as instructed: `prisma/schema.prisma` (orchestrator had already added MessageThread +
Message), `rbac.ts` (`messages.use` pre-existed), app-shell / page-index / store / sms / fees /
settings / backup / email / accounts. **No generic media POST route existed** (only GET
`/api/media/[id]`) and `src/lib/media.ts` caps at 700KB images-only — so the attachment upload
lives inside the messages route as action `upload-attachment`. The media GET needed **no changes**
(it has no kind whitelist; `canOperateSchool` covers both participants since sender/recipient are
same-school staff — verified 200 `image/png` in the smoke).

## API surface — `POST /api/school/{id}/ops/messages` (perms `["messages.use"]`)

| action | body | rules → result |
|---|---|---|
| `send` | `{ to, subject, body, attachmentId? }` | `to` must exist, be ACTIVE, same school, have effective `messages.use` (role-config + overrides — `hasMessagesPerm()` helper); no self-send; subject 1–120, body 1–5000 (trimmed) → 400s. Thread find-or-create checks **both directions** (CI3 `send_new_private_message`: one thread per unordered pair; subject set at creation only). Bumps `lastMessageAt`. → `{ threadId, messageId }` |
| `reply` | `{ threadId, body, attachmentId? }` | thread must exist at this school AND involve the caller (sender or recipient) else **404**; body 1–5000 → `{ messageId }` |
| `mark-read` | `{ threadId }` | participant-only else 404; `readAt=now` on messages where `senderId != me AND readAt null` (CI3 `mark_thread_messages_read`) → `{ updated }` |
| `delete-thread` | `{ threadId }` | participant-only else 404; deletes the thread — **messages cascade** (CI3 `delete`) → `{ ok }` |
| `upload-attachment` | `{ dataUrl }` | base64 data-URL; mime ∈ png/jpeg/webp/pdf; decoded ≤ **4 \* 1024 \* 1024** bytes (CI3 limit) else 400; creates `MediaAsset` kind `ATTACHMENT` → `{ attachmentId, sizeBytes, mimeType }` |

`attachmentId` passed to send/reply is validated (exists + same school + kind ATTACHMENT) else 400.

### GET `/api/school/{id}/ops/messages[?thread=<id>]`
- `threads[]` for the caller (senderId OR recipientId = me, school-scoped, `lastMessageAt` desc):
  `{ id, subject, counterpart{id, fullName, role, title, avatarSeed}, lastMessage{body (trunc 90), senderId, createdAt}, lastMessageAt, unreadCount }` — unread via one `groupBy` over the caller's thread ids.
- `unreadTotal`, `me`, `recipients[]` (compose picker: ACTIVE school users, excluding self and
  PARENT/STUDENT roles, `{id, fullName, role, title}`).
- `?thread=` → `thread{ subject, counterpart, messages[{id, senderId, senderName, body, attachmentId, readAt, createdAt}] }` oldest→newest; **404** unless the caller is a participant.
- Parent/student (no `messages.use`, not backoffice) → 403 on GET and POST (smoke (a)).

## UI notes (`src/components/modules/messages.tsx`)
- `ModuleHeader` + unread Badge + Compose button; `md:grid-cols-[minmax(280px,340px)_1fr]` with
  `md:h-[calc(100vh-13rem)]` two-pane; mobile stacks (list → tap → conversation with Back,
  `mobilePane` state; icon buttons `size-10 md:size-9` for touch).
- Thread rows: `SeedAvatar` (initials + `avatarColor(avatarSeed ?? fullName)` — same palette as
  students module), name (bold when unread), `RoleChip` (ROLE_LABELS, outline), subject, 90-char
  preview with "You: " prefix, relTime, unread count pill (9+ capped). `max-h`/flex-1 +
  `nice-scroll`.
- Conversation: header (avatar, name, role chip, subject, trash), bubbles — own right-aligned
  `bg-primary` rounded-br-sm, counterpart left `bg-secondary` rounded-bl-sm, sender name +
  `stamp()` timestamp, attachment chip → `/api/media/{id}` download; reply `Textarea`
  (**Enter sends, Shift+Enter newline**), Attach (client-side 4MB/mime check → FileReader →
  `upload-attachment` → removable chip), Send with spinner; container-scroll pinned to bottom
  (`messagesRef.scrollTop`, not `scrollIntoView` — avoids page jump).
- Mark-read fires automatically on open (then inbox refresh); compose Dialog: recipient `Select`
  **grouped by role label** (auto-searchable ≥7 items), subject (120), body (5000), attachment
  picker, validation-gated Send + toasts; delete via `AlertDialog` confirm ("removed for both of
  you"); empty states (no threads / no conversation selected) + `LoadingBlock`s; error toasts.
- Polling: lightweight GET every **45s** + on window focus (bell pattern); also refreshes the open
  conversation.

## Notification-bell integration
`/api/notifications` item 6 (after overdue fees), gated `can("messages.use")`: counts messages
where `readAt=null, senderId != user.id`, thread ∈ caller's threads (school-scoped). Shape matches
the other items exactly: `{ id: "messages-unread", kind: "messages", severity: "info", count,
title: "N unread message(s)", detail: "from {counterpart.fullName} — {thread.subject}",
module: "messages", at: latest unread createdAt }` (no `tab` — module has none). Verified live in
the browser: appears on send, disappears after mark-read, deep-links via the existing
`setModule("messages")` path. Bell icon: `MessageSquare` added to `KIND_ICON` only.

## Verification
- `DATABASE_URL=postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public bun scripts/smoke-messages.ts`
  → **41/41 passed** (run twice; second run also confirmed zero leftover threads/messages/attachments).
  Covers (a)–(l) incl. either-direction thread reuse (pair count stays 1), subject-frozen-after-create,
  mark-read counts (2), non-participant 404s (GET ?thread=, reply, delete), all send validation,
  attachment upload/serve/send, >4MB (6M base64 chars ≈ 4.5MB decoded) → 400, text/plain → 400,
  cascade delete.
- Browser E2E (agent-browser, seeded localStorage session): module renders; bell item lists;
  reply sends and bubbles appear; compose (filter "Boateng" → pick → subject/body → Send) creates
  the thread and opens it; mobile 390px shows single-pane conversation with back. Screenshots:
  `agent-ctx/shot-messages-desktop.png`, `shot-messages-mobile.png`. Demo threads + browser test
  threads cleaned up afterwards (threads=0).
- `bunx tsc --noEmit` → **0 errors**. `bunx eslint` on all 5 touched files → **0 errors/0 warnings**
  (`bun run lint` still reports 3 pre-existing errors in `app-shell.tsx` / `cashier-console.tsx`
  owned by other agents — untouched here).
- NOTE: dev server got OOM-killed once mid-task; restarted with the prescribed setsid command, no
  other impact.

## Notes for later agents
- **Portal-side messaging (parents/students) is NOT in this wave** — `recipients` excludes
  PARENT/STUDENT and `send` rejects them (400, "no access to internal messaging"). If a later wave
  opens the inbox to portals: relax `STAFF_ONLY_ROLES`, keep the per-user `messages.use` check, and
  remember `/api/media/[id]` requires `canOperateSchool` — portal accounts would need a
  participants-based download path for attachments (e.g. allow when caller is sender/recipient of a
  message referencing the asset).
- Thread creation has CI3's inherent find-then-create race (no unique constraint on the pair —
  schema owned by orchestrator). Harmless at this scale; if it ever matters, add a `@@unique`
  on a normalized (min/max) sender-recipient pair + upsert.
- Group messaging (CI3 `group_message` / `send_reply_group_message`) is out of scope — model only
  supports 1:1 threads.

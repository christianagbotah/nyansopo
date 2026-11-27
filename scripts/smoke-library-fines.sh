#!/usr/bin/env bash
# ── wave 74 — Library overdue chase & fines end-to-end smoke ──
# Exercises the full money story head-first: the fine policy editor
# (OpsSetting-backed), a deliberately-overdue loan, the guardian reminder
# scan (throttled to one SMS per loan per 3 days), the fine recorded at
# return ((days − grace) × rate, capped), the batch + per-loan fine.post
# riding the receipt engine (invoice item on the current-term invoice),
# the waive guard after billing, and a waived fine. Cleans up after itself.
set -u
BASE=http://localhost:3000
SID=cmwq8hof4000zmoe1gbjv3rr1
export DATABASE_URL='postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public'

up() { curl -s -o /dev/null -w "%{http_code}" "$BASE/" --max-time 8; }
if [ "$(up)" != "200" ]; then
  echo "dev server down — starting…"
  bash /home/z/my-project/scripts/ensure-server.sh
fi
echo "server: $(up)"

jqpy() { python3 -c "import json,sys; d=json.load(sys.stdin); $1"; }
fail() { echo "FAIL: $1"; exit 1; }

HEAD=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"head@nyansapo.gh","password":"nyansapo"}' | jqpy "print(d['user']['id'])") || fail "head login"
TEACHER=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"teacher@nyansapo.gh","password":"nyansapo"}' | jqpy "print(d['user']['id'])") || fail "teacher login"
HH="x-user-id: $HEAD"
TH="x-user-id: $TEACHER"
echo "head=$HEAD teacher=$TEACHER"

TAG="SMOKE-FINES-$(date +%s)"
echo "book tag: $TAG"

# ── 1 — baseline: policy defaults ride in OpsSetting with 50/0/2000 fallbacks
G1=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/library")
P0=$(echo "$G1" | jqpy "print(d['finePolicy']['perDayCents'])")
echo "1. baseline policy perDayCents=$P0"
[ -n "$P0" ] || fail "GET should carry finePolicy"

# ── 2 — the head tunes the policy: 80 pesewas/day, 1 grace day, 500 cap
curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/library" -H 'content-type: application/json' -d '{"action":"policy.save","perDayCents":80,"graceDays":1,"capCents":500}' > /dev/null
P1=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/library" | jqpy "print(d['finePolicy']['perDayCents'], d['finePolicy']['graceDays'], d['finePolicy']['capCents'])")
echo "2. policy saved: $P1"
[ "$P1" = "80 1 500" ] || fail "policy.save did not stick ($P1)"

# ── 3 — the teacher must not touch circulation or the policy
CODE=$(curl -s -o /dev/null -w "%{http_code}" -H "$TH" -X POST "$BASE/api/school/$SID/ops/library" -H 'content-type: application/json' -d '{"action":"policy.save","perDayCents":1,"graceDays":0,"capCents":1}')
echo "3. teacher policy.save → $CODE"
[ "$CODE" = "403" ] || fail "teacher should be refused the policy ($CODE)"

# ── 4 — catalogue a smoke book and issue it ALREADY 3 days overdue
BK=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/library" -H 'content-type: application/json' -d "{\"action\":\"book.save\",\"title\":\"$TAG Anansi wisdom tales\",\"author\":\"Smoke runner\",\"category\":\"Storybook\",\"copies\":2,\"shelf\":\"Smoke shelf\"}")
BOOK=$(echo "$BK" | jqpy "print(d['book']['id'])")
STU=$(echo "$G1" | jqpy "print(d['loans'][0]['studentId'] if d['loans'] else '')")
if [ -z "$STU" ]; then
  # no prior loans — find any learner through the issue search endpoint
  STU=$(curl -s -H "$HH" "$BASE/api/school/$SID/students?search=a" | jqpy "print(d['students'][0]['id'])") || fail "find a learner"
fi
LOAN1=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/library" -H 'content-type: application/json' -d "{\"action\":\"loan.issue\",\"bookId\":\"$BOOK\",\"studentId\":\"$STU\",\"days\":-3}")
L1=$(echo "$LOAN1" | jqpy "print(d['loan']['id'])")
echo "4. issued $L1 due 3 days ago (loan.issue accepts negative days → immediately overdue)"

# ── 5 — the reminder scan: one SMS to the guardian, then the throttle holds
SCAN1=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/library" -H 'content-type: application/json' -d '{"action":"overdue.scan"}')
S1=$(echo "$SCAN1" | jqpy "print(d['sent'])")
echo "5. scan#1 sent=$S1 $(echo "$SCAN1" | jqpy "print('failed=' + str(d['failed']))")"
[ "$S1" -ge 1 ] || fail "first scan should send at least one guardian SMS"
SCAN2=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/library" -H 'content-type: application/json' -d '{"action":"overdue.scan"}')
S2=$(echo "$SCAN2" | jqpy "print(d['sent'])")
SK=$(echo "$SCAN2" | jqpy "print(d['skipped'])")
echo "5b. scan#2 sent=$S2 skipped=$SK (3-day throttle)"
[ "$S2" = "0" ] || fail "throttle should hold the second scan back"

# ── 6 — GET shows the accruing fine: (daysOverdue − 1 grace) × 80, capped at 500.
#    daysOverdue is whatever the board says (a millisecond past the issue sets ceil over).
F=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/library" | jqpy "
loan = [l for l in d['loans'] if l['id'] == '$L1'][0]
exp = min(max(loan['daysOverdue'] - 1, 0) * 80, 500)
print(loan['computedFineCents'], exp, loan['overdueSmsCount'], loan['overdue'], loan['daysOverdue'])")
ACT=$(echo "$F" | cut -d' ' -f1)
EXP=$(echo "$F" | cut -d' ' -f2)
echo "6. accruing: $F (computed, expected, sms count, overdue, days)"
[ "$ACT" = "$EXP" ] || fail "accruing fine wrong ($F)"

# ── 7 — return the late book: the same fine recorded in the response
RET=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/library" -H 'content-type: application/json' -d "{\"action\":\"loan.return\",\"loanId\":\"$L1\"}")
RC=$(echo "$RET" | jqpy "print(d['fineCents'])")
echo "7. returned — fine recorded=$RC"
[ "$RC" = "$ACT" ] || fail "fine at return should match the accruing amount ($RC vs $ACT)"

# ── 8 — post it: the fine rides the receipt engine onto the current-term invoice
#    (per-loan post — the batch may carry fines from other smoke runs, but this loan's
#    fine is exactly $ACT and posting it must bill exactly one line)
POST=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/library" -H 'content-type: application/json' -d "{\"action\":\"fine.post\",\"loanId\":\"$L1\"}")
PN=$(echo "$POST" | jqpy "print(d['posted'])")
PT=$(echo "$POST" | jqpy "print(d['postedTotalCents'])")
echo "8. posted=$PN total=$PT"
[ "$PN" = "1" ] || fail "one fine should post ($PN)"
[ "$PT" = "$ACT" ] || fail "posted total should be $ACT ($PT)"

# ── 9 — waive after billing is refused (credit through the cashier instead)
W2=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/library" -H 'content-type: application/json' -d "{\"action\":\"fine.waive\",\"loanId\":\"$L1\"}")
echo "9. waive-after-post → $(echo "$W2" | jqpy "print(d.get('error','ok'))")"
echo "$W2" | jqpy "exit(0 if 'already on an invoice' in d.get('error','') else 1)" || fail "waive must be refused after billing"

# ── 10 — a second late loan, waived before billing: mercy works
LOAN2=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/library" -H 'content-type: application/json' -d "{\"action\":\"loan.issue\",\"bookId\":\"$BOOK\",\"studentId\":\"$STU\",\"days\":-2}")
L2=$(echo "$LOAN2" | jqpy "print(d['loan']['id'])")
R2=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/library" -H 'content-type: application/json' -d "{\"action\":\"loan.return\",\"loanId\":\"$L2\"}")
R2F=$(echo "$R2" | jqpy "print(d['fineCents'])")
echo "10a. second loan returned, fine=$R2F"
[ "$R2F" -ge 80 ] || fail "second late return should carry a fine"
W=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/library" -H 'content-type: application/json' -d "{\"action\":\"fine.waive\",\"loanId\":\"$L2\"}")
WV=$(echo "$W" | jqpy "print(d['loan']['fineCents'])")
echo "10b. waived → fineCents=$WV"
[ "$WV" = "0" ] || fail "waive should zero the fine ($WV)"

# ── 11 — history carries the billed badge; posted tally grew
FIN=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/library" | jqpy "
loan = [l for l in d['loans'] if l['id'] == '$L1'][0]
print(loan['finePostedAt'] is not None, d['fines']['posted'] >= 1)")
echo "11. billed flag + posted tally: $FIN"
[ "$FIN" = "True True" ] || fail "finePostedAt / tally wrong ($FIN)"

# ── 12 — restore the default policy and clean every smoke row (prefix match)
curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/library" -H 'content-type: application/json' -d '{"action":"policy.save","perDayCents":50,"graceDays":0,"capCents":2000}' > /dev/null
bun /home/z/my-project/scripts/cleanup-library-smoke.ts "SMOKE-FINES" || fail "cleanup"
echo "12. policy restored, smoke rows cleaned"

echo "=== ALL WAVE-74 LIBRARY SMOKE CHECKS PASSED ==="

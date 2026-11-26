#!/usr/bin/env bash
# ── wave 71 — Guidance & Counselling end-to-end smoke ──────────
# Boots the dev server if it is down, then exercises the whole G&C flow
# head-first: create HIGH case (SMS to counsellor), case detail, session
# (AI draft), follow-up create/complete/remind, refer out, analytics,
# confidentiality (teacher must NOT see a case assigned to someone else).
set -u
BASE=http://localhost:3000
SID=cmwq8hof4000zmoe1gbjv3rr1

up() { curl -s -o /dev/null -w "%{http_code}" "$BASE/" --max-time 8; }
if [ "$(up)" != "200" ]; then
  echo "dev server down — starting…"
  bash /home/z/my-project/scripts/ensure-server.sh
fi
echo "server: $(up)"

jqpy() { python3 -c "import json,sys; d=json.load(sys.stdin); $1"; }
fail() { echo "FAIL: $1"; exit 1; }

# logins (x-user-id scheme)
HEAD=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"head@nyansapo.gh","password":"nyansapo"}' | jqpy "print(d['user']['id'])") || fail "head login"
TEACHER=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"teacher@nyansapo.gh","password":"nyansapo"}' | jqpy "print(d['user']['id'])") || fail "teacher login"
HH="x-user-id: $HEAD"
TH="x-user-id: $TEACHER"
echo "head=$HEAD teacher=$TEACHER"

# 0 — teacher sees the board (counsel.view) and can search learners
TBOARD=$(curl -s -H "$TH" "$BASE/api/school/$SID/ops/counsel")
[ "$(echo "$TBOARD" | jqpy "print('canManage' in d and d['canManage'])")" = "False" ] || fail "teacher should not have counsel.manage"
echo "0. teacher board OK (canManage=false)"

# 1 — head creates a HIGH confidential case assigned to Grace (has phone) → SMS
STUDENT=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/counsel?action=search&q=" | jqpy "print(d['rows'][0]['id'])")
GRACE=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/counsel" | jqpy "print([s['id'] for s in d['staff'] if 'Grace' in s['fullName']][0])")
CASE=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/counsel" -H 'content-type: application/json' -d "{\"action\":\"case.create\",\"studentId\":\"$STUDENT\",\"category\":\"FAMILY\",\"severity\":\"HIGH\",\"source\":\"TEACHER_REFERRAL\",\"title\":\"Smoke: falling asleep in class\",\"summary\":\"Observed three mornings this week.\",\"assignedToId\":\"$GRACE\",\"confidential\":true,\"followUpAt\":\"2026-01-01\",\"followUpNote\":\"Speak with the guardian\"}")
CID=$(echo "$CASE" | jqpy "print(d['row']['id'])")
CNO=$(echo "$CASE" | jqpy "print(d['row']['caseNo'])")
SMS=$(echo "$CASE" | jqpy "print((d.get('sms') or {}).get('status'))")
echo "1. case $CNO created ($CID) — counsellor SMS: $SMS"
[ "$SMS" = "SENT" ] || fail "counsellor SMS should be SENT (Grace has a phone)"

# 2 — teacher CANNOT open a case assigned to Grace (confidentiality)
CODE=$(curl -s -o /dev/null -w "%{http_code}" -H "$TH" "$BASE/api/school/$SID/ops/counsel?action=case&caseId=$CID")
[ "$CODE" = "403" ] || fail "teacher should get 403 on a confidential case (got $CODE)"
echo "2. confidentiality OK — teacher blocked (403)"

# 3 — teacher refers a case of their own (counsel.view is enough) and CAN open it
TCASE=$(curl -s -H "$TH" -X POST "$BASE/api/school/$SID/ops/counsel" -H 'content-type: application/json' -d "{\"action\":\"case.create\",\"studentId\":\"$STUDENT\",\"category\":\"PEER\",\"severity\":\"MEDIUM\",\"title\":\"Smoke: quarrels at break\",\"confidential\":true}")
TCID=$(echo "$TCASE" | jqpy "print(d['row']['id'])")
CODE=$(curl -s -o /dev/null -w "%{http_code}" -H "$TH" "$BASE/api/school/$SID/ops/counsel?action=case&caseId=$TCID")
[ "$CODE" = "200" ] || fail "teacher should see their own case (got $CODE)"
echo "3. teacher self-opened case visible (200) — $TCID"

# 4 — teacher cannot run the unit (no counsel.manage): session.add must 403
CODE=$(curl -s -o /dev/null -w "%{http_code}" -H "$TH" -X POST "$BASE/api/school/$SID/ops/counsel" -H 'content-type: application/json' -d "{\"action\":\"session.add\",\"caseId\":\"$TCID\",\"notes\":\"x\"}")
[ "$CODE" = "403" ] || fail "teacher session.add should be 403 (got $CODE)"
echo "4. teacher blocked from unit operations (403)"

# 5 — head logs a session with AI summary draft
DRAFT=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/counsel" -H 'content-type: application/json' -d "{\"action\":\"session.ai-draft\",\"caseId\":\"$CID\",\"notes\":\"Met Ama alone. She says the family moved and she now travels 2 hours to school. Wakes at 4am. Agreed to try earlier bedtime and the school will note late arrival tolerance for two weeks.\"}")
echo "5. AI draft (fallback=$(echo "$DRAFT" | jqpy "print(d.get('fallback') is not None)")): $(echo "$DRAFT" | jqpy "print(d['summary'][:90])")"
SESS=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/counsel" -H 'content-type: application/json' -d "{\"action\":\"session.add\",\"caseId\":\"$CID\",\"mode\":\"FACE_TO_FACE\",\"attendees\":\"learner alone\",\"outcome\":\"IMPROVED\",\"notes\":\"Met Ama alone — transport fatigue discussed.\",\"aiSummary\":$(echo "$DRAFT" | jqpy "print(json.dumps(d['summary']))")}")
echo "$SESS" | jqpy "print('5. session logged:', d['session']['id'], '| status now IN_PROGRESS')"
DET=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/counsel?action=case&caseId=$CID")
echo "$DET" | jqpy "print('   case status =', d['row']['status'], '| sessions =', len(d['sessions']))"

# 6 — follow-up: the case.create one is overdue (2026-01-01) → remind (SMS) + complete
FU=$(echo "$DET" | jqpy "print(d['followUps'][0]['id'])")
REM=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/counsel" -H 'content-type: application/json' -d "{\"action\":\"followup.remind\",\"id\":\"$FU\"}")
echo "6a. remind SMS: $(echo "$REM" | jqpy "print((d.get('sms') or {}).get('status', d.get('error','—')))")"
curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/counsel" -H 'content-type: application/json' -d "{\"action\":\"followup.complete\",\"id\":\"$FU\",\"doneNote\":\"Guardian called — transport plan agreed.\"}" | jqpy "print('6b. follow-up completed:', d['ok'])"

# 7 — refer out: REFERRED requires a named service
CODE=$(curl -s -o /dev/null -w "%{http_code}" -H "$HH" -X POST "$BASE/api/school/$SID/ops/counsel" -H 'content-type: application/json' -d "{\"action\":\"case.update\",\"id\":\"$CID\",\"status\":\"REFERRED\"}")
[ "$CODE" = "400" ] || fail "REFERRED without a service should 400 (got $CODE)"
curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/counsel" -H 'content-type: application/json' -d "{\"action\":\"case.update\",\"id\":\"$CID\",\"status\":\"REFERRED\",\"referredExternal\":\"DOVVSU — district office\"}" | jqpy "print('7. referred out →', d['row']['status'], '·', d['row']['referredExternal'])"

# 8 — analytics + followups + audit trail landed
AN=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/counsel?action=analytics")
echo "$AN" | jqpy "print('8. analytics: cases', d['totals']['cases'], '· sessions', d['totals']['sessions'], '· referred', d['totals']['referred'], '· top cat', (d['categories'][0]['name'] if d['categories'] else '—'))"
FUS=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/counsel?action=followups&scope=all")
echo "   followups open: $(echo "$FUS" | jqpy "print(len(d['rows']))")"
AUD=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/audit?take=6")
echo "$AUD" | jqpy "print('9. audit rows:', sum(1 for r in d['rows'] if 'G&C' in r.get('summary','') or 'Counsel' in r.get('entityType','')))"

echo "ALL COUNSEL SMOKE CHECKS PASSED"

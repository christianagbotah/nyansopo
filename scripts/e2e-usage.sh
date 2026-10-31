#!/usr/bin/env bash
# Transport-usage E2E (iedupro §1): NOT_USING waives, MORNING halves the fare.
# Runs against a seeded weekday (2026-10-29, Thursday).
set -e
BASE=http://localhost:3000
D=2026-10-29
ADMIN=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"head@nyansapo.gh","password":"nyansapo"}' | python3 -c "import json,sys; print(json.load(sys.stdin)['user']['id'])")
SCHOOL=$(curl -s -H "x-user-id: $ADMIN" $BASE/api/context | python3 -c "import json,sys; d=json.load(sys.stdin); print([s['id'] for s in d['schools'] if s['code']=='GEA'][0])")

echo "== weekday $D snapshot =="
curl -s -H "x-user-id: $ADMIN" "$BASE/api/school/$SCHOOL/ops/daily?date=$D" -o /tmp/wd.json
python3 - <<'PY'
import json
d = json.load(open('/tmp/wd.json'))
cands = [s for s in d['posStudents'] if s.get('todayTransport') and s['todayTransport']['status']=='OWED' and s['balance'] > 0]
print("OWED transport charges today:", len(cands))
for s in cands[:4]:
    print("  ", s['name'], "| fare:", s['todayTransport']['amount'], "| balance:", s['balance'])
json.dump({'a': cands[0], 'b': cands[1]}, open('/tmp/ucands.json','w'))
PY

A_ID=$(python3 -c "import json; print(json.load(open('/tmp/ucands.json'))['a']['id'])")
A_NAME=$(python3 -c "import json; print(json.load(open('/tmp/ucands.json'))['a']['name'])")
A_FARE=$(python3 -c "import json; print(json.load(open('/tmp/ucands.json'))['a']['todayTransport']['amount'])")
B_ID=$(python3 -c "import json; print(json.load(open('/tmp/ucands.json'))['b']['id'])")
B_NAME=$(python3 -c "import json; print(json.load(open('/tmp/ucands.json'))['b']['name'])")
B_FARE=$(python3 -c "import json; print(json.load(open('/tmp/ucands.json'))['b']['todayTransport']['amount'])")

echo "== A: $A_NAME — NOT_USING waives the fare (fare=$A_FARE) =="
curl -s -H "x-user-id: $ADMIN" -X POST $BASE/api/school/$SCHOOL/ops/daily -H 'content-type: application/json' \
  -d "{\"action\":\"pos-collect\",\"studentId\":\"$A_ID\",\"amountCents\":100,\"method\":\"CASH\",\"paidAt\":\"$D\",\"date\":\"$D\",\"transportUsage\":\"NOT_USING\",\"markPresent\":true}" -o /tmp/ua.json -w "[%{http_code}]\n"
python3 -c "import json; d=json.load(open('/tmp/ua.json')); print('  applied:', d['payment']['applied'], '| present:', d.get('present'))" 2>/dev/null || cat /tmp/ua.json

echo "== B: $B_NAME — MORNING halves the fare (fare=$B_FARE) =="
curl -s -H "x-user-id: $ADMIN" -X POST $BASE/api/school/$SCHOOL/ops/daily -H 'content-type: application/json' \
  -d "{\"action\":\"pos-collect\",\"studentId\":\"$B_ID\",\"amountCents\":100,\"method\":\"CASH\",\"paidAt\":\"$D\",\"date\":\"$D\",\"transportUsage\":\"MORNING\"}" -o /tmp/ub.json -w "[%{http_code}]\n"
python3 -c "import json; d=json.load(open('/tmp/ub.json')); print('  applied:', d['payment']['applied'])" 2>/dev/null || cat /tmp/ub.json

echo "== verify charge states after usage stamps =="
curl -s -H "x-user-id: $ADMIN" "$BASE/api/school/$SCHOOL/ops/daily?date=$D" -o /tmp/wd2.json
python3 - <<PY
import json
d = json.load(open('/tmp/wd2.json'))
for sid, fare, label in [("$A_ID", $A_FARE, "A (NOT_USING)"), ("$B_ID", $B_FARE, "B (MORNING)")]:
    s = [x for x in d['posStudents'] if x['id'] == sid][0]
    t = s['todayTransport']
    print(f"{label}: {s['name']} | status={t['status']} | amount={t['amount']} (was {fare}) | usage={t['usage']}")
PY
echo "== usage E2E done =="

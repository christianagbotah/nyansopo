#!/usr/bin/env bash
# End-to-end exercise of the daily-fees deep-parity layer (iedupro §1).
# Logs in as head (admin) + bursar + teacher, drives the new API actions,
# and verifies balances. Leaves demo data in a sensible state (HYBRID mode).
set -e
BASE=http://localhost:3000
# stateless demo auth: x-user-id header
ADMIN=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"head@nyansapo.gh","password":"nyansapo"}' | python3 -c "import json,sys; print(json.load(sys.stdin)['user']['id'])")
BURSAR=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"bursar@nyansapo.gh","password":"nyansapo"}' | python3 -c "import json,sys; print(json.load(sys.stdin)['user']['id'])")
TEACHER=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"teacher@nyansapo.gh","password":"nyansapo"}' | python3 -c "import json,sys; print(json.load(sys.stdin)['user']['id'])")
HA() { echo "x-user-id: $1"; }
echo "admin=$ADMIN bursar=$BURSAR teacher=$TEACHER"

echo "== 2. resolve GEA + date =="
SCHOOL=$(curl -s -H "x-user-id: $ADMIN" $BASE/api/context | python3 -c "import json,sys; d=json.load(sys.stdin); print([s['id'] for s in d['schools'] if s['code']=='GEA'][0])")
DATE=$(date +%F)
echo "school=$SCHOOL date=$DATE"

echo "== 3. GET ops/daily (admin) — new payload keys =="
curl -s -H "x-user-id: $ADMIN" "$BASE/api/school/$SCHOOL/ops/daily?date=$DATE" -o /tmp/daily.json
python3 - <<'PY'
import json
d = json.load(open('/tmp/daily.json'))
print("settings:", d['settings']['collectionMode'], d['settings']['teacherPerms'])
print("classrooms:", len(d['settings']['classrooms']))
print("wallets:", len(d['wallets']), "| total prepaid:", sum(w['prepaid'] for w in d['wallets'])/100, "GHS | transport:", sum(w['transport'] for w in d['wallets'])/100)
print("walletTxns:", len(d['walletTxns']))
print("transport services:", [s['name'] for s in d['services'] if s['category']=='TRANSPORT'])
print("posStudents sample:", [(s['name'], s['balance'], s['wallet']['prepaidCents']) for s in d['posStudents'] if s['balance']>0][:3])
withbal = [s for s in d['posStudents'] if s['balance'] > 0]
paid  = [s for s in d['posStudents'] if s['wallet']['prepaidCents'] > 0 and s['balance'] > 0]
json.dump({'withbal': [s['id'] for s in withbal[:5]], 'wallet_student': paid[0]['id'] if paid else None, 'name': paid[0]['name'] if paid else None}, open('/tmp/picks.json','w'))
print("picked wallet student:", paid[0]['name'] if paid else None, "with balance", paid[0]['balance'] if paid else None)
PY

echo "== 4. settings-save: CASHIER-only mode =="
curl -s -H "x-user-id: $ADMIN" -X POST $BASE/api/school/$SCHOOL/ops/daily -H 'content-type: application/json' \
  -d '{"action":"settings-save","collectionMode":"CASHIER","teacherPerms":{"mode":"SELECTIVE","classroomIds":[],"serviceIds":[]}}' | head -c 200; echo

echo "== 5. teacher tries to collect (expect 403 cashier-only) =="
STU=$(python3 -c "import json; print(json.load(open('/tmp/picks.json'))['withbal'][0])")
AMT=$(python3 -c "import json; d=json.load(open('/tmp/daily.json')); s=[x for x in d['posStudents'] if x['id']=='$STU'][0]; print(s['balance'])")
curl -s -H "x-user-id: $TEACHER" -X POST $BASE/api/school/$SCHOOL/ops/daily -H 'content-type: application/json' \
  -d "{\"action\":\"pos-collect\",\"studentId\":\"$STU\",\"amountCents\":$AMT,\"method\":\"CASH\",\"date\":\"$DATE\"}" -w " [%{http_code}]"; echo

echo "== 6. teacher tries management action (expect 403) =="
curl -s -H "x-user-id: $TEACHER" -X POST $BASE/api/school/$SCHOOL/ops/daily -H 'content-type: application/json' \
  -d "{\"action\":\"wallet-topup\",\"studentId\":\"$STU\",\"wallet\":\"PREPAID\",\"amountCents\":500}" -w " [%{http_code}]"; echo

echo "== 7. bursar collects under CASHIER mode (expect 200) + backdate + mark-present attempt =="
curl -s -H "x-user-id: $BURSAR" -X POST $BASE/api/school/$SCHOOL/ops/daily -H 'content-type: application/json' \
  -d "{\"action\":\"pos-collect\",\"studentId\":\"$STU\",\"amountCents\":$AMT,\"method\":\"CASH\",\"paidAt\":\"$DATE\",\"date\":\"$DATE\",\"markPresent\":true,\"reference\":\"E2E-1\"}" -o /tmp/collect.json -w "[%{http_code}]\n"
python3 -c "import json; d=json.load(open('/tmp/collect.json')); print('receipt:', d['payment']['receiptNo'], '| applied:', d['payment']['applied'], '| present:', d.get('present'))"

echo "== 8. wallet top-up (bursar) then pay-from-prepaid-wallet =="
WSTU=$(python3 -c "import json; print(json.load(open('/tmp/picks.json'))['wallet_student'])")
curl -s -H "x-user-id: $BURSAR" -X POST $BASE/api/school/$SCHOOL/ops/daily -H 'content-type: application/json' \
  -d "{\"action\":\"wallet-topup\",\"studentId\":\"$WSTU\",\"wallet\":\"PREPAID\",\"amountCents\":1000,\"note\":\"E2E top-up\"}" -o /tmp/topup.json -w "[%{http_code}]\n"
python3 -c "import json; d=json.load(open('/tmp/topup.json')); print('wallet now:', d['wallet']['prepaidCents'], '| txn:', d['txn']['kind'], d['txn']['balanceAfter'])"
WAMT=$(python3 -c "import json; print(min(500, json.load(open('/tmp/topup.json'))['wallet']['prepaidCents']))")
curl -s -H "x-user-id: $BURSAR" -X POST $BASE/api/school/$SCHOOL/ops/daily -H 'content-type: application/json' \
  -d "{\"action\":\"pos-collect\",\"studentId\":\"$WSTU\",\"amountCents\":$WAMT,\"payFrom\":\"PREPAID\",\"paidAt\":\"$DATE\",\"date\":\"$DATE\",\"reference\":\"E2E-wallet\"}" -o /tmp/wpay.json -w "[%{http_code}]\n"
python3 -c "import json; d=json.load(open('/tmp/wpay.json')); print('method:', d['payment']['method'], '| applied:', d['payment']['applied'])"

echo "== 9. transport usage: NOT_USING waives today's fare =="
TSTU=$(python3 -c "
import json
d=json.load(open('/tmp/daily.json'))
c=[s for s in d['posStudents'] if s.get('todayTransport')]
print(c[0]['id'] if c else '')")
if [ -n "$TSTU" ]; then
  echo "student with transport charge today: $TSTU"
  curl -s -H "x-user-id: $BURSAR" -X POST $BASE/api/school/$SCHOOL/ops/daily -H 'content-type: application/json' \
    -d "{\"action\":\"pos-collect\",\"studentId\":\"$TSTU\",\"amountCents\":100,\"payFrom\":\"PREPAID\"}" -o /dev/null -w "pre-state collect skipped [%{http_code}]\n" || true
  python3 - <<PY
import json, urllib.request
# find a student with an OWED transport charge today
d = json.load(open('/tmp/daily.json'))
cands = [s for s in d['posStudents'] if s.get('todayTransport') and s['todayTransport']['status']=='OWED']
if cands:
    sid = cands[0]['id']; name = cands[0]['name']
    amt = cands[0]['todayTransport']['amount']
    body = json.dumps({"action":"pos-collect","studentId":sid,"amountCents":amt,"payFrom":"PREPAID","paidAt":"$DATE","date":"$DATE","transportUsage":"NOT_USING"}).encode()
    # first top up a wallet so payment can proceed — or expect waiver to zero the need
    print(f"usage test on {name}: today's fare {amt/100:.2f} GHS (OWED)")
    open('/tmp/tstu.json','w').write(json.dumps({'id': sid, 'amount': amt}))
else:
    print("no OWED transport charge today — usage E2E skipped (seed history covers other days)")
PY
  if [ -f /tmp/tstu.json ]; then
    TS=$(python3 -c "import json; print(json.load(open('/tmp/tstu.json'))['id'])")
    TA=$(python3 -c "import json; print(json.load(open('/tmp/tstu.json'))['amount'])")
    # NOT_USING waives the fare, so any tiny remaining balance settles; amount=0 case:
    curl -s -H "x-user-id: $BURSAR" -X POST $BASE/api/school/$SCHOOL/ops/daily -H 'content-type: application/json' \
      -d "{\"action\":\"pos-collect\",\"studentId\":\"$TS\",\"amountCents\":$TA,\"paidAt\":\"$DATE\",\"date\":\"$DATE\",\"transportUsage\":\"NOT_USING\",\"markPresent\":true}" -o /tmp/tres.json -w "[%{http_code}]\n"
    python3 -c "import json; d=json.load(open('/tmp/tres.json')); print('present:', d.get('present'), '| applied:', d['payment'].get('applied')) if 'payment' in d else print(d)"
    # verify the transport charge got WAIVED
    curl -s -H "x-user-id: $ADMIN" "$BASE/api/school/$SCHOOL/ops/daily?date=$DATE" | python3 -c "
import json,sys
d=json.load(sys.stdin)
s=[x for x in d['posStudents'] if x['id']=='$TS'][0]
print('todayTransport after NOT_USING:', s['todayTransport'])"
  fi
else
  echo "(no transport charge found today)"
fi

echo "== 10. restore HYBRID + teacher ALL =="
curl -s -H "x-user-id: $ADMIN" -X POST $BASE/api/school/$SCHOOL/ops/daily -H 'content-type: application/json' \
  -d '{"action":"settings-save","collectionMode":"HYBRID","teacherPerms":{"mode":"ALL","classroomIds":[],"serviceIds":[]}}' -w " [%{http_code}]"; echo

echo "== 11. teacher collects under HYBRID (expect 200) =="
curl -s -H "x-user-id: $TEACHER" -X POST $BASE/api/school/$SCHOOL/ops/daily -H 'content-type: application/json' \
  -d "{\"action\":\"pos-collect\",\"studentId\":\"$WSTU\",\"amountCents\":100,\"method\":\"CASH\",\"paidAt\":\"$DATE\",\"date\":\"$DATE\"}" -o /tmp/tcpay.json -w "[%{http_code}]\n"
python3 -c "import json; d=json.load(open('/tmp/tcpay.json')); print('teacher collect ok:', d['payment']['receiptNo'], d['payment']['receivedByName'])" 2>/dev/null || cat /tmp/tcpay.json

echo "== 12. final GET — wallet ledger consistency =="
curl -s -H "x-user-id: $ADMIN" "$BASE/api/school/$SCHOOL/ops/daily?date=$DATE" -o /tmp/daily2.json
python3 - <<'PY'
import json
d = json.load(open('/tmp/daily2.json'))
w = [x for x in d['wallets']]
print("mode now:", d['settings']['collectionMode'], "| perms:", d['settings']['teacherPerms']['mode'])
print("wallet txns:", len(d['walletTxns']), "| latest 3:")
for t in d['walletTxns'][:3]:
    print("  ", t['student'], t['wallet'], t['kind'], f"{t['amount']/100:.2f}", "→ bal", f"{t['balanceAfter']/100:.2f}")
tot = sum(x['prepaid'] for x in w); print("total prepaid:", f"{tot/100:.2f}", "GHS across", len([x for x in w if x['prepaid']>0]), "wallets")
PY
echo "== E2E DONE =="

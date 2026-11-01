#!/bin/bash
# Feature test — parity-2 batch (final iEduPro CI3 parity):
#   boarding · barcode scanner · portfolio SBA · cumulative reports ·
#   WAEC grading · question papers · marks SMS · modification approvals ·
#   SSNIT reports · aging · cashier summary/handover · library requests · audit log
set -u
BASE=http://127.0.0.1:3000
PASS=0; FAIL=0
ok()  { PASS=$((PASS+1)); echo "  ✔ $1"; }
bad() { FAIL=$((FAIL+1)); echo "  ✘ $1"; }
jq()   { python3 -c "
import sys,json
d=json.load(sys.stdin)
v=eval(sys.argv[1])
if v is None: print('')
elif isinstance(v,(int,float,bool,str)): print(v)
else: print(json.dumps(v))
" "$1" 2>/dev/null; }

for i in $(seq 1 30); do curl -s -o /dev/null --max-time 3 "$BASE" && break; sleep 2; done
curl -s -o /dev/null --max-time 5 "$BASE" || { echo "SERVER DOWN"; exit 1; }

login() { curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d "{\"email\":\"$1@nyansapo.gh\",\"password\":\"nyansapo\"}"; }
SU=$(login super);   SU_ID=$(echo "$SU" | jq "d['user']['id']");   SCHOOL=$(echo "$SU" | jq "d['user']['schoolId']")
CA=$(login cashier); CA_ID=$(echo "$CA" | jq "d['user']['id']")
TE=$(login teacher); TE_ID=$(echo "$TE" | jq "d['user']['id']")
LI=$(login librarian); LI_ID=$(echo "$LI" | jq "d['user']['id']")
ST=$(login student); ST_ID=$(echo "$ST" | jq "d['user']['id']"); STU_ID=$(echo "$ST" | jq "d['user']['studentId']")
echo "═══ parity-2 test · school=$SCHOOL ═══"

# ═══════════ 1. BOARDING ═══════════
B=$(curl -s "$BASE/api/school/$SCHOOL/ops/boarding" -H "x-user-id: $SU_ID")
BH=$(echo "$B" | jq "len(d['houses'])")
BB=$(echo "$B" | jq "len(d['beds'])")
BO=$(echo "$B" | jq "d['stats']['occupancyRate'] > 0")
[ "${BH:-0}" -ge 2 ] 2>/dev/null && ok "boarding: $BH houses" || bad "boarding houses ($BH)"
[ "${BB:-0}" -ge 30 ] 2>/dev/null && ok "boarding: $BB beds" || bad "boarding beds ($BB)"
[ "$BO" = "True" ] && ok "boarding: occupancy rate computed" || bad "boarding occupancy"
FREEBED=$(echo "$B" | jq "[b for b in d['beds'] if b['status']=='AVAILABLE'][0]['id']")
LEARNER=$(echo "$B" | jq "[l for l in d['activeLearners'] if not l.get('bedCode')][0]['id']")
AS=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/boarding" -H 'content-type: application/json' -H "x-user-id: $SU_ID" -d "{\"action\":\"bed.assign\",\"bedId\":\"$FREEBED\",\"studentId\":\"$LEARNER\"}")
echo "$AS" | grep -q '"status":"ASSIGNED"' && ok "boarding: bed assign round-trip" || bad "bed assign: $AS"
RL=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/boarding" -H 'content-type: application/json' -H "x-user-id: $SU_ID" -d "{\"action\":\"bed.release\",\"bedId\":\"$FREEBED\"}")
echo "$RL" | grep -q '"status":"AVAILABLE"' && ok "boarding: bed release round-trip" || bad "bed release: $RL"
TM=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/api/school/$SCHOOL/ops/boarding" -H 'content-type: application/json' -H "x-user-id: $TE_ID" -d '{"action":"house.create","name":"X"}')
[ "$TM" = "403" ] && ok "boarding: teacher blocked from manage (403)" || bad "teacher house.create → $TM"

# ═══════════ 2. BARCODE SCANNER ═══════════
SCAN_DATE=$(date +%F)
SJSON=$(curl -s "$BASE/api/school/$SCHOOL/students" -H "x-user-id: $SU_ID")
OWING=$(echo "$SJSON" | jq "[s for s in d['students'] if s['invoice'] and s['invoice']['billed'] - s['invoice']['paid'] > 0][0]")
CLEAN=$(echo "$SJSON" | jq "[s for s in d['students'] if not s['invoice'] or s['invoice']['billed'] - s['invoice']['paid'] <= 0][0]['id']")
CLEAN_CODE=$(echo "$SJSON" | jq "[s for s in d['students'] if not s['invoice'] or s['invoice']['billed'] - s['invoice']['paid'] <= 0][0]['nyansapoIndex']")
OWING_CODE=$(echo "$OWING" | jq "d['nyansapoIndex']")
OWING_AMT=$(echo "$OWING" | jq "d['invoice']['billed'] - d['invoice']['paid']")
OWID=$(echo "$OWING" | jq "d['id']")
S1=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/scanner" -H 'content-type: application/json' -H "x-user-id: $SU_ID" -d "{\"action\":\"scan\",\"code\":\"$CLEAN_CODE\",\"mode\":\"CHECK_IN\"}")
echo "$S1" | grep -q '"granted":true' && ok "scanner: check-in granted for $CLEAN_CODE" || bad "scan: $S1"
S2=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/scanner" -H 'content-type: application/json' -H "x-user-id: $SU_ID" -d "{\"action\":\"scan\",\"code\":\"$CLEAN_CODE\",\"mode\":\"CHECK_IN\"}")
echo "$S2" | grep -q '"duplicate":true' && ok "scanner: duplicate scan rejected" || bad "dup scan: $S2"
S3=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/scanner" -H 'content-type: application/json' -H "x-user-id: $SU_ID" -d "{\"action\":\"scan\",\"code\":\"$OWING_CODE\",\"mode\":\"CHECK_IN\"}")
echo "$S3" | grep -q '"denied":true' && ok "scanner: arrears gate denied (${OWING_AMT}c owing)" || bad "arrears gate: $S3"
S4=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/scanner" -H 'content-type: application/json' -H "x-user-id: $SU_ID" -d "{\"action\":\"scan\",\"code\":\"$OWING_CODE\",\"mode\":\"CHECK_IN\",\"approve\":true}")
echo "$S4" | grep -q '"granted":true' && ok "scanner: approve bypass admits owing learner" || bad "approve bypass: $S4"
S5=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/scanner" -H 'content-type: application/json' -H "x-user-id: $SU_ID" -d "{\"action\":\"scan\",\"code\":\"$CLEAN_CODE\",\"mode\":\"CHECK_OUT\"}")
echo "$S5" | grep -q '"checkedOut":true' && ok "scanner: check-out recorded" || bad "checkout: $S5"
S6=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/scanner" -H 'content-type: application/json' -H "x-user-id: $SU_ID" -d "{\"action\":\"scan\",\"code\":\"NY-9999-999999\",\"mode\":\"CHECK_IN\"}")
echo "$S6" | grep -q '"found":false' && ok "scanner: unknown code handled" || bad "unknown: $S6"
C1=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/scanner" -H 'content-type: application/json' -H "x-user-id: $SU_ID" -d "{\"action\":\"cancel\",\"studentId\":\"$CLEAN\",\"date\":\"$SCAN_DATE\"}")
echo "$C1" | grep -q '"cancelled":true' && ok "scanner: cancel cleans up" || bad "cancel: $C1"
C2=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/scanner" -H 'content-type: application/json' -H "x-user-id: $SU_ID" -d "{\"action\":\"cancel\",\"studentId\":\"$OWID\",\"date\":\"$SCAN_DATE\"}")
echo "$C2" | grep -q '"cancelled":true' && ok "scanner: cancel owing learner scan" || bad "cancel2: $C2"
S7=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/api/school/$SCHOOL/ops/scanner" -H 'content-type: application/json' -H "x-user-id: $TE_ID" -d "{\"action\":\"scan\",\"code\":\"$CLEAN_CODE\",\"mode\":\"CHECK_IN\"}")
[ "$S7" = "403" ] && ok "scanner: teacher (level 3+) blocked — admin ≤2 only" || bad "teacher scan → $S7"

# ═══════════ 3. ACADEMICS ═══════════
A=$(curl -s "$BASE/api/school/$SCHOOL/ops/academics" -H "x-user-id: $SU_ID")
CL=$(echo "$A" | jq "[c for c in d['classrooms'] if 'Basic 4' in c['name']][0]['id']")
SUBJ=$(echo "$A" | jq "[s for s in d['subjects'] if 'Math' in s['name']][0]['id']")
G=$(curl -s "$BASE/api/school/$SCHOOL/ops/academics?tab=rawgrades" -H "x-user-id: $SU_ID")
GN=$(echo "$G" | jq "len(d['grades'])")
GP=$(echo "$G" | jq "d['stats']['passMark']")
[ "$GN" = "9" ] && ok "WAEC: 9 grades seeded" || bad "WAEC grades ($GN)"
[ "$GP" = "50" ] && ok "WAEC: pass mark 50 (CI3 min-markFrom rule)" || bad "WAEC pass mark ($GP)"
RG=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/academics" -H 'content-type: application/json' -H "x-user-id: $SU_ID" -d '{"action":"rawgrade.create","name":"TEST","gradePoint":"T1","gradePointNumeric":1,"markFrom":99,"markUpto":100}')
RGID=$(echo "$RG" | jq "d['grade']['id']")
[ -n "$RGID" ] && ok "WAEC: grade create" || bad "rawgrade create: $RG"
RGD=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/academics" -H 'content-type: application/json' -H "x-user-id: $SU_ID" -d "{\"action\":\"rawgrade.delete\",\"id\":\"$RGID\"}")
echo "$RGD" | grep -q "ok" && ok "WAEC: grade delete" || bad "rawgrade delete: $RGD"
PG=$(curl -s "$BASE/api/school/$SCHOOL/ops/academics?tab=portfolio&classroomId=$CL&subjectId=$SUBJ&week=2026-W42" -H "x-user-id: $SU_ID")
PGR=$(echo "$PG" | jq "len(d['rows'])")
PGD=$(echo "$PG" | jq "len(d['days'])")
[ "${PGR:-0}" -ge 10 ] 2>/dev/null && [ "$PGD" = "5" ] && ok "portfolio: $PGR learners × $PGD days (W42)" || bad "portfolio grid ($PGR×$PGD)"
PA=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/academics" -H 'content-type: application/json' -H "x-user-id: $SU_ID" -d "{\"action\":\"portfolio.aggregate\",\"classroomId\":\"$CL\",\"subjectId\":\"$SUBJ\",\"week\":\"2026-W42\"}")
echo "$PA" | grep -q "Portfolio SBA 2026-W42" && ok "portfolio: aggregate → PROJECT assessment (20-mark class score)" || bad "aggregate: $PA"
STU=$(echo "$SJSON" | jq "[s for s in d['students'] if 'Basic 4' in s['classroom']][0]['id']")
CU=$(curl -s "$BASE/api/school/$SCHOOL/ops/academics?tab=cumulative&classroomId=$CL&studentId=$STU" -H "x-user-id: $SU_ID")
CUT=$(echo "$CU" | jq "len(d['terms'])")
CUA=$(echo "$CU" | jq "d['cumulative']['average'] is not None")
[ "${CUT:-0}" -ge 1 ] 2>/dev/null && [ "$CUA" = "True" ] && ok "cumulative: $CUT term(s) + cumulative average" || bad "cumulative: $CU"
PP=$(curl -s "$BASE/api/school/$SCHOOL/ops/academics?tab=papers" -H "x-user-id: $SU_ID")
PPN=$(echo "$PP" | jq "len(d['papers'])")
[ "${PPN:-0}" -ge 3 ] 2>/dev/null && ok "papers: $PPN in the bank" || bad "papers ($PPN)"
MS=$(curl -s "$BASE/api/school/$SCHOOL/ops/academics?tab=marks-sms&classroomId=$CL&receiver=guardian" -H "x-user-id: $SU_ID")
MSG=$(echo "$MS" | jq "d['previews'][0]['message']")
echo "$MSG" | grep -q "Exam Scores of" && ok "marks-SMS: CI3 message format" || bad "marks-SMS format: $MSG"

# ═══════════ 4. FINANCE ═══════════
# find a real receipt (walk back up to 10 days for a day with takings)
PAYID=""; R0=""
for dd in 0 1 2 3 4 5 6 7 8 9; do
  D=$(date -d "-$dd days" +%F)
  CSX=$(curl -s "$BASE/api/school/$SCHOOL/ops/reports?tab=cashier-summary&date=$D" -H "x-user-id: $SU_ID")
  R0=$(echo "$CSX" | jq "d['receipts'][0]['receiptNo'] if d.get('receipts') else ''")
  if [ -n "$R0" ] && [ "$R0" != "None" ]; then PAYID=$(echo "$CSX" | jq "d['receipts'][0]['id']"); break; fi
done
[ -n "$PAYID" ] && ok "finance: found real receipt $R0 for approvals test" || bad "no receipts found for approvals test"
REC=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/reports" -H 'content-type: application/json' -H "x-user-id: $CA_ID" -d "{\"action\":\"modreq.create\",\"targetType\":\"PAYMENT\",\"recordId\":\"$PAYID\",\"requestType\":\"EDIT\",\"reason\":\"parity2 test edit\",\"newData\":{\"method\":\"CHEQUE\"}}")
echo "$REC" | grep -q "PENDING" && ok "approvals: cashier creates request" || bad "modreq create: $REC"
REC2=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/reports" -H 'content-type: application/json' -H "x-user-id: $CA_ID" -d "{\"action\":\"modreq.create\",\"targetType\":\"PAYMENT\",\"recordId\":\"$PAYID\",\"requestType\":\"EDIT\",\"reason\":\"dup\",\"newData\":{\"method\":\"CHEQUE\"}}")
echo "$REC2" | grep -qE "409|pending" && ok "approvals: duplicate pending → 409" || bad "modreq dedupe: $REC2"
DEC403=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/api/school/$SCHOOL/ops/reports" -H 'content-type: application/json' -H "x-user-id: $CA_ID" -d "{\"action\":\"modreq.decide\",\"id\":\"x\",\"decision\":\"REJECTED\"}")
[ "$DEC403" = "403" ] && ok "approvals: cashier cannot decide (403 — level 1 only)" || bad "cashier decide → $DEC403"
RQID=$(echo "$REC" | jq "d['request']['id']")
DEC=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/reports" -H 'content-type: application/json' -H "x-user-id: $SU_ID" -d "{\"action\":\"modreq.decide\",\"id\":\"$RQID\",\"decision\":\"REJECTED\",\"note\":\"test reject\"}")
echo "$DEC" | grep -q "REJECTED" && ok "approvals: super rejects request" || bad "decide: $DEC"
PR=$(curl -s "$BASE/api/school/$SCHOOL/ops/payroll" -H "x-user-id: $SU_ID")
PERIOD=$(echo "$PR" | jq "d['runs'][0]['periodLabel']" 2>/dev/null)
[ "$PERIOD" = "None" ] && PERIOD=$(echo "$PR" | jq "d['runs'][0]['period']")
SS=$(curl -s "$BASE/api/school/$SCHOOL/ops/reports?tab=ssnit&period=$PERIOD" -H "x-user-id: $SU_ID")
SSN=$(echo "$SS" | jq "len(d['rows'])")
if [ "${SSN:-0}" -ge 1 ] 2>/dev/null; then
  BASE_SAL=$(echo "$SS" | jq "d['rows'][0]['basicSalaryCents']")
  T1E=$(echo "$SS" | jq "d['rows'][0]['tier1EmployerCents']")
  T2=$(echo "$SS" | jq "d['rows'][0]['tier2Cents']")
  M1=$(python3 -c "print(round($BASE_SAL*0.135))")
  M2=$(python3 -c "print(round($BASE_SAL*0.05))")
  [ "$T1E" = "$M1" ] && ok "SSNIT: tier-1 employer = 13.5% ($T1E)" || bad "tier1 math ($T1E vs $M1)"
  [ "$T2" = "$M2" ] && ok "SSNIT: tier-2 = 5% ($T2)" || bad "tier2 math ($T2 vs $M2)"
else
  bad "SSNIT rows for period '$PERIOD'"
fi
AG=$(curl -s "$BASE/api/school/$SCHOOL/ops/reports?tab=aging" -H "x-user-id: $SU_ID")
AGT=$(echo "$AG" | jq "len(d['rows'])")
AGSUM=$(echo "$AG" | python3 -c "import sys,json;d=json.load(sys.stdin);s=d['summary'];print(s['currentCents']+s['d31Cents']+s['d61Cents']+s['d90Cents'])")
AGROWS=$(echo "$AG" | python3 -c "import sys,json;d=json.load(sys.stdin);print(sum(r['balanceCents'] for r in d['rows']))")
[ "${AGT:-0}" -gt 0 ] 2>/dev/null && ok "aging: $AGT outstanding invoices bucketed" || bad "aging rows ($AGT)"
[ "$AGSUM" = "$AGROWS" ] && ok "aging: bucket sums == total balance" || bad "aging sums ($AGSUM vs $AGROWS)"
TODAY=$(date +%F)
CS=$(curl -s "$BASE/api/school/$SCHOOL/ops/reports?tab=cashier-summary&date=$TODAY" -H "x-user-id: $SU_ID")
echo "$CS" | grep -q "byMethod" && ok "cashier summary: methods + categories payload" || bad "cashier summary: $CS"
CA_NAME=$(echo "$CA" | jq "d['user']['fullName']")
SU_NAME=$(echo "$SU" | jq "d['user']['fullName']")
HO=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/reports" -H 'content-type: application/json' -H "x-user-id: $SU_ID" -d "{\"action\":\"handover.save\",\"date\":\"$TODAY\",\"cashierId\":\"$CA_ID\",\"cashierName\":\"$CA_NAME\",\"receiverId\":\"$SU_ID\",\"receiverName\":\"$SU_NAME\",\"totalCents\":0,\"cashCents\":0}")
echo "$HO" | grep -q '"handover"' && ok "handover: save round-trip" || bad "handover save: $HO"

# ═══════════ 5. LIBRARY REQUESTS ═══════════
LB=$(curl -s "$BASE/api/school/$SCHOOL/ops/library" -H "x-user-id: $LI_ID")
BK=$(echo "$LB" | jq "[b for b in d['books'] if b['available'] > 0][0]['id']")
AVL_BEFORE=$(echo "$LB" | jq "[b for b in d['books'] if b['id']=='$BK'][0]['available']")
BRQ=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/portal" -H 'content-type: application/json' -H "x-user-id: $ST_ID" -d "{\"action\":\"book-request.create\",\"bookId\":\"$BK\",\"studentId\":\"$STU_ID\",\"startAt\":\"$TODAY\",\"endAt\":\"$(date -d '+7 days' +%F)\"}")
echo "$BRQ" | grep -q "PENDING" && ok "library: student book request → PENDING" || bad "book request: $BRQ"
LBN=$(curl -s "$BASE/api/school/$SCHOOL/ops/library" -H "x-user-id: $LI_ID")
RQID2=$(echo "$LBN" | jq "[r for r in d['requests'] if r['status']=='PENDING' and r['bookId']=='$BK'][0]['id']")
ACC=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/library" -H 'content-type: application/json' -H "x-user-id: $LI_ID" -d "{\"action\":\"request.accept\",\"id\":\"$RQID2\"}")
echo "$ACC" | grep -q "ISSUED" && ok "library: accept → ISSUED + loan created" || bad "accept: $ACC"
LBA=$(curl -s "$BASE/api/school/$SCHOOL/ops/library" -H "x-user-id: $LI_ID")
AVL_AFTER=$(echo "$LBA" | jq "[b for b in d['books'] if b['id']=='$BK'][0]['available']")
[ "$AVL_AFTER" = "$((AVL_BEFORE - 1))" ] && ok "library: availability decremented ($AVL_BEFORE → $AVL_AFTER)" || bad "availability ($AVL_BEFORE → $AVL_AFTER)"
FORGE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/api/school/$SCHOOL/ops/portal" -H 'content-type: application/json' -H "x-user-id: $ST_ID" -d "{\"action\":\"book-request.create\",\"bookId\":\"$BK\",\"studentId\":\"$CLEAN\",\"startAt\":\"$TODAY\",\"endAt\":\"$(date -d '+7 days' +%F)\"}")
[ "$FORGE" = "403" ] && ok "library: forged studentId → 403" || bad "forged request → $FORGE"

# ═══════════ 6. DAILY-FEE AUDIT LOG ═══════════
DL=$(curl -s "$BASE/api/school/$SCHOOL/ops/daily" -H "x-user-id: $SU_ID")
AN=$(echo "$DL" | jq "len(d.get('audit',[]))")
[ "${AN:-0}" -gt 0 ] 2>/dev/null && ok "audit log: $AN rows with balances + actor" || bad "audit rows ($AN)"
ACT=$(echo "$DL" | jq "d['audit'][0]['actionType']")
[ -n "$ACT" ] && [ "$ACT" != "None" ] && ok "audit log: latest action = $ACT" || bad "audit actionType"

echo
echo "═══ RESULT: $PASS passed · $FAIL failed ═══"
[ "$FAIL" = "0" ]

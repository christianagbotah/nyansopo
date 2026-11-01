#!/bin/bash
# Feature test — students (promotion/mute/diet roster views) + online exams.
set -u
BASE=http://127.0.0.1:3000
PASS=0; FAIL=0
ok()  { PASS=$((PASS+1)); echo "  ✔ $1"; }
bad() { FAIL=$((FAIL+1)); echo "  ✘ $1"; }
jq()   { python3 -c "import sys,json;d=json.load(sys.stdin);print(eval(sys.argv[1]))" "$1" 2>/dev/null; }

for i in $(seq 1 30); do curl -s -o /dev/null --max-time 3 "$BASE" && break; sleep 2; done
curl -s -o /dev/null --max-time 5 "$BASE" || { echo "SERVER DOWN"; exit 1; }

ADMIN=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"super@nyansapo.gh","password":"nyansapo"}' | jq "d['user']['id']")
SCHOOL=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"super@nyansapo.gh","password":"nyansapo"}' | jq "d['user']['schoolId']")
STUDENT_USER=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"student@nyansapo.gh","password":"nyansapo"}' | jq "d['user']['id']")
STUDENT_ID=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"student@nyansapo.gh","password":"nyansapo"}' | jq "d['user']['studentId']")
TEACHER=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"teacher@nyansapo.gh","password":"nyansapo"}' | jq "d['user']['id']")
echo "═══ students+exams test · school=$SCHOOL ═══"

# ── 1. roster views (on roll / muted / alumni) ──
ACT=$(curl -s "$BASE/api/school/$SCHOOL/students?status=ACTIVE" -H "x-user-id: $ADMIN" | jq "len(d['students'])")
GRD=$(curl -s "$BASE/api/school/$SCHOOL/students?status=GRADUATED" -H "x-user-id: $ADMIN" | jq "len(d['students'])")
[ "${ACT:-0}" -gt 0 ] 2>/dev/null && ok "on-roll view returns $ACT learners" || bad "on-roll view empty"
FIELD=$(curl -s "$BASE/api/school/$SCHOOL/students" -H "x-user-id: $ADMIN" | jq "'dietNote' in d['students'][0] and 'promotedFrom' in d['students'][0]")
[ "$FIELD" = "True" ] && ok "rows expose dietNote + promotion trail" || bad "rows missing diet/promotion fields"

# pick a Basic-1 learner + a Basic-2 target class
SJSON=$(curl -s "$BASE/api/school/$SCHOOL/students" -H "x-user-id: $ADMIN")
PICK=$(echo "$SJSON" | jq "[s for s in d['students'] if 'Basic 1' in s['classroom']][0]['id']")
PICK_NAME=$(echo "$SJSON" | jq "[s for s in d['students'] if 'Basic 1' in s['classroom']][0]['name']")
TARGET=$(echo "$SJSON" | jq "[s['classroomId'] for s in d['students'] if 'Basic 2' in s['classroom']][0]")
[ -n "$PICK" ] && ok "picked $PICK_NAME (Basic 1) to promote" || bad "no Basic-1 learner"

# ── 2. diet round-trip ──
curl -s -X POST "$BASE/api/school/$SCHOOL/students" -H 'content-type: application/json' -H "x-user-id: $ADMIN" -d "{\"action\":\"set-diet\",\"studentId\":\"$PICK\",\"dietNote\":\"no pork (halal)\"}" > /dev/null
DIET=$(curl -s "$BASE/api/school/$SCHOOL/students" -H "x-user-id: $ADMIN" | jq "[s for s in d['students'] if s['id']=='$PICK'][0]['dietNote']")
[ "$DIET" = "no pork (halal)" ] && ok "diet note saved & returned" || bad "diet round-trip failed ($DIET)"

# ── 3. promotion round-trip (Basic 1 → Basic 2, then trail shows) ──
PROMO=$(curl -s -X POST "$BASE/api/school/$SCHOOL/students" -H 'content-type: application/json' -H "x-user-id: $ADMIN" -d "{\"action\":\"promote\",\"studentIds\":[\"$PICK\"],\"toClassroomId\":\"$TARGET\"}")
MOVED=$(echo "$PROMO" | jq "d['moved']")
[ "$MOVED" = "1" ] && ok "promotion moved 1 learner" || bad "promotion failed: $PROMO"
TRAIL=$(curl -s "$BASE/api/school/$SCHOOL/students" -H "x-user-id: $ADMIN" | jq "[s for s in d['students'] if s['id']=='$PICK'][0]['promotedFrom']")
CLS=$(curl -s "$BASE/api/school/$SCHOOL/students" -H "x-user-id: $ADMIN" | jq "[s for s in d['students'] if s['id']=='$PICK'][0]['classroom']")
[ "$TRAIL" = "Basic 1" ] && ok "promotion trail = 'Basic 1' (now in $CLS)" || bad "trail wrong: $TRAIL"

# ── 4. mute / unmute ──
curl -s -X POST "$BASE/api/school/$SCHOOL/students" -H 'content-type: application/json' -H "x-user-id: $ADMIN" -d "{\"action\":\"mute\",\"studentId\":\"$PICK\",\"reason\":\"travelling with mum\"}" > /dev/null
INMUTED=$(curl -s "$BASE/api/school/$SCHOOL/students?status=WITHDRAWN" -H "x-user-id: $ADMIN" | jq "len([s for s in d['students'] if s['id']=='$PICK'])")
[ "$INMUTED" = "1" ] && ok "learner muted (appears in Muted view)" || bad "mute failed"
curl -s -X POST "$BASE/api/school/$SCHOOL/students" -H 'content-type: application/json' -H "x-user-id: $ADMIN" -d "{\"action\":\"unmute\",\"studentId\":\"$PICK\"}" > /dev/null
BACK=$(curl -s "$BASE/api/school/$SCHOOL/students?status=ACTIVE" -H "x-user-id: $ADMIN" | jq "len([s for s in d['students'] if s['id']=='$PICK'])")
[ "$BACK" = "1" ] && ok "learner restored to roll" || bad "unmute failed"

# ── 5. graduate (JHS 3 → alumni) ──
GJSON=$(curl -s "$BASE/api/school/$SCHOOL/students" -H "x-user-id: $ADMIN")
GPICK=$(echo "$GJSON" | jq "[s for s in d['students'] if 'JHS 3' in s['classroom'] and s['id']!='$STUDENT_ID'][0]['id']")
curl -s -X POST "$BASE/api/school/$SCHOOL/students" -H 'content-type: application/json' -H "x-user-id: $ADMIN" -d "{\"action\":\"promote\",\"studentIds\":[\"$GPICK\"],\"graduateInstead\":true}" > /dev/null
INGRAD=$(curl -s "$BASE/api/school/$SCHOOL/students?status=GRADUATED" -H "x-user-id: $ADMIN" | jq "len([s for s in d['students'] if s['id']=='$GPICK'])")
[ "$INGRAD" = "1" ] && ok "JHS 3 learner graduated to alumni" || bad "graduation failed"

# ── 6. online exams — staff view ──
EJSON=$(curl -s "$BASE/api/school/$SCHOOL/ops/exams" -H "x-user-id: $TEACHER")
ECOUNT=$(echo "$EJSON" | jq "len(d['exams'])")
AVG=$(echo "$EJSON" | jq "[e for e in d['exams'] if e['status']=='PUBLISHED'][0]['avgScore']")
[ "${ECOUNT:-0}" -ge 3 ] 2>/dev/null && ok "exams list returns $ECOUNT (published avg=$AVG)" || bad "exams list wrong ($ECOUNT)"
QCOUNT=$(echo "$EJSON" | jq "[e for e in d['exams'] if e['status']=='PUBLISHED'][0]['questionCount']")
[ "$QCOUNT" = "4" ] && ok "published exam has 4 questions" || bad "question count $QCOUNT"

# ── 7. exam permissions — student sees list, submits, auto-marked ──
SCODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/api/school/$SCHOOL/ops/exams" -H "x-user-id: $STUDENT_USER")
[ "$SCODE" = "200" ] && ok "student can list exams (GET 200)" || bad "student GET exams: $SCODE"
# student is in JHS 3 (B9) — the published fractions exam targets that class
EXAMID=$(echo "$EJSON" | jq "[e for e in d['exams'] if e['status']=='PUBLISHED' and e['title'].startswith('Fractions')][0]['id']")
SUB=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/exams" -H 'content-type: application/json' -H "x-user-id: $STUDENT_USER" -d "{\"action\":\"submit-attempt\",\"examId\":\"$EXAMID\",\"studentId\":\"$STUDENT_ID\",\"answers\":{\"0\":\"A\",\"1\":\"C\",\"2\":\"B\",\"3\":\"A\"}}")
SCORE=$(echo "$SUB" | jq "d['score']")
MAX=$(echo "$SUB" | jq "d['max']")
[ "$SCORE" = "8" ] && [ "$MAX" = "8" ] && ok "auto-marked perfect 8/8 (all four correct keys)" || bad "auto-mark wrong: $SUB"
DUP=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/exams" -H 'content-type: application/json' -H "x-user-id: $STUDENT_USER" -d "{\"action\":\"submit-attempt\",\"examId\":\"$EXAMID\",\"studentId\":\"$STUDENT_ID\",\"answers\":{\"0\":\"A\"}}" | jq "d.get('error','')")
[ -n "$DUP" ] && ok "double submit rejected: $DUP" || bad "double submission allowed"

# forge: student attempting another learner's exam
FORGE=$(curl -s -X POST "$BASE/api/school/$SCHOOL/ops/exams" -H 'content-type: application/json' -H "x-user-id: $STUDENT_USER" -d "{\"action\":\"submit-attempt\",\"examId\":\"$EXAMID\",\"studentId\":\"$PICK\",\"answers\":{\"0\":\"A\"}}" | jq "d.get('error','')")
[ -n "$FORGE" ] && ok "forged attempt rejected: $FORGE" || bad "student could submit for someone else"

echo "═══ RESULT: $PASS passed · $FAIL failed ═══"
[ "$FAIL" = "0" ] || exit 1

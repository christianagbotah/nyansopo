#!/usr/bin/env bash
# ── wave 72 — Front-desk appointment booking & response end-to-end smoke ──
# Boots the dev server if it is down, then exercises the full booking
# workflow head-first: the desk books a visitor in to see a SELECTED staff
# member (instant SMS/email/in-app request alerts), the staff member
# accepts / declines / reschedules (incl. the +30-min quick offer), the
# desk moves a meeting, the odd-hours rule blocks teachers (evening,
# weekend, and staff self-reschedule) while administration staff stay
# bookable at any hour, the T-2-minute SMS reminder engine fires from the
# live desk poll, and the notification bell carries the request + the
# response feed (confidentiality: only the visited staff can respond).
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
DESK=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"staff@nyansapo.gh","password":"nyansapo"}' | jqpy "print(d['user']['id'])") || fail "desk login"
TEACHER=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"teacher@nyansapo.gh","password":"nyansapo"}' | jqpy "print(d['user']['id'])") || fail "teacher login"
HH="x-user-id: $HEAD"
DH="x-user-id: $DESK"
TH="x-user-id: $TEACHER"
echo "head=$HEAD desk=$DESK teacher=$TEACHER"

# staff ids from the board's staff picker (head can view)
BOARD=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/frontoffice?action=board")
GRACE=$(echo "$BOARD" | jqpy "print([s['id'] for s in d['staff'] if 'Grace' in s['fullName']][0])") || fail "find Grace"
ISAAC=$(echo "$BOARD" | jqpy "print([s['id'] for s in d['staff'] if 'Isaac' in s['fullName']][0])") || fail "find Isaac"
GRACE_CAT=$(echo "$BOARD" | jqpy "print([s['category'] for s in d['staff'] if 'Grace' in s['fullName']][0])")
ISAAC_CAT=$(echo "$BOARD" | jqpy "print([s['category'] for s in d['staff'] if 'Isaac' in s['fullName']][0])")
echo "Grace=$GRACE ($GRACE_CAT)  Isaac=$ISAAC ($ISAAC_CAT)"
[ "$GRACE_CAT" = "ADMINISTRATION" ] || fail "Grace should be ADMINISTRATION"
[ "$ISAAC_CAT" = "TEACHING" ] || fail "Isaac should be TEACHING"

# a safe future school-hours slot: next Monday 10:00 local (Ghana = UTC)
NEXTMON=$(python3 -c "
from datetime import date, timedelta, datetime
d = date.today() + timedelta(days=1)
while d.weekday() != 0: d += timedelta(days=1)
print(datetime(d.year, d.month, d.day, 10, 0).astimezone().strftime('%Y-%m-%dT%H:%M:00.000Z') if False else f'{d.isoformat()}T10:00:00.000Z')")
echo "next Monday: $NEXTMON"

# ── 1 — the desk books a visitor in to see Grace (admin) at a school-hours slot → alerts fire
BOOK=$(curl -s -H "$DH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-create\",\"title\":\"Smoke: discuss JHS 3 fees balance\",\"withName\":\"Nana Yaa Osei\",\"withRole\":\"PARENT\",\"visitorPhone\":\"024 999 8877\",\"visitorOrg\":\"PTA\",\"staffId\":\"$GRACE\",\"scheduledAt\":\"$NEXTMON\",\"durationMin\":30,\"location\":\"Head's office\",\"purpose\":\"Fees balance review\"}")
AID=$(echo "$BOOK" | jqpy "print(d['appointment']['id'])")
SMS=$(echo "$BOOK" | jqpy "print((d.get('alert') or {}).get('sms'))")
EMAIL=$(echo "$BOOK" | jqpy "print((d.get('alert') or {}).get('email'))")
STAFFN=$(echo "$BOOK" | jqpy "print(d['appointment']['staffName'])")
echo "1. booked $AID — staff: $STAFFN · request SMS: $SMS · email: $EMAIL"
[ "$STAFFN" = "Madam Grace Mensah" ] || fail "appointment should link Grace ($STAFFN)"
[ "$SMS" = "SENT" ] || fail "request SMS should be SENT (Grace has a phone)"

# ── 2 — the request reaches the head's self-service board (action=my)
MY=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/frontoffice?action=my")
FOUND=$(echo "$MY" | jqpy "print(any(a['id']=='$AID' for a in d['requests']))")
echo "2. head's my-board has the request: $FOUND"
[ "$FOUND" = "True" ] || fail "request missing from head's action=my"

# ── 3 — the bell shows the request to the head (dashboard deep-link)
BELLH=$(curl -s -H "$HH" "$BASE/api/notifications?schoolId=$SID")
BELLREQ=$(echo "$BELLH" | jqpy "print(any(i['id']=='appt-requests' for i in d['items']))")
echo "3. head bell appt-requests: $BELLREQ"
[ "$BELLREQ" = "True" ] || fail "bell missing appt-requests for head"

# ── 4 — a teacher CANNOT respond to Grace's request (only the visited staff)
CODE=$(curl -s -o /dev/null -w "%{http_code}" -H "$TH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-respond\",\"appointmentId\":\"$AID\",\"decision\":\"ACCEPT\"}")
echo "4. teacher blocked from Grace's appointment: $CODE"
[ "$CODE" = "403" ] || fail "teacher should get 403 responding to Grace's appointment (got $CODE)"

# ── 5 — the head DECLINES with a note → status + trail + desk bell feed
DEC=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-respond\",\"appointmentId\":\"$AID\",\"decision\":\"DECLINE\",\"note\":\"At a GES workshop that morning\"}")
DST=$(echo "$DEC" | jqpy "print(d['appointment']['status'])")
DBY=$(echo "$DEC" | jqpy "print(d['appointment']['respondedByName'])")
echo "5. head declined: $DST by $DBY"
[ "$DST" = "DECLINED" ] || fail "decline should set DECLINED (got $DST)"

# desk bell shows the response feed (respondedByUserId excludes the responder)
BELLD=$(curl -s -H "$DH" "$BASE/api/notifications?schoolId=$SID")
FEED=$(echo "$BELLD" | jqpy "print(any(i['id']=='appt-updates' for i in d['items']))")
echo "   desk bell appt-updates: $FEED"
[ "$FEED" = "True" ] || fail "desk bell missing appt-updates"

# ── 6 — odd-hours rule: teacher at 20:00 → 400
EVE=$(curl -s -H "$DH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-create\",\"title\":\"Smoke: teacher evening\",\"withName\":\"Kwesi Night\",\"staffId\":\"$ISAAC\",\"scheduledAt\":\"${NEXTMON%T*}T20:00:00.000Z\"}")
CODE=$(echo "$EVE" | jqpy "print('Odd hours' in d.get('error',''))and print(0)" 2>/dev/null || echo "$EVE" | jqpy "print('Odd hours' in d.get('error',''))")
echo "6. teacher at 20:00 rejected: $CODE"
[ "$CODE" = "True" ] || fail "teacher evening booking should fail with odd-hours error"

# ── 7 — weekend rule: teacher on Saturday 10:00 → 400
SAT=$(python3 -c "
from datetime import date, timedelta, datetime
d = date.today() + timedelta(days=1)
while d.weekday() != 5: d += timedelta(days=1)
print(f'{d.isoformat()}T10:00:00.000Z')")
WKND=$(curl -s -H "$DH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-create\",\"title\":\"Smoke: teacher weekend\",\"withName\":\"Kwesi Weekend\",\"staffId\":\"$ISAAC\",\"scheduledAt\":\"$SAT\"}")
CODE=$(echo "$WKND" | jqpy "print('Odd hours' in d.get('error',''))")
echo "7. teacher on Saturday rejected: $CODE"
[ "$CODE" = "True" ] || fail "teacher weekend booking should fail with odd-hours error"

# ── 8 — administration at the SAME odd hour → allowed (the head's door stays open)
ADMIN=$(curl -s -H "$DH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-create\",\"title\":\"Smoke: head evening OK\",\"withName\":\"Kwesi Night\",\"staffId\":\"$GRACE\",\"scheduledAt\":\"${NEXTMON%T*}T20:30:00.000Z\",\"location\":\"Head's office\"}")
A2=$(echo "$ADMIN" | jqpy "print(d['appointment']['id'])")
echo "8. administration booked at 20:30: $A2"
[ "$A2" != "None" ] || fail "administration staff must be bookable at odd hours"

# ── 9 — teacher within school hours → allowed
OKT=$(curl -s -H "$DH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-create\",\"title\":\"Smoke: teacher school hours\",\"withName\":\"Ama Parent\",\"staffId\":\"$ISAAC\",\"scheduledAt\":\"${NEXTMON%T*}T11:00:00.000Z\"}")
A3=$(echo "$OKT" | jqpy "print(d['appointment']['id'])")
echo "9. teacher booked Monday 11:00: $A3"
[ "$A3" != "None" ] || fail "teacher booking within school hours should succeed"

# ── 9b — class-hours rule: the timetable is the truth — Isaac teaches
#      Mathematics Monday 08:00–08:40, so an 08:15 booking is refused
CLASH=$(curl -s -H "$DH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-create\",\"title\":\"Smoke: teacher mid-lesson\",\"withName\":\"Ama Parent\",\"staffId\":\"$ISAAC\",\"scheduledAt\":\"${NEXTMON%T*}T08:15:00.000Z\"}")
CLASHY=$(echo "$CLASH" | jqpy "print('Class clash' in d.get('error',''))")
echo "9b. teacher mid-lesson (Mon 08:15) rejected: $CLASHY"
[ "$CLASHY" = "True" ] || fail "booking a teacher inside their lesson should fail with a class clash"

# ── 9c — wave 73: the free/break windows engine — teachers CAN be seen in
#      break hours: the gaps between their lessons surface as one-tap windows
FREE=$(curl -s -H "$DH" "$BASE/api/school/$SID/ops/frontoffice?action=staff-free&staffId=$ISAAC&date=${NEXTMON%T*}")
NW=$(echo "$FREE" | jqpy "print(len(d['windows']))")
GAP=$(echo "$FREE" | jqpy "print(any(w['start']=='08:40' and w['end']=='09:20' for w in d['windows']))")
echo "9c. Isaac's free windows on ${NEXTMON%T*}: $NW (the 08:40–09:20 timetable gap surfaced: $GAP)"
[ "$NW" -ge 1 ] || fail "staff-free should return at least one free window for a teaching day"
[ "$GAP" = "True" ] || fail "the 08:40–09:20 timetable gap should surface as a free window (break hours)"

# the desk books the visit INTO the first free window — a break-hours visit lands
BRKT=$(echo "$FREE" | jqpy "print(d['windows'][0]['start'])")
BRK=$(curl -s -H "$DH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-create\",\"title\":\"Smoke: break-hours visit\",\"withName\":\"Ama Parent\",\"staffId\":\"$ISAAC\",\"scheduledAt\":\"${NEXTMON%T*}T${BRKT}:00.000Z\"}")
BID=$(echo "$BRK" | jqpy "print(d['appointment']['id'])")
echo "    booked into the first free window (${BRKT}) — break-hours visit: $BID"
[ "$BID" != "None" ] || fail "a teacher booking inside their free period (break hours) should succeed"

# the class-clash refusal now points at the next open door (wave 73 hint)
HINT=$(echo "$CLASH" | jqpy "print('next free' in d.get('error',''))")
echo "    class-clash refusal carries the next-free hint: $HINT"
[ "$HINT" = "True" ] || fail "the class clash error should suggest the staff member's next free slot"

# administration reports anytime — the head's door never closes
FREEA=$(curl -s -H "$DH" "$BASE/api/school/$SID/ops/frontoffice?action=staff-free&staffId=$GRACE&date=${NEXTMON%T*}")
ANY=$(echo "$FREEA" | jqpy "print(d['anytime'])")
echo "    Grace (administration) anytime flag: $ANY"
[ "$ANY" = "True" ] || fail "administration staff should report anytime free windows"

# ── 10 — the head ACCEPTS the evening appointment → CONFIRMED
ACC=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-respond\",\"appointmentId\":\"$A2\",\"decision\":\"ACCEPT\"}")
AST=$(echo "$ACC" | jqpy "print(d['appointment']['status'])")
echo "10. head accepted evening: $AST"
[ "$AST" = "CONFIRMED" ] || fail "accept should set CONFIRMED (got $AST)"

# ── 11 — the head RESCHEDULES (+30 min quick offer) → RESCHEDULED + trail
RS=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-respond\",\"appointmentId\":\"$A2\",\"decision\":\"RESCHEDULE\",\"scheduledAt\":\"$(python3 -c "import datetime; print((datetime.datetime.utcnow()+datetime.timedelta(minutes=35)).strftime('%Y-%m-%dT%H:%M:00.000Z'))")\"}")
RST=$(echo "$RS" | jqpy "print(d['appointment']['status'])")
RFROM=$(echo "$RS" | jqpy "print(d['appointment']['rescheduledFrom'] is not None)")
echo "11. head rescheduled: $RST (trail: $RFROM)"
[ "$RST" = "RESCHEDULED" ] || fail "reschedule should set RESCHEDULED (got $RST)"
[ "$RFROM" = "True" ] || fail "reschedule should keep the old slot in rescheduledFrom"

# ── 12 — a teacher's own reschedule to odd hours is still blocked
TR=$(curl -s -H "$TH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-respond\",\"appointmentId\":\"$A3\",\"decision\":\"RESCHEDULE\",\"scheduledAt\":\"${NEXTMON%T*}T21:00:00.000Z\"}")
CODE=$(echo "$TR" | jqpy "print('Odd hours' in d.get('error',''))")
echo "12. teacher self-reschedule to 21:00 rejected: $CODE"
[ "$CODE" = "True" ] || fail "teacher self-reschedule to odd hours should fail"

# ── 13 — the desk moving a teacher's appointment to odd hours is blocked
DM=$(curl -s -H "$DH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-reschedule\",\"appointmentId\":\"$A3\",\"scheduledAt\":\"${NEXTMON%T*}T19:00:00.000Z\"}")
CODE=$(echo "$DM" | jqpy "print('Odd hours' in d.get('error',''))")
echo "13. desk move of teacher to 19:00 rejected: $CODE"
[ "$CODE" = "True" ] || fail "desk moving a teacher to odd hours should fail"

# ── 14 — the desk CAN move a teacher's appointment within school hours
DM2=$(curl -s -H "$DH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-reschedule\",\"appointmentId\":\"$A3\",\"scheduledAt\":\"${NEXTMON%T*}T14:00:00.000Z\"}")
MST=$(echo "$DM2" | jqpy "print(d['appointment']['status'])")
echo "14. desk moved teacher to Monday 14:00: $MST"
[ "$MST" = "SCHEDULED" ] || fail "desk move within school hours should keep the row movable (got $MST)"

# ── 15 — T-2-minute reminder: book Grace ~100s out; the live desk poll fires her SMS
SOON=$(python3 -c "import datetime; print((datetime.datetime.utcnow()+datetime.timedelta(seconds=100)).strftime('%Y-%m-%dT%H:%M:00.000Z'))")
QUICK=$(curl -s -H "$DH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-create\",\"title\":\"Smoke: in two minutes\",\"withName\":\"Akosua Rush\",\"staffId\":\"$GRACE\",\"scheduledAt\":\"$SOON\",\"location\":\"Head's office\"}")
A4=$(echo "$QUICK" | jqpy "print(d['appointment']['id'])")
LIVE=$(curl -s -H "$DH" "$BASE/api/school/$SID/ops/frontoffice?action=live")
REMINDED=$(echo "$LIVE" | jqpy "print(any(x['id']=='$A4' for x in d.get('reminded',[])))")
echo "15. live poll fired the T-2-min reminder for $A4: $REMINDED"
[ "$REMINDED" = "True" ] || fail "live poll should send the T-2-min SMS reminder"

# reminder dedupes (reminderSentAt set — a second poll never re-sends)
LIVE2=$(curl -s -H "$DH" "$BASE/api/school/$SID/ops/frontoffice?action=live")
REMINDED2=$(echo "$LIVE2" | jqpy "print(any(x['id']=='$A4' for x in d.get('reminded',[])))")
echo "    second poll does not re-send: $REMINDED2 (correct when False)"
[ "$REMINDED2" = "False" ] || fail "T-2-min reminder should dedupe on the second poll"

# the staff member's own poll (action=my) also sees it starting
MYH=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/frontoffice?action=my")
HASSOON=$(echo "$MYH" | jqpy "print(any(a['id']=='$A4' for a in d['upcoming']) or any(a['id']=='$A4' for a in d['requests']))")
echo "    head's my-board shows the imminent meeting: $HASSOON"
[ "$HASSOON" = "True" ] || fail "imminent meeting missing from head's action=my"

# ── 16 — desk-only action guard: respond without being the target/desk is 403
PARENT=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"parent@nyansapo.gh","password":"nyansapo"}' | jqpy "print(d['user']['id'])")
CODE=$(curl -s -o /dev/null -w "%{http_code}" -H "x-user-id: $PARENT" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-respond\",\"appointmentId\":\"$A4\",\"decision\":\"ACCEPT\"}")
echo "16. parent cannot respond: $CODE"
[ "$CODE" = "403" ] || fail "parent should get 403 responding (got $CODE)"

# ── 16b — the public one-tap answer link: create a fresh request, then answer via link
PUB=$(curl -s -H "$DH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-create\",\"title\":\"Smoke: link answer\",\"withName\":\"Link Tester\",\"staffId\":\"$GRACE\",\"scheduledAt\":\"$NEXTMON\",\"location\":\"Head's office\"}")
A5=$(echo "$PUB" | jqpy "print(d['appointment']['id'])")
A5CODE=$(echo "$PUB" | jqpy "print(d['appointment']['code'])")
URL=$(echo "$PUB" | jqpy "print(d['alert']['decisionUrl'])")
TOK=$(python3 -c "from urllib.parse import urlparse,parse_qs; print(parse_qs(urlparse('$URL').query)['t'][0])")
echo "16b. created $A5CODE ($A5) — link: ${URL:0:60}…"
[ "$A5CODE" != "None" ] || fail "staff-linked booking should carry an APT code"
[ -n "$URL" ] || fail "create should return the decisionUrl"

# a wrong token is refused
CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/api/public/appointments/$A5CODE?t=deadbeef")
echo "     wrong token refused: $CODE"
[ "$CODE" = "404" ] || fail "wrong token should 404 (got $CODE)"

# the right token sees the request (minimal fields, canRespond)
VIEW=$(curl -s "$BASE/api/public/appointments/$A5CODE?t=$TOK")
CANR=$(echo "$VIEW" | jqpy "print(d['canRespond'])")
HOST=$(echo "$VIEW" | jqpy "print(d['host']['name'])")
echo "     link view: canRespond=$CANR host=$HOST"
[ "$CANR" = "True" ] || fail "link view should allow responding"
[ "$HOST" = "Madam Grace Mensah" ] || fail "link view should show the host (got $HOST)"

# the staff member answers ACCEPT from the public page (token IS the auth)
ACC=$(curl -s -X POST "$BASE/api/public/appointments/$A5CODE" -H 'content-type: application/json' -d "{\"t\":\"$TOK\",\"decision\":\"ACCEPT\",\"note\":\"From the link on my phone\"}")
PUBST=$(echo "$ACC" | jqpy "print(d['status'])")
echo "     answered via link: $PUBST"
[ "$PUBST" = "CONFIRMED" ] || fail "public link ACCEPT should confirm (got $PUBST)"

# a confirmed meeting may still be answered (change of mind) — RESCHEDULE to +2h
CHG=$(curl -s -X POST "$BASE/api/public/appointments/$A5CODE" -H 'content-type: application/json' -d "{\"t\":\"$TOK\",\"decision\":\"RESCHEDULE\",\"scheduledAt\":\"$(python3 -c "import datetime; print((datetime.datetime.now(datetime.UTC)+datetime.timedelta(hours=2)).strftime('%Y-%m-%dT%H:%M:00.000Z'))")\"}")
CHGST=$(echo "$CHG" | jqpy "print(d['status'])")
echo "     changed mind via link to reschedule: $CHGST"
[ "$CHGST" = "RESCHEDULED" ] || fail "staff must be able to reschedule a confirmed answer (got $CHGST)"

# then DECLINE the rescheduled meeting (final answer) → answered out
CHG2=$(curl -s -X POST "$BASE/api/public/appointments/$A5CODE" -H 'content-type: application/json' -d "{\"t\":\"$TOK\",\"decision\":\"DECLINE\"}")
CHGST2=$(echo "$CHG2" | jqpy "print(d['status'])")
echo "     final decline via link: $CHGST2"
[ "$CHGST2" = "DECLINED" ] || fail "staff must be able to decline after rescheduling (got $CHGST2)"

# answering a DECLINED (answered-out) request is refused
CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/api/public/appointments/$A5CODE" -H 'content-type: application/json' -d "{\"t\":\"$TOK\",\"decision\":\"ACCEPT\"}")
echo "     third answer refused: $CODE"
[ "$CODE" = "400" ] || fail "answering a declined request should 400 (got $CODE)"

# ── 16c — the desk relays the answer to the visitor (closes the loop)
REL=$(curl -s -H "$DH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-relay\",\"appointmentId\":\"$A5\"}")
RELST=$(echo "$REL" | jqpy "print(d['appointment']['status'])")
RELAT=$(echo "$REL" | jqpy "print(d['appointment']['relayedAt'] is not None)")
echo "16c. desk relayed: status=$RELST relayed=$RELAT"
[ "$RELAT" = "True" ] || fail "relay should set relayedAt"

# relay on a RESCHEDULED appointment confirms the new time with the visitor
A6=$(curl -s -H "$DH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-create\",\"title\":\"Smoke: relay-confirm\",\"withName\":\"Relay Tester\",\"staffId\":\"$GRACE\",\"scheduledAt\":\"$NEXTMON\",\"location\":\"Head's office\"}" | jqpy "print(d['appointment']['id'])")
RS2=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-respond\",\"appointmentId\":\"$A6\",\"decision\":\"RESCHEDULE\",\"scheduledAt\":\"$(python3 -c "import datetime; print((datetime.datetime.now(datetime.UTC)+datetime.timedelta(minutes=95)).strftime('%Y-%m-%dT%H:%M:00.000Z'))")\",\"note\":\"Push it after lunch\"}")
R2ST=$(echo "$RS2" | jqpy "print(d['appointment']['status'])")
echo "     rescheduled a fresh booking: $R2ST"
[ "$R2ST" = "RESCHEDULED" ] || fail "fresh reschedule should work (got $R2ST)"
REL2=$(curl -s -H "$DH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-relay\",\"appointmentId\":\"$A6\"}")
REL2ST=$(echo "$REL2" | jqpy "print(d['appointment']['status'])")
echo "     relayed reschedule confirms the new time: $REL2ST"
[ "$REL2ST" = "CONFIRMED" ] || fail "relaying a reschedule should confirm the new time (got $REL2ST)"

# ── 17 — clean the transient rows (keep the book tidy — the week-ahead ones stay as demo data)
for id in $AID $A2 $A3 $BID $A4 $A5 $A6; do
  curl -s -o /dev/null -H "$DH" -X POST "$BASE/api/school/$SID/ops/frontoffice" -H 'content-type: application/json' -d "{\"action\":\"appointment-status\",\"appointmentId\":\"$id\",\"status\":\"CANCELLED\"}"
done
echo "17. smoke rows cancelled"

echo
echo "ALL WAVE-72 APPOINTMENT SMOKE CHECKS PASSED"

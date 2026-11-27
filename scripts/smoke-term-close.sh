#!/usr/bin/env bash
# ── wave 76 — Term closing & rollover end-to-end smoke ──
# The end-of-term walk as one API: the readiness board (results governance,
# billing coverage, library returns, registers, the calendar's next term),
# the close gate (results locked, or force), reopen, and the rollover that
# flips the live session inside one transaction. Everything runs against the
# seeded current term and is RESTORED afterwards (reopen + set-current-term
# + delete-year), so the demo school wakes up exactly as it started.
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
HH="x-user-id: $HEAD"; TH="x-user-id: $TEACHER"
echo "head=$HEAD teacher=$TEACHER"

# ── 1 — readiness board for the seeded current term
G=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/term-close")
CUR=$(echo "$G" | jqpy "print(d['term']['id'], d['term']['name'], d['term']['yearName'], d['term']['closedAt'] is None)")
BOARD=$(echo "$G" | jqpy "print(d['results']['total'], d['results']['locked'], d['results']['approved'], d['calendar']['name'] if d['calendar'] else None, d['billing']['coverage'] >= 0, d['library']['loansOut'] >= 0)")
CURID=$(echo "$CUR" | cut -d' ' -f1)
echo "1. board: $CUR | $BOARD (total, locked, approved, next, billing, library)"
[ "$(echo "$CUR" | cut -d' ' -f2,3,4,5)" = "First Term 2026/2027 True" ] || fail "unexpected current term ($CUR)"
echo "$G" | jqpy "exit(0 if d['results']['allLocked'] is False and d['calendar'] and d['calendar']['name'] == 'Second Term' else 1)" || fail "board wrong ($BOARD)"

# ── 2 — RBAC: teacher may READ the board (reports.view) but never close (settings.manage)
TCODE=$(curl -s -o /dev/null -w "%{http_code}" -H "$TH" "$BASE/api/school/$SID/ops/term-close")
CCODE=$(curl -s -o /dev/null -w "%{http_code}" -H "$TH" -X POST "$BASE/api/school/$SID/ops/term-close" -H 'content-type: application/json' -d '{"action":"close"}')
echo "2. teacher GET=$TCODE POST close=$CCODE"
[ "$TCODE" = "200" ] || fail "teacher should read the board ($TCODE)"
[ "$CCODE" = "403" ] || fail "teacher must not close terms ($CCODE)"

# ── 3 — the close gate: unlocked results refuse the close
C1=$(curl -s -w "\n%{http_code}" -H "$HH" -X POST "$BASE/api/school/$SID/ops/term-close" -H 'content-type: application/json' -d '{"action":"close"}')
C1CODE=$(echo "$C1" | tail -1); C1ERR=$(echo "$C1" | head -1 | jqpy "print(d['error'])")
echo "3. close → $C1CODE: $C1ERR"
[ "$C1CODE" = "409" ] || fail "close should be refused while results are unlocked ($C1CODE)"
case "$C1ERR" in *"not locked"*) ;; *) fail "refusal should name the unlocked results ($C1ERR)" ;; esac

# ── 4 — force the close: the stamp lands
C2=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/term-close" -H 'content-type: application/json' -d '{"action":"close","force":true}')
C2M=$(echo "$C2" | jqpy "print(d.get('message', d.get('error','')))")
echo "4. close force → $C2M"
echo "$C2" | jqpy "exit(0 if d.get('closedAt') else 1)" || fail "forced close should stamp closedAt ($C2)"
G2=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/term-close")
echo "$G2" | jqpy "exit(0 if d['term']['closedAt'] else 1)" || fail "GET should show the closed stamp"

# ── 5 — closing twice is refused
C3=$(curl -s -o /dev/null -w "%{http_code}" -H "$HH" -X POST "$BASE/api/school/$SID/ops/term-close" -H 'content-type: application/json' -d '{"action":"close"}')
echo "5. close again → $C3"
[ "$C3" = "409" ] || fail "double close should refuse ($C3)"

# ── 6 — reopen: the stamp clears (a closed term may reopen)
C4=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/term-close" -H 'content-type: application/json' -d '{"action":"reopen"}')
C4M=$(echo "$C4" | jqpy "print(d.get('message', d.get('error','')))")
echo "6. reopen → $C4M"
echo "$C4" | jqpy "exit(0 if d.get('ok') else 1)" || fail "reopen should work ($C4)"
G3=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/term-close")
echo "$G3" | jqpy "exit(0 if d['term']['closedAt'] is None else 1)" || fail "reopen should clear the stamp"

# ── 7 — rollover while open is refused (force escapes, but not silently)
C5=$(curl -s -o /dev/null -w "%{http_code}" -H "$HH" -X POST "$BASE/api/school/$SID/ops/term-close" -H 'content-type: application/json' -d '{"action":"rollover"}')
echo "7. rollover while open → $C5"
[ "$C5" = "409" ] || fail "rollover should refuse while the term is open ($C5)"

# ── 8 — forced rollover: the live session flips to Second Term (same year)
C6=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/term-close" -H 'content-type: application/json' -d '{"action":"rollover","force":true}')
C6M=$(echo "$C6" | jqpy "print(d.get('message', d.get('error','')))")
echo "8. rollover → $C6M"
echo "$C6" | jqpy "exit(0 if d.get('current', {}).get('name', '').startswith('Second Term') else 1)" || fail "rollover should land on Second Term ($C6)"
G4=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/term-close")
NOWT=$(echo "$G4" | jqpy "print(d['term']['name'], d['calendar']['name'] if d['calendar'] else None)")
echo "8b. board now: $NOWT"
[ "$NOWT" = "Second Term Third Term" ] || fail "board should show Second Term live with Third Term next ($NOWT)"

# ── 9 — restore: the seeded First Term returns as the live session
R1=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/sessions" -H 'content-type: application/json' -d "{\"action\":\"set-current-term\",\"termId\":\"$CURID\"}")
echo "9. restored → $(echo "$R1" | jqpy "print(d.get('message', d.get('error','')))")"
G5=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/term-close")
echo "$G5" | jqpy "exit(0 if d['term']['name'] == 'First Term' and d['term']['isCurrent'] and d['term']['closedAt'] is None else 1)" || fail "restore failed"

# ── 10 — the no-next-term guard: a year past everything has no successor
Y=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/sessions" -H 'content-type: application/json' -d '{"action":"create-year","name":"SMOKE 2099/2100","startsAt":"2099-01-01","endsAt":"2100-12-31","autoTerms":true}')
YID=$(echo "$Y" | jqpy "print(d['yearId'])")
[ -n "$YID" ] || fail "throwaway year not created ($Y)"
YS=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/sessions")
T3=$(echo "$YS" | jqpy "
y = [x for x in d['years'] if x['id'] == '$YID'][0]
t = [x for x in y['terms'] if x['sequence'] == 3][0]
print(t['id'])")
curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/sessions" -H 'content-type: application/json' -d "{\"action\":\"set-current-term\",\"termId\":\"$T3\"}" > /dev/null
G6=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/term-close")
echo "10a. throwaway Third Term live: $(echo "$G6" | jqpy "print(d['term']['name'], d['calendar'])")"
C7=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/term-close" -H 'content-type: application/json' -d '{"action":"rollover","force":true}')
C7CODE=$(echo "$C7" | jqpy "print(d.get('error','OK')[:60])")
echo "10b. rollover with no successor → $C7CODE"
echo "$C7" | jqpy "exit(0 if 'No next term' in d.get('error','') else 1)" || fail "no-calendar rollover should refuse ($C7)"

# ── 11 — restore the seeded session and remove the throwaway year
curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/sessions" -H 'content-type: application/json' -d "{\"action\":\"set-current-term\",\"termId\":\"$CURID\"}" > /dev/null
D1=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/sessions" -H 'content-type: application/json' -d "{\"action\":\"delete-year\",\"yearId\":\"$YID\"}")
echo "11. throwaway removed → $(echo "$D1" | jqpy "print(d.get('message', d.get('error','')))")"
G7=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/term-close")
FINAL=$(echo "$G7" | jqpy "print(d['term']['name'], d['term']['closedAt'] is None, d['calendar']['name'] if d['calendar'] else None)")
echo "11b. final state: $FINAL"
[ "$FINAL" = "First Term True Second Term" ] || fail "final state wrong ($FINAL)"

echo "=== ALL WAVE-76 TERM-CLOSE SMOKE CHECKS PASSED ==="

#!/bin/bash
# Full RBAC/portal verification — starts dev server, then exercises every portal.
set -u
cd /home/z/my-project
PGURL="postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public"

# ── 1. start server if needed ──
if ! curl -s -o /dev/null --max-time 3 http://127.0.0.1:3000 2>/dev/null; then
  setsid nohup env DATABASE_URL="$PGURL" bun run dev > /dev/null 2>&1 < /dev/null &
  for i in $(seq 1 60); do
    curl -s -o /dev/null --max-time 3 http://127.0.0.1:3000 2>/dev/null && break
    sleep 1
  done
fi
curl -s -o /dev/null --max-time 3 http://127.0.0.1:3000 && echo "SERVER UP" || { echo "SERVER FAILED"; exit 1; }

mkdir -p /tmp/rbac-verify
AB="agent-browser"

login_as() {  # email -> sets localStorage session from real auth API, then opens app
  local email="$1" mod="${2:-dashboard}"
  local resp
  resp=$(curl -s -X POST http://127.0.0.1:3000/api/auth -H 'content-type: application/json' -d "{\"email\":\"$email\",\"password\":\"nyansapo\"}")
  local ok
  ok=$(echo "$resp" | python3 -c "import sys,json; d=json.load(sys.stdin); print('OK' if 'user' in d else 'FAIL '+json.dumps(d))" 2>/dev/null || echo "FAIL parse")
  if [[ "$ok" != OK* ]]; then echo "  !! AUTH $email → $ok"; echo "$resp" | head -c 200; echo; return 1; fi
  local landing
  landing=$(echo "$resp" | python3 -c "import sys,json; print(json.load(sys.stdin)['user'].get('landingModule') or 'dashboard')")
  local store
  store=$(echo "$resp" | python3 -c "
import sys, json
u = json.load(sys.stdin)['user']
print(json.dumps({'state': {'session': u, 'currentSchoolId': u.get('schoolId'), 'module': u.get('landingModule') or 'dashboard', 'queue': [], 'lastSyncAt': None}, 'version': 0}))")
  $AB open http://localhost:3000/ > /dev/null 2>&1
  $AB eval "localStorage.setItem('nyansapo-store', $(python3 -c "import sys; print(json.dumps(sys.argv[1]))" "$store")); 'set'" > /dev/null 2>&1
  $AB open "http://localhost:3000/" > /dev/null 2>&1
  sleep 3.5
  echo "  auth=$ok landing=$landing"
}

navdump() {
  $AB eval "Array.from(document.querySelectorAll('aside nav button, header button')).map(b => b.textContent.trim()).filter(t => t && t.length < 30).join(' | ')" 2>/dev/null
}

pagetext() {
  $AB eval "document.querySelector('main') ? document.querySelector('main').innerText.slice(0, 600) : document.body.innerText.slice(0, 400)" 2>/dev/null
}

for ROLE in super head cashier conductor librarian staff parent student teacher bursar; do
  echo "════════ $ROLE ════════"
  login_as "$ROLE@nyansapo.gh"
  echo "  NAV: $(navdump)"
  $AB screenshot "/tmp/rbac-verify/$ROLE.png" > /dev/null 2>&1
  sleep 1
done

echo "════════ API permission enforcement ════════"
SUPER_ID=$(curl -s -X POST http://127.0.0.1:3000/api/auth -H 'content-type: application/json' -d '{"email":"super@nyansapo.gh","password":"nyansapo"}' | python3 -c "import sys,json; print(json.load(sys.stdin)['user']['id'])")
CASHIER_ID=$(curl -s -X POST http://127.0.0.1:3000/api/auth -H 'content-type: application/json' -d '{"email":"cashier@nyansapo.gh","password":"nyansapo"}' | python3 -c "import sys,json; print(json.load(sys.stdin)['user']['id'])")
PARENT_ID=$(curl -s -X POST http://127.0.0.1:3000/api/auth -H 'content-type: application/json' -d '{"email":"parent@nyansapo.gh","password":"nyansapo"}' | python3 -c "import sys,json; print(json.load(sys.stdin)['user']['id'])")
SCHOOL=$(python3 -c "
import json, subprocess
r = subprocess.run(['curl','-s','-H','x-user-id: $SUPER_ID','http://127.0.0.1:3000/api/context'], capture_output=True, text=True)
print(json.loads(r.stdout)['schools'][0]['id'])")

echo "— cashier POST payroll (expect 403):"
curl -s -o /dev/null -w "  %{http_code}\n" -X POST "http://127.0.0.1:3000/api/school/$SCHOOL/ops/payroll" -H 'content-type: application/json' -H "x-user-id: $CASHIER_ID" -d '{"action":"create-run","period":"2099-01"}'
echo "— cashier GET cashier console (expect 200):"
curl -s -o /dev/null -w "  %{http_code}\n" "http://127.0.0.1:3000/api/school/$SCHOOL/ops/cashier?from=2026-10-01&to=2026-10-31" -H "x-user-id: $CASHIER_ID"
echo "— cashier GET payroll (expect 403):"
curl -s -o /dev/null -w "  %{http_code}\n" "http://127.0.0.1:3000/api/school/$SCHOOL/ops/payroll" -H "x-user-id: $CASHIER_ID"
echo "— parent GET school students list (expect 403):"
curl -s -o /dev/null -w "  %{http_code}\n" "http://127.0.0.1:3000/api/school/$SCHOOL/students" -H "x-user-id: $PARENT_ID"
echo "— parent GET rbac users (expect 403):"
curl -s -o /dev/null -w "  %{http_code}\n" "http://127.0.0.1:3000/api/school/$SCHOOL/ops/rbac" -H "x-user-id: $PARENT_ID"
echo "— super GET rbac users (expect 200):"
curl -s -o /dev/null -w "  %{http_code}\n" "http://127.0.0.1:3000/api/school/$SCHOOL/ops/rbac" -H "x-user-id: $SUPER_ID"
echo "— parent GET own portal (expect 200):"
curl -s -o /dev/null -w "  %{http_code}\n" "http://127.0.0.1:3000/api/school/$SCHOOL/ops/portal?view=parent" -H "x-user-id: $PARENT_ID"
echo "— parent GET staff portal view (expect 403):"
curl -s -o /dev/null -w "  %{http_code}\n" "http://127.0.0.1:3000/api/school/$SCHOOL/ops/portal?view=staff" -H "x-user-id: $PARENT_ID"
echo "— super GET library (expect 200):"
curl -s -o /dev/null -w "  %{http_code}\n" "http://127.0.0.1:3000/api/school/$SCHOOL/ops/library" -H "x-user-id: $SUPER_ID"

echo "════════ page console errors ════════"
$AB errors 2>/dev/null | tail -8
echo "DONE — screenshots in /tmp/rbac-verify/"

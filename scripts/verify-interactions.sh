#!/bin/bash
# Interaction tests v5 — correct IIFEs (braces + return), React-safe clicks.
set -u
cd /home/z/my-project
PGURL="postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public"

if ! curl -s -o /dev/null --max-time 3 http://127.0.0.1:3000 2>/dev/null; then
  setsid nohup env DATABASE_URL="$PGURL" bun run dev > /dev/null 2>&1 < /dev/null &
  for i in $(seq 1 60); do
    curl -s -o /dev/null --max-time 3 http://127.0.0.1:3000 2>/dev/null && break
    sleep 1
  done
fi
curl -s -o /dev/null --max-time 3 http://127.0.0.1:3000 && echo "SERVER UP" || exit 1

AB="agent-browser"
mkdir -p /tmp/rbac-verify

login_as() {
  local email="$1"
  $AB open http://localhost:3000/ > /dev/null 2>&1
  sleep 1
  $AB eval "localStorage.clear(); 'ok'" > /dev/null 2>&1
  $AB reload > /dev/null 2>&1
  $AB wait --fn "!!Array.from(document.querySelectorAll('button')).find(b => b.textContent.trim() === 'Sign in')" --timeout 20000 > /dev/null 2>&1
  $AB eval "Array.from(document.querySelectorAll('button')).find(b => b.textContent.trim() === 'Sign in').click(); 'ok'" > /dev/null 2>&1
  sleep 1.5
  $AB eval "(() => { const t = '$email'; const b = Array.from(document.querySelectorAll('button')).find(x => x.textContent.includes(t)); if (!b) return 'QUICK BTN NOT FOUND'; b.click(); return 'clicked'; })()" > /dev/null 2>&1
  $AB wait --fn "!!document.querySelector('aside nav button')" --timeout 25000 > /dev/null 2>&1
  sleep 3
  echo "  $($AB eval "!!document.querySelector('aside nav button') ? 'LOGGED IN' : 'LOGIN FAILED'" 2>/dev/null)"
}

echo "═══ 1. Library issue book (librarian) ═══"
login_as "librarian@nyansapo.gh"
$AB eval "(() => { const b = Array.from(document.querySelectorAll('aside nav button')).find(x => x.textContent.trim() === 'Library'); if (!b) return 'NAV MISSING'; b.click(); return 'ok'; })()" > /dev/null 2>&1
sleep 3.5
echo "— module: $($AB eval "(document.querySelector('main h1') || {innerText:'?'}).innerText" 2>/dev/null)"
$AB eval "Array.from(document.querySelectorAll('main button')).find(b => b.textContent.includes('Issue book')).click(); 'ok'" > /dev/null 2>&1
sleep 2.5
echo "— dialog: $($AB eval "(document.querySelector('[role=dialog] h2') || {innerText:'NO DIALOG'}).innerText" 2>/dev/null)"
$AB eval "(() => { const inp = document.querySelector('[role=dialog] input'); if (!inp) return 'NO INPUT'; const set = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set; set.call(inp, 'Selorm'); inp.dispatchEvent(new Event('input', {bubbles:true})); return 'typed'; })()" > /dev/null 2>&1
sleep 3
echo "— learners found: $($AB eval "Array.from(document.querySelectorAll('[role=dialog] button')).filter(b => b.textContent.includes('NY-')).length" 2>/dev/null)"
echo "— pick: $($AB eval "(() => { const b = Array.from(document.querySelectorAll('[role=dialog] button')).find(x => x.textContent.includes('NY-')); if (!b) return 'NO LEARNER'; b.click(); return 'picked ' + b.textContent.slice(0, 36); })()" 2>/dev/null)"
sleep 1.5
echo "— combobox: $($AB eval "(() => { const cb = document.querySelector('[role=dialog] [role=combobox]'); if (!cb) return 'NO COMBOBOX'; cb.click(); return 'opened'; })()" 2>/dev/null)"
sleep 2
echo "— book: $($AB eval "(() => { const o = Array.from(document.querySelectorAll('[role=option]'))[0]; if (!o) return 'NO OPTIONS'; o.click(); return 'picked ' + o.textContent.slice(0, 32); })()" 2>/dev/null)"
sleep 1.5
echo "— submit: $($AB eval "(() => { const b = Array.from(document.querySelectorAll('[role=dialog] button')).find(x => x.textContent.trim() === 'Issue book'); if (!b) return 'NO SUBMIT'; if (b.disabled) return 'SUBMIT DISABLED (validation)'; b.click(); return 'submitted'; })()" 2>/dev/null)"
sleep 3.5
echo "— after issue: $($AB eval "document.querySelector('main') ? document.querySelector('main').innerText.replace(/\n+/g,' · ').slice(0, 230) : 'NO MAIN'" 2>/dev/null)"
$AB screenshot /tmp/rbac-verify/library-issued.png > /dev/null 2>&1

echo ""
echo "═══ 2. RBAC permission editor (super) ═══"
login_as "super@nyansapo.gh"
echo "— nav click: $($AB eval "(() => { const b = Array.from(document.querySelectorAll('aside nav button')).find(x => x.textContent.includes('Users & Access')); if (!b) return 'NAV MISSING'; b.click(); return 'ok'; })()" 2>/dev/null)"
sleep 4
echo "— page: $($AB eval "(document.querySelector('main h1') || document.querySelector('main h2') || {innerText:'?'}).innerText" 2>/dev/null)"
echo "— rows: $($AB eval "document.querySelectorAll('tbody tr').length" 2>/dev/null) | cashier row: $($AB eval "(() => { const row = Array.from(document.querySelectorAll('tbody tr')).find(tr => tr.textContent.includes('cashier@nyansapo.gh')); return row ? 'FOUND' : 'NOT FOUND'; })()" 2>/dev/null)"
echo "— open menu: $($AB eval "(() => { const row = Array.from(document.querySelectorAll('tbody tr')).find(tr => tr.textContent.includes('cashier@nyansapo.gh')); if (!row) return 'NO ROW'; const btn = row.querySelector('td:last-child button'); btn.dispatchEvent(new PointerEvent('pointerdown', {bubbles:true, button:0, pointerId:1, pointerType:'mouse'})); btn.dispatchEvent(new PointerEvent('pointerup', {bubbles:true, button:0, pointerId:1, pointerType:'mouse'})); btn.click(); return 'menu opened'; })()" 2>/dev/null)"
sleep 2
echo "— menu: $($AB eval "Array.from(document.querySelectorAll('[role=menuitem]')).map(m => m.textContent.trim()).join(' / ')" 2>/dev/null)"
echo "— open editor: $($AB eval "(() => { const mi = Array.from(document.querySelectorAll('[role=menuitem]')).find(m => m.textContent.includes('Permissions')); if (!mi) return 'NO PERMISSIONS ITEM'; mi.click(); return 'editor opening'; })()" 2>/dev/null)"
sleep 2.5
echo "— dialog: $($AB eval "(document.querySelector('[role=dialog] h2') || {innerText:'NO DIALOG'}).innerText" 2>/dev/null)"
echo "— academic tab: $($AB eval "(() => { const t = Array.from(document.querySelectorAll('[role=dialog] [role=tab]')).find(x => x.textContent.includes('Academic')); if (!t) return 'TAB NOT FOUND'; t.id = 'perm-academic-tab'; return 'tagged'; })()" 2>/dev/null)"
$AB scrollintoview "#perm-academic-tab" > /dev/null 2>&1
$AB click "#perm-academic-tab" > /dev/null 2>&1
sleep 1.5
echo "— checkboxes: $($AB eval "Array.from(document.querySelectorAll('[role=dialog] [role=checkbox]')).length" 2>/dev/null)"
echo "— toggle: $($AB eval "(() => { const lbl = Array.from(document.querySelectorAll('[role=dialog] label')).find(l => l.textContent.includes('Enter marks')); if (!lbl) return 'LABEL NOT FOUND'; const cb = lbl.querySelector('[role=checkbox]'); if (!cb) return 'NO CHECKBOX'; cb.id = 'perm-gb-enter'; return 'tagged'; })()" 2>/dev/null)"
$AB scrollintoview "#perm-gb-enter" > /dev/null 2>&1
$AB click "#perm-gb-enter" > /dev/null 2>&1
sleep 1.5
echo "— aria-checked: $($AB eval "(() => { const el = document.getElementById('perm-gb-enter'); return el ? el.getAttribute('aria-checked') : 'gone'; })()" 2>/dev/null)"
echo "— implied view badge: $($AB eval "(() => { const lbl = Array.from(document.querySelectorAll('[role=dialog] label')).find(l => l.textContent.includes('View gradebook')); if (!lbl) return 'NO LABEL'; const cb = lbl.querySelector('[role=checkbox]'); const badge = lbl.innerText.includes('implied') ? ' (implied badge shown)' : ''; return 'gradebook.view checked=' + (cb ? cb.getAttribute('aria-checked') : 'none') + badge; })()" 2>/dev/null)"
$AB screenshot /tmp/rbac-verify/perm-editor.png > /dev/null 2>&1
echo "— save: $($AB eval "(() => { const b = Array.from(document.querySelectorAll('[role=dialog] button')).find(x => x.textContent.trim() === 'Save permissions'); if (!b) return 'NO SAVE'; b.id = 'perm-save-btn'; return 'tagged'; })()" 2>/dev/null)"
$AB scrollintoview "#perm-save-btn" > /dev/null 2>&1
$AB click "#perm-save-btn" > /dev/null 2>&1
sleep 3
echo "— dialog after save: $($AB eval "document.querySelector('[role=dialog]') ? 'STILL OPEN (bad)' : 'closed (saved)'" 2>/dev/null)"
echo "— persisted via API:"
SUPER_ID=$(curl -s -X POST http://127.0.0.1:3000/api/auth -H 'content-type: application/json' -d '{"email":"super@nyansapo.gh","password":"nyansapo"}' | python3 -c "import sys,json; print(json.load(sys.stdin)['user']['id'])")
SCHOOL=$(curl -s -H "x-user-id: $SUPER_ID" http://127.0.0.1:3000/api/context | python3 -c "import sys,json; print(json.load(sys.stdin)['schools'][0]['id'])")
curl -s -H "x-user-id: $SUPER_ID" "http://127.0.0.1:3000/api/school/$SCHOOL/ops/rbac" | python3 -c "
import sys, json
d = json.load(sys.stdin)
u = next(x for x in d['users'] if x['email'] == 'cashier@nyansapo.gh')
print('  overrides:', json.dumps(u['overrides']), '| gradebook.enter effective:', 'gradebook.enter' in u['permissions'], '| implied gradebook.view:', 'gradebook.view' in u['permissions'])"
echo ""
echo "— cashier nav reflects new permission?"
login_as "cashier@nyansapo.gh"
echo "  cashier NAV: $($AB eval "Array.from(document.querySelectorAll('aside nav button')).map(b => b.textContent.trim()).join(' | ')" 2>/dev/null)"
echo "— revert override (restore clean demo state):"
CASHIER_DB_ID=$(curl -s -H "x-user-id: $SUPER_ID" "http://127.0.0.1:3000/api/school/$SCHOOL/ops/rbac" | python3 -c "import sys,json; d=json.load(sys.stdin); print(next(x['id'] for x in d['users'] if x['email']=='cashier@nyansapo.gh'))")
curl -s -o /dev/null -w "  revert HTTP %{http_code}\n" -X POST "http://127.0.0.1:3000/api/school/$SCHOOL/ops/rbac" -H 'content-type: application/json' -H "x-user-id: $SUPER_ID" -d "{\"action\":\"user.perms\",\"id\":\"$CASHIER_DB_ID\",\"extra\":[],\"denied\":[]}"

echo ""
echo "═══ console errors ═══"
$AB errors 2>/dev/null | tail -5
echo "DONE"

#!/bin/bash
# UI verification via the REAL login flow, with deterministic waits.
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
curl -s -o /dev/null --max-time 3 http://127.0.0.1:3000 && echo "SERVER UP" || { echo "SERVER FAILED"; exit 1; }

mkdir -p /tmp/rbac-verify
AB="agent-browser"

# ── purge stale service worker + caches (dev builds share chunk URLs) ──
$AB open http://localhost:3000/ > /dev/null 2>&1
sleep 1
$AB eval "Promise.all([navigator.serviceWorker.getRegistrations().then(rs => Promise.all(rs.map(r => r.unregister()))), caches.keys().then(ks => Promise.all(ks.map(k => caches.delete(k))))]).then(() => 'purged')" > /dev/null 2>&1
sleep 1
$AB reload > /dev/null 2>&1
sleep 2

login_as() {
  local email="$1"
  $AB open http://localhost:3000/ > /dev/null 2>&1
  sleep 1
  $AB eval "localStorage.clear(); 'cleared'" > /dev/null 2>&1
  $AB reload > /dev/null 2>&1
  # wait for landing (Sign in button)
  $AB wait --fn "!!Array.from(document.querySelectorAll('button')).find(b => b.textContent.trim() === 'Sign in')" --timeout 20000 > /dev/null 2>&1
  $AB eval "Array.from(document.querySelectorAll('button')).find(b => b.textContent.trim() === 'Sign in').click(); 'signin-clicked'" > /dev/null 2>&1
  # wait for login card quick accounts
  $AB wait --fn "!!Array.from(document.querySelectorAll('button')).find(b => b.textContent.includes('nyansapo.gh'))" --timeout 20000 > /dev/null 2>&1
  $AB eval "Array.from(document.querySelectorAll('button')).find(b => b.textContent.includes('$email')).click(); 'quick-clicked'" > /dev/null 2>&1
  # wait for the app shell (nav renders only with a session)
  $AB wait --fn "!!document.querySelector('aside nav button, [data-slot=sheet-content] nav button') || document.body.innerText.includes('Sign-in failed')" --timeout 30000 > /dev/null 2>&1
  sleep 2
}

navdump() {
  $AB eval "Array.from(document.querySelectorAll('aside nav button')).map(b => b.textContent.trim()).filter(Boolean).join(' | ')" 2>/dev/null
}
headertext() {
  $AB eval "(document.querySelector('main h1') || document.querySelector('main h2') || {innerText:'(no heading)'}).innerText" 2>/dev/null
}
maintext() {
  $AB eval "document.querySelector('main') ? document.querySelector('main').innerText.replace(/\n+/g, ' · ').slice(0, 300) : '(no main)'" 2>/dev/null
}

for ROLE in super head cashier conductor librarian staff parent student teacher; do
  echo "════════ $ROLE ════════"
  login_as "$ROLE@nyansapo.gh"
  echo "  NAV : $(navdump)"
  echo "  HEAD: $(headertext)"
  echo "  TXT : $(maintext)"
  $AB screenshot "/tmp/rbac-verify/$ROLE.png" > /dev/null 2>&1
done

echo "════════ console errors ════════"
$AB errors 2>/dev/null | tail -6
echo "DONE"

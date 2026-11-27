#!/usr/bin/env bash
# ── wave 77 — Parent payments & School Shop end-to-end smoke ──
# Covers the full parent self-service money story:
#   RBAC (staff refused parent actions · teacher refused the shop console),
#   catalog CRUD + stock guard, parent order create / cancel / pay ONLINE
#   (simulated MoMo through the public pay page), stock drawn on payment,
#   idempotent completion (double-simulate refused), counter payment with a
#   numbered receipt, pack → deliver lifecycle + guardian SMS trail,
#   prepaid wallet top-up credited to the ledger, and a partial ONLINE fee
#   payment landing on the invoice. Verifies the ledger state in the DB,
#   then restores everything (cleanup-shop-smoke.ts).
set -u
BASE=http://localhost:3000
SID=cmwq8hof4000zmoe1gbjv3rr1
export DATABASE_URL='postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public'
STATE=/tmp/smoke-shop-state.json

up() { curl -s -o /dev/null -w "%{http_code}" "$BASE/" --max-time 8; }
if [ "$(up)" != "200" ]; then
  echo "dev server down — starting…"
  bash /home/z/my-project/scripts/ensure-server.sh
fi
echo "server: $(up)"

jqpy() { python3 -c "import json,sys; d=json.load(sys.stdin); $1"; }
fail() { echo "FAIL: $1"; exit 1; }
code() { curl -s -o /dev/null -w "%{http_code}" "$@"; }

BURSAR=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"bursar@nyansapo.gh","password":"nyansapo"}' | jqpy "print(d['user']['id'])") || fail "bursar login"
TEACHER=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"teacher@nyansapo.gh","password":"nyansapo"}' | jqpy "print(d['user']['id'])") || fail "teacher login"
PARENT=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"parent@nyansapo.gh","password":"nyansapo"}' | jqpy "print(d['user']['id'])") || fail "parent login"
BH="x-user-id: $BURSAR"; TH="x-user-id: $TEACHER"; PH="x-user-id: $PARENT"
PORTAL="$BASE/api/school/$SID/ops/portal"
SHOP="$BASE/api/school/$SID/ops/shop"
echo "bursar=$BURSAR teacher=$TEACHER parent=$PARENT"

# ── 1 — shop console baseline (bursar): items/orders/counts/lowStock ──
G=$(curl -s -H "$BH" "$SHOP")
OK=$(echo "$G" | jqpy "print('items' in d and 'orders' in d and 'counts' in d and 'lowStock' in d and 'categories' in d)")
echo "1. shop GET shape ok=$OK items=$(echo "$G" | jqpy "print(len(d['items']))") orders=$(echo "$G" | jqpy "print(len(d['orders']))")"
[ "$OK" = "True" ] || fail "shop GET payload missing collections"

# ── 2 — RBAC: teacher refused the shop console; staff refused parent-only actions ──
C1=$(code -H "$TH" "$SHOP")
C2=$(code -H "$TH" -X POST "$PORTAL" -H 'content-type: application/json' -d '{"action":"fees.pay-online","studentId":"x","invoiceId":"y"}')
C3=$(code -H "$TH" -X POST "$PORTAL" -H 'content-type: application/json' -d '{"action":"shop.order-create","studentId":"x","items":[]}')
echo "2. teacher shop GET → $C1 · teacher fees.pay-online → $C2 · teacher shop.order-create → $C3"
[ "$C1" = "403" ] || fail "teacher must not read the shop console ($C1)"
[ "$C2" = "403" ] && [ "$C3" = "403" ] || fail "staff accounts must be refused parent-only money actions"

# ── 3 — parent portal data: catalog visible, kids linked, own scope enforced ──
P=$(curl -s -H "$PH" "$PORTAL?view=parent")
NITEMS=$(echo "$P" | jqpy "print(len(d['shopItems']))")
NKIDS=$(echo "$P" | jqpy "print(len(d['children']))")
KID=$(echo "$P" | jqpy "print(d['children'][0]['id'])")
KIDNAME=$(echo "$P" | jqpy "print(d['children'][0]['name'])")
GUARD=$(echo "$P" | jqpy "print(d['children'][0]['guardianPhone'])")
MODE=$(echo "$P" | jqpy "print(d['paymentsMode'])")
FOREIGN=$(code -H "$PH" -X POST "$PORTAL" -H 'content-type: application/json' -d '{"action":"shop.order-create","studentId":"cmNOTMINE000000000000000","items":[]}')
echo "3. parent catalog=$NITEMS kids=$NKIDS ($KIDNAME) mode=$MODE · order for a foreign learner → $FOREIGN"
[ "$FOREIGN" = "403" ] || fail "parent must be scoped to their own children ($FOREIGN)"

# ── 4 — catalog CRUD (bursar): create a smoke item, stock 10 ──
TAG="SMK77-$(date +%s)"
IT=$(curl -s -H "$BH" -X POST "$SHOP" -H 'content-type: application/json' -d "{\"action\":\"item.create\",\"name\":\"$TAG Exercise Book\",\"category\":\"STATIONERY\",\"priceCents\":500,\"stock\":10,\"lowStockAt\":3}")
ITEM=$(echo "$IT" | jqpy "print(d['item']['id'])")
echo "4. item created $ITEM ($TAG Exercise Book — ₵5.00, stock 10)"
[ -n "$ITEM" ] || fail "item.create failed: $IT"
# invalid price refused
BAD=$(code -H "$BH" -X POST "$SHOP" -H 'content-type: application/json' -d '{"action":"item.create","name":"no","priceCents":0,"stock":1}')
echo "   bad price → $BAD"; [ "$BAD" = "400" ] || fail "zero price must be refused ($BAD)"

# ── 5 — parent order: 2× book, PENDING_PAYMENT, stock NOT drawn yet ──
O=$(curl -s -H "$PH" -X POST "$PORTAL" -H 'content-type: application/json' -d "{\"action\":\"shop.order-create\",\"studentId\":\"$KID\",\"items\":[{\"itemId\":\"$ITEM\",\"qty\":2}],\"deliveryNote\":\"$TAG\"}")
ORD1=$(echo "$O" | jqpy "print(d['order']['id'])")
NO1=$(echo "$O" | jqpy "print(d['order']['orderNo'])")
TOT1=$(echo "$O" | jqpy "print(d['order']['total'])")
STOCK0=$(curl -s -H "$BH" "$SHOP" | jqpy "print([i['stock'] for i in d['items'] if i['id']=='$ITEM'][0])")
echo "5. order $NO1 total=$TOT1 stock-after-create=$STOCK0 (expect 1000 / 10)"
[ "$TOT1" = "1000" ] || fail "order total wrong: $TOT1"
[ "$STOCK0" = "10" ] || fail "stock must not move until payment ($STOCK0)"
# over-stock order refused
OVR=$(code -H "$PH" -X POST "$PORTAL" -H 'content-type: application/json' -d "{\"action\":\"shop.order-create\",\"studentId\":\"$KID\",\"items\":[{\"itemId\":\"$ITEM\",\"qty\":99}]}")
echo "   qty 99 order → $OVR"; [ "$OVR" = "400" ] || fail "over-stock order must be refused ($OVR)"

# ── 6 — parent cancels it, reorders 2× again (fresh PENDING order) ──
C=$(curl -s -H "$PH" -X POST "$PORTAL" -H 'content-type: application/json' -d "{\"action\":\"shop.order-cancel\",\"orderId\":\"$ORD1\"}")
[ "$(echo "$C" | jqpy "print(d.get('ok'))")" = "True" ] || fail "order cancel failed: $C"
O=$(curl -s -H "$PH" -X POST "$PORTAL" -H 'content-type: application/json' -d "{\"action\":\"shop.order-create\",\"studentId\":\"$KID\",\"items\":[{\"itemId\":\"$ITEM\",\"qty\":2}],\"deliveryNote\":\"$TAG\"}")
ORD1=$(echo "$O" | jqpy "print(d['order']['id'])")
NO1=$(echo "$O" | jqpy "print(d['order']['orderNo'])")
echo "6. cancel ok · reordered $NO1 ($ORD1)"

# ── 7 — pay ONLINE: simulated MoMo prompt → public pay page completes it ──
PAY=$(curl -s -H "$PH" -X POST "$PORTAL" -H 'content-type: application/json' -d "{\"action\":\"shop.order-pay\",\"orderId\":\"$ORD1\",\"method\":\"MOMO_PROMPT\",\"payerMsisdn\":\"+233 24 818 3344\"}")
REQ1=$(echo "$PAY" | jqpy "print(d['request']['id'])")
REF1=$(echo "$PAY" | jqpy "print(d['request']['clientReference'])")
PRV=$(echo "$PAY" | jqpy "print(d['request']['provider'])")
echo "7. request $REQ1 ref=$REF1 provider=$PRV status=$(echo "$PAY" | jqpy "print(d['request']['status'])")"
[ "$(echo "$PAY" | jqpy "print(d['request']['status'])")" = "PENDING" ] || fail "request should start PENDING"
SIM=$(curl -s -X POST "$BASE/api/public/pay/$REF1" -H 'content-type: application/json' -d '{}')
echo "   simulate → $SIM"
CHK=$(curl -s -H "$PH" -X POST "$PORTAL" -H 'content-type: application/json' -d "{\"action\":\"payment.check\",\"requestId\":\"$REQ1\"}")
ST=$(echo "$CHK" | jqpy "print(d['request']['status'])")
NOTE=$(echo "$CHK" | jqpy "print(d['request']['appliedNote'])")
echo "   check → $ST · $NOTE"
[ "$ST" = "COMPLETED" ] || fail "payment should complete ($ST)"
S1=$(curl -s -H "$BH" "$SHOP" | jqpy "
o=[x for x in d['orders'] if x['id']=='$ORD1'][0]
print(o['status'], o['paymentMethod'], [i['stock'] for i in d['items'] if i['id']=='$ITEM'][0])")
echo "   order now: $S1 (expect PAID ONLINE stock 8)"
[ "$(echo "$S1" | awk '{print $1}')" = "PAID" ] && [ "$(echo "$S1" | awk '{print $2}')" = "ONLINE" ] || fail "order should be PAID/ONLINE ($S1)"
[ "$(echo "$S1" | awk '{print $3}')" = "8" ] || fail "stock should have been drawn by 2 ($S1)"

# ── 8 — idempotency: simulating the completed request again is refused ──
D=$(code -X POST "$BASE/api/public/pay/$REF1" -H 'content-type: application/json' -d '{}')
echo "8. double-simulate → $D"; [ "$D" = "400" ] || fail "double completion must be refused ($D)"

# ── 9 — second order paid at the COUNTER with a numbered receipt ──
O=$(curl -s -H "$PH" -X POST "$PORTAL" -H 'content-type: application/json' -d "{\"action\":\"shop.order-create\",\"studentId\":\"$KID\",\"items\":[{\"itemId\":\"$ITEM\",\"qty\":1}],\"deliveryNote\":\"$TAG\"}")
ORD2=$(echo "$O" | jqpy "print(d['order']['id'])")
NO2=$(echo "$O" | jqpy "print(d['order']['orderNo'])")
MP=$(curl -s -H "$BH" -X POST "$SHOP" -H 'content-type: application/json' -d "{\"action\":\"order.mark-paid\",\"orderId\":\"$ORD2\",\"method\":\"CASH\"}")
RCP=$(echo "$MP" | jqpy "print(d.get('receiptNo'))")
echo "9. counter payment on $NO2 → receipt $RCP"
[ -n "$RCP" ] && [ "$RCP" != "None" ] || fail "counter payment should mint a receipt ($MP)"
S2=$(curl -s -H "$BH" "$SHOP" | jqpy "
o=[x for x in d['orders'] if x['id']=='$ORD2'][0]
print(o['status'], o['paymentMethod'], [i['stock'] for i in d['items'] if i['id']=='$ITEM'][0])")
echo "   order now: $S2 (expect PAID COUNTER stock 7)"
[ "$(echo "$S2" | awk '{print $2}')" = "COUNTER" ] || fail "counter method expected ($S2)"
# deliver before ready refused
EARLY=$(code -H "$BH" -X POST "$SHOP" -H 'content-type: application/json' -d "{\"action\":\"order.deliver\",\"orderId\":\"$ORD2\"}")
echo "   deliver-before-ready → $EARLY"; [ "$EARLY" = "409" ] || fail "premature delivery must be refused ($EARLY)"

# ── 10 — pack → deliver; both SMS trail events recorded ──
curl -s -H "$BH" -X POST "$SHOP" -H 'content-type: application/json' -d "{\"action\":\"order.ready\",\"orderId\":\"$ORD2\"}" >/dev/null
curl -s -H "$BH" -X POST "$SHOP" -H 'content-type: application/json' -d "{\"action\":\"order.deliver\",\"orderId\":\"$ORD2\"}" >/dev/null
S3=$(curl -s -H "$BH" "$SHOP" | jqpy "print([x for x in d['orders'] if x['id']=='$ORD2'][0]['status'])")
echo "10. lifecycle end: $S3 (expect DELIVERED)"; [ "$S3" = "DELIVERED" ] || fail "order should be DELIVERED ($S3)"

# ── 11 — wallet top-up ONLINE: ₵20 into the prepaid wallet ──
W0=$(echo "$P" | jqpy "
w=[c for c in d['children'] if c['id']=='$KID'][0]['wallet']
print(w['prepaid'] if w else 0)")
WT=$(curl -s -H "$PH" -X POST "$PORTAL" -H 'content-type: application/json' -d "{\"action\":\"wallet.topup\",\"studentId\":\"$KID\",\"walletKind\":\"PREPAID\",\"amountCents\":2000,\"method\":\"MOMO_PROMPT\",\"payerMsisdn\":\"+233 24 818 3344\"}")
REQ2=$(echo "$WT" | jqpy "print(d['request']['id'])")
REF2=$(echo "$WT" | jqpy "print(d['request']['clientReference'])")
curl -s -X POST "$BASE/api/public/pay/$REF2" -H 'content-type: application/json' -d '{}' >/dev/null
WCHK=$(curl -s -H "$PH" -X POST "$PORTAL" -H 'content-type: application/json' -d "{\"action\":\"payment.check\",\"requestId\":\"$REQ2\"}")
W1=$(curl -s -H "$PH" "$PORTAL?view=parent" | jqpy "
w=[c for c in d['children'] if c['id']=='$KID'][0]['wallet']
print(w['prepaid'] if w else 0)")
echo "11. wallet prepaid $W0 → $W1 (expect +2000) · status=$(echo "$WCHK" | jqpy "print(d['request']['status'])")"
[ $((W1 - W0)) -eq 2000 ] || fail "wallet must gain exactly ₵20 ($W0 → $W1)"

# ── 12 — ONLINE fee payment: ₵2 partial on the first open invoice across the kids ──
INV=$(echo "$P" | jqpy "
for c in d['children']:
    for i in c['invoices']:
        if i['balance'] > 0:
            print(json.dumps({'id': i['id'], 'paid': i['paid'], 'status': i['status'], 'kid': c['id']}))
            raise SystemExit
print('NONE')")
[ "$INV" = "NONE" ] && fail "no open invoice for $KIDNAME — cannot exercise the fees path"
INVID=$(echo "$INV" | jqpy "print(d['id'])")
INVP0=$(echo "$INV" | jqpy "print(d['paid'])")
INVST0=$(echo "$INV" | jqpy "print(d['status'])")
FEEKID=$(echo "$INV" | jqpy "print(d['kid'])")
FP=$(curl -s -H "$PH" -X POST "$PORTAL" -H 'content-type: application/json' -d "{\"action\":\"fees.pay-online\",\"studentId\":\"$FEEKID\",\"invoiceId\":\"$INVID\",\"amountCents\":200,\"method\":\"MOMO_PROMPT\",\"payerMsisdn\":\"+233 24 818 3344\"}")
REQ3=$(echo "$FP" | jqpy "print(d['request']['id'])")
REF3=$(echo "$FP" | jqpy "print(d['request']['clientReference'])")
echo "12. fees request on $INVID (paid was $INVP0, $INVST0) ref=$REF3"
curl -s -X POST "$BASE/api/public/pay/$REF3" -H 'content-type: application/json' -d '{}' >/dev/null
FCHK=$(curl -s -H "$PH" -X POST "$PORTAL" -H 'content-type: application/json' -d "{\"action\":\"payment.check\",\"requestId\":\"$REQ3\"}")
INVP1=$(curl -s -H "$PH" "$PORTAL?view=parent" | jqpy "
inv=[i for c in d['children'] if c['id']=='$FEEKID' for i in c['invoices'] if i['id']=='$INVID']
print(inv[0]['paid'])")
echo "    invoice paid $INVP0 → $INVP1 (expect +200) · $(echo "$FCHK" | jqpy "print(d['request']['appliedNote'])")"
[ $((INVP1 - INVP0)) -eq 200 ] || fail "invoice must gain exactly ₵2 ($INVP0 → $INVP1)"

# ── 13 — shop ledger verification in the DB, then full cleanup ──
python3 - "$STATE" << PYEOF
import json, sys
state = {
  "tag": "$TAG", "schoolId": "$SID", "kidId": "$KID", "kidName": "$KIDNAME",
  "itemIds": ["$ITEM"], "orderNos": ["$NO1", "$NO2"],
  "orderIds": ["$ORD1", "$ORD2"], "requestIds": ["$REQ1", "$REQ2", "$REQ3"],
  "shopRefs": ["$REF1"],
  "receiptNos": ["$RCP"],
  "wallet": {"studentId": "$KID", "kind": "PREPAID", "amountCents": 2000, "refs": ["$REF2"]},
  "invoice": {"id": "$INVID", "origPaidCents": $INVP0, "origStatus": "$INVST0", "payCents": 200, "refs": ["$REF3"]},
  "feeKidId": "$FEEKID",
}
open(sys.argv[1], "w").write(json.dumps(state, indent=2))
print("state:", sys.argv[1])
PYEOF
bun /home/z/my-project/scripts/cleanup-shop-smoke.ts "$STATE" --verify || fail "DB verification failed"
echo "13. verify+cleanup OK"

echo ""
echo "ALL GREEN — wave 77 smoke passed (13 steps)"

#!/usr/bin/env bash
# ── wave 78 — multi-gateway collections smoke (Paystack + Flutterwave + sandbox webhooks)
# Rehearses every gateway path the parent portal and the bursar console can
# drive, without any gateway credentials (SIMULATED sandbox mode):
#   wallet top-up via PAYSTACK completed by a (sandbox) webhook
#   wallet top-up via FLUTTERWAVE completed by the pay-page simulate button
#   partial fee payment via PAYSTACK (invoice + receipt SMS)
#   shop order paid online via FLUTTERWAVE (PAID + stock drawn)
#   amount-mismatch / unknown-ref / foreign-ref webhook defences
#   double-webhook replay never double-posts (CAS)
#   payment.check stays inert on simulated requests
# Then scripts/cleanup-providers-smoke.ts --verify checks the ledger traces
# in the DB and restores everything.
set -u
BASE=http://localhost:3000
SID=cmwq8hof4000zmoe1gbjv3rr1
export DATABASE_URL='postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public'
STATE=/tmp/smoke-providers-state.json
up() { curl -s -o /dev/null -w "%{http_code}" "$BASE/" --max-time 8; }
if [ "$(up)" != "200" ]; then bash /home/z/my-project/scripts/ensure-server.sh; fi
jqpy() { python3 -c "import json,sys; d=json.load(sys.stdin); $1"; }
fail() { echo "FAIL: $1"; exit 1; }
post() { curl -s -X POST "$1" -H "$2" -H 'content-type: application/json' -d "$3"; }
code() { curl -s -o /dev/null -w "%{http_code}" -X POST "$1" -H "$2" -H 'content-type: application/json' -d "$3"; }

PARENT=$(curl -s -X POST $BASE/api/auth -d '{"email":"parent@nyansapo.gh","password":"nyansapo"}' | jqpy "print(d['user']['id'])")
TEACHER=$(curl -s -X POST $BASE/api/auth -d '{"email":"teacher@nyansapo.gh","password":"nyansapo"}' | jqpy "print(d['user']['id'])")
BURSAR=$(curl -s -X POST $BASE/api/auth -d '{"email":"bursar@nyansapo.gh","password":"nyansapo"}' | jqpy "print(d['user']['id'])")
[ -n "$PARENT" ] && [ -n "$TEACHER" ] && [ -n "$BURSAR" ] || fail "auth failed"
PH="x-user-id: $PARENT"; TH="x-user-id: $TEACHER"; BH="x-user-id: $BURSAR"
echo "auth ok — parent/teacher/bursar"

# ── 1. portal payload shape: paymentsProviders is a list ──────────
VIEW=$(curl -s "$BASE/api/school/$SID/ops/portal?view=parent" -H "$PH")
echo "$VIEW" | jqpy "assert isinstance(d.get('paymentsProviders'), list)" || fail "paymentsProviders missing"
PROVS=$(echo "$VIEW" | jqpy "print(','.join(d['paymentsProviders']))")
echo "1 ok — paymentsProviders=[${PROVS:-<sandbox, all simulated>}]"

# ── 2. RBAC: teacher can never touch parent money actions ─────────
C=$(code "$BASE/api/school/$SID/ops/portal" "$TH" '{"action":"wallet.topup","studentId":"x","amountCents":100}')
[ "$C" = "403" ] || fail "teacher wallet.topup → $C (want 403)"
echo "2 ok — teacher refused (403)"

# ── 3. child (one with a payable invoice), wallet snapshot, dedicated shop item ──
CHILD=$(echo "$VIEW" | jqpy "
kid=[c for c in d['children'] if any(i['balance']>=1000 for i in c['invoices'])]
assert kid, 'no child with a payable invoice'
print(kid[0]['id'])") || fail "no child with payable invoice"
CNAME=$(echo "$VIEW" | jqpy "print([c for c in d['children'] if c['id']=='$CHILD'][0]['name'])")
KPHONE=$(echo "$VIEW" | jqpy "print([c for c in d['children'] if c['id']=='$CHILD'][0].get('guardianPhone') or '0241000001')")
W0=$(echo "$VIEW" | jqpy "print((([c for c in d['children'] if c['id']=='$CHILD'][0].get('wallet') or {}).get('prepaid')) or 0)")
INV=$(echo "$VIEW" | jqpy "c=[c for c in d['children'] if c['id']=='$CHILD'][0]; inv=[i for i in c['invoices'] if i['balance']>=1000]; inv.sort(key=lambda i:-i['balance']); print(inv[0]['id'])")
INVNO=$(echo "$VIEW" | jqpy "c=[c for c in d['children'] if c['id']=='$CHILD'][0]; inv=[i for i in c['invoices'] if i['balance']>=1000]; inv.sort(key=lambda i:-i['balance']); print(inv[0]['invoiceNo'])")
IB0=$(echo "$VIEW" | jqpy "c=[c for c in d['children'] if c['id']=='$CHILD'][0]; inv=[i for i in c['invoices'] if i['balance']>=1000]; inv.sort(key=lambda i:-i['balance']); print(inv[0]['balance'])")
# the smoke mints its own tracked shop item (the restored cluster's catalog may be empty)
R=$(post "$BASE/api/school/$SID/ops/shop" "$BH" '{"action":"item.create","name":"Wave 78 smoke notebook","category":"STATIONERY","priceCents":500,"stock":5}')
ITEM=$(echo "$R" | jqpy "print(d['item']['id'])")
STOCK0=5
IPRICE=500
[ -n "$CHILD" ] && [ -n "$INV" ] && [ -n "$ITEM" ] || fail "child/invoice/item snapshot failed"
echo "3 ok — child ${CNAME}, wallet prepaid ${W0}, invoice ${INVNO} (balance ${IB0}), smoke item ${ITEM} (stock 5, ₵5)"

# ── 4. wallet top-up via PAYSTACK → PSK- ref + pay page shape ─────
R=$(post "$BASE/api/school/$SID/ops/portal" "$PH" '{"action":"wallet.topup","studentId":"'$CHILD'","walletKind":"PREPAID","amountCents":1200,"method":"PAYMENT_LINK","provider":"PAYSTACK","payerMsisdn":"'"$KPHONE"'"}')
REF1=$(echo "$R" | jqpy "print(d['request']['clientReference'])")
REQ1=$(echo "$R" | jqpy "print(d['request']['id'])")
echo "$R" | jqpy "assert d['request']['provider']=='PAYSTACK' and d['request']['status']=='PENDING'" || fail "paystack topup shape — R=$R"
echo "$REF1" | grep -q '^PSK-' || fail "reference $REF1 should be PSK-prefixed"
curl -s "$BASE/api/public/pay/$REF1" | jqpy "assert d['providerLabel']=='Paystack' and d['simulated']==True and d['status']=='PENDING' and d['amountCents']==1200" || fail "pay page shape (paystack)"
echo "4 ok — wallet top-up ₵12 via Paystack ($REF1)"

# ── 5. Paystack webhook defences (amount mismatch + unknown ref) ──
R=$(post "$BASE/api/webhooks/paystack" "" '{"event":"charge.success","data":{"reference":"'$REF1'","amount":999999}}')
echo "$R" | jqpy "assert d.get('ignored')=='amount mismatch'" || fail "amount mismatch not ignored: $R"
R=$(post "$BASE/api/webhooks/paystack" "" '{"event":"charge.success","data":{"reference":"PSK-UNKNOWN-REF","amount":1200}}')
echo "$R" | jqpy "assert d.get('ignored')=='unknown reference'" || fail "unknown ref not ignored: $R"
C=$(code "$BASE/api/webhooks/paystack" "" '{"event":"charge.success","data":{"reference":"HTL-FOREIGN-REF","amount":100}}')
[ "$C" = "401" ] || fail "foreign-prefixed unsigned ref → $C (want 401)"
echo "5 ok — webhook defences (amount mismatch, unknown ref, foreign ref 401)"

# ── 6. Paystack webhook completes the top-up → wallet credited ────
R=$(post "$BASE/api/webhooks/paystack" "" '{"event":"charge.success","data":{"reference":"'$REF1'","amount":1200}}')
echo "$R" | jqpy "assert d.get('success')==True" || fail "webhook completion failed: $R"
curl -s "$BASE/api/public/pay/$REF1" | jqpy "assert d['status']=='COMPLETED' and d['receiptNo'], d" || fail "not completed after webhook"
W1=$(curl -s "$BASE/api/school/$SID/ops/portal?view=parent" -H "$PH" | jqpy "print([c for c in d['children'] if c['id']=='$CHILD'][0]['wallet']['prepaid'])")
[ "$W1" = "$((W0 + 1200))" ] || fail "wallet $W1 want $((W0 + 1200))"
echo "6 ok — webhook completed top-up, wallet ${W0}→${W1}"

# ── 7. replay the same webhook → CAS refuses a second ledger post ──
R=$(post "$BASE/api/webhooks/paystack" "" '{"event":"charge.success","data":{"reference":"'$REF1'","amount":1200}}')
echo "$R" | jqpy "assert d.get('success')==True" || fail "replay rejected hard: $R"
W2=$(curl -s "$BASE/api/school/$SID/ops/portal?view=parent" -H "$PH" | jqpy "print([c for c in d['children'] if c['id']=='$CHILD'][0]['wallet']['prepaid'])")
[ "$W2" = "$W1" ] || fail "double-post! wallet $W1→$W2"
echo "7 ok — replayed webhook no double-post (wallet stays ${W2})"

# ── 8. wallet top-up via FLUTTERWAVE → pay-page simulate path ─────
R=$(post "$BASE/api/school/$SID/ops/portal" "$PH" '{"action":"wallet.topup","studentId":"'$CHILD'","walletKind":"PREPAID","amountCents":800,"method":"PAYMENT_LINK","provider":"FLUTTERWAVE","payerMsisdn":"'"$KPHONE"'"}')
REF2=$(echo "$R" | jqpy "print(d['request']['clientReference'])")
REQ2=$(echo "$R" | jqpy "print(d['request']['id'])")
echo "$REF2" | grep -q '^FLW-' || fail "reference $REF2 should be FLW-prefixed"
curl -s "$BASE/api/public/pay/$REF2" | jqpy "assert d['providerLabel']=='Flutterwave' and d['simulated']==True" || fail "pay page shape (flutterwave)"
R=$(post "$BASE/api/public/pay/$REF2" "$PH" '{"action":"pay"}')
echo "$R" | jqpy "assert d.get('status')=='COMPLETED'" || fail "simulate completion failed: $R"
W3=$(curl -s "$BASE/api/school/$SID/ops/portal?view=parent" -H "$PH" | jqpy "print([c for c in d['children'] if c['id']=='$CHILD'][0]['wallet']['prepaid'])")
[ "$W3" = "$((W2 + 800))" ] || fail "wallet $W3 want $((W2 + 800))"
echo "8 ok — Flutterwave top-up ₵8 via pay page ($REF2), wallet ${W2}→${W3}"

# ── 9. partial fee payment via PAYSTACK → invoice drops ₵10 ──────
R=$(post "$BASE/api/school/$SID/ops/portal" "$PH" '{"action":"fees.pay-online","studentId":"'$CHILD'","invoiceId":"'$INV'","amountCents":1000,"method":"PAYMENT_LINK","provider":"PAYSTACK","payerMsisdn":"'"$KPHONE"'"}')
REF3=$(echo "$R" | jqpy "print(d['request']['clientReference'])")
REQ3=$(echo "$R" | jqpy "print(d['request']['id'])")
R=$(post "$BASE/api/webhooks/paystack" "" '{"event":"charge.success","data":{"reference":"'$REF3'","amount":1000}}')
echo "$R" | jqpy "assert d.get('success')==True" || fail "fees webhook failed: $R"
curl -s "$BASE/api/public/pay/$REF3" | jqpy "assert d['status']=='COMPLETED' and d['receiptNo']" || fail "fees not completed"
IB1=$(curl -s "$BASE/api/school/$SID/ops/portal?view=parent" -H "$PH" | jqpy "print([i for c in d['children'] if c['id']=='$CHILD' for i in c['invoices'] if i['id']=='$INV'][0]['balance'])")
[ "$IB1" = "$((IB0 - 1000))" ] || fail "invoice balance $IB1 want $((IB0 - 1000))"
echo "9 ok — fees ₵10 via Paystack on ${INVNO}: balance ${IB0}→${IB1}"

# ── 10. a PENDING simulated request survives payment.check ────────
R=$(post "$BASE/api/school/$SID/ops/portal" "$PH" '{"action":"fees.pay-online","studentId":"'$CHILD'","invoiceId":"'$INV'","amountCents":500,"method":"PAYMENT_LINK","provider":"PAYSTACK","payerMsisdn":"'"$KPHONE"'"}')
REF4=$(echo "$R" | jqpy "print(d['request']['clientReference'])")
REQ4=$(echo "$R" | jqpy "print(d['request']['id'])")
R=$(post "$BASE/api/school/$SID/ops/portal" "$PH" '{"action":"payment.check","requestId":"'$REQ4'"}')
echo "$R" | jqpy "assert d['request']['status']=='PENDING'" || fail "simulated request did not stay pending: $R"
echo "10 ok — payment.check inert on simulated Paystack request ($REF4)"

# ── 11. shop order paid online via FLUTTERWAVE ────────────────────
R=$(post "$BASE/api/school/$SID/ops/portal" "$PH" '{"action":"shop.order-create","studentId":"'$CHILD'","items":[{"itemId":"'$ITEM'","qty":1}],"deliveryNote":"wave 78 smoke"}')
ORDER=$(echo "$R" | jqpy "print(d['order']['id'])")
ONO=$(echo "$R" | jqpy "print(d['order']['orderNo'])")
R=$(post "$BASE/api/school/$SID/ops/portal" "$PH" '{"action":"shop.order-pay","orderId":"'$ORDER'","method":"PAYMENT_LINK","provider":"FLUTTERWAVE","payerMsisdn":"'"$KPHONE"'"}')
REF5=$(echo "$R" | jqpy "print(d['request']['clientReference'])")
REQ5=$(echo "$R" | jqpy "print(d['request']['id'])")
echo "$REF5" | grep -q '^FLW-' || fail "shop ref $REF5 should be FLW-prefixed"
R=$(post "$BASE/api/public/pay/$REF5" "$PH" '{"action":"pay"}')
echo "$R" | jqpy "assert d.get('status')=='COMPLETED'" || fail "shop simulate failed: $R"
curl -s "$BASE/api/school/$SID/ops/portal?view=parent" -H "$PH" | jqpy "o=[x for x in d['shopOrders'] if x['id']=='$ORDER'][0]; assert o['status']=='PAID' and o['paymentMethod']=='ONLINE', o" || fail "order not PAID/ONLINE"
STOCK1=$(curl -s "$BASE/api/school/$SID/ops/portal?view=parent" -H "$PH" | jqpy "print([i for i in d['shopItems'] if i['id']=='$ITEM'][0]['stock'])")
[ "$STOCK1" = "$((STOCK0 - 1))" ] || fail "stock $STOCK1 want $((STOCK0 - 1))"
echo "11 ok — shop order ${ONO} paid via Flutterwave, stock ${STOCK0}→${STOCK1}"

# ── 12. Flutterwave webhook defences ──────────────────────────────
C=$(code "$BASE/api/webhooks/flutterwave" "" '{"event":"charge.completed","data":{"tx_ref":"HTL-FOREIGN","status":"successful","amount":10}}')
[ "$C" = "401" ] || fail "flutterwave foreign ref → $C (want 401)"
R=$(post "$BASE/api/webhooks/flutterwave" "" '{"event":"charge.completed","data":{"tx_ref":"FLW-UNKNOWN","status":"successful","amount":10}}')
echo "$R" | jqpy "assert d.get('ignored')=='unknown reference'" || fail "flutterwave unknown ref: $R"
echo "12 ok — Flutterwave webhook defences"

# ── 13. bursar console payload carries the provider model ─────────
P=$(curl -s "$BASE/api/school/$SID/ops/payments" -H "$BH")
echo "$P" | jqpy "assert isinstance(d.get('providers'), list)" || fail "console providers missing"
echo "$P" | jqpy "rows=[r for r in d['requests'] if r['clientReference'] in ('$REF1','$REF2','$REF3','$REF5')]; assert len(rows)==4 and sorted(r['provider'] for r in rows)==['FLUTTERWAVE','FLUTTERWAVE','PAYSTACK','PAYSTACK'], rows" || fail "console rows provider labels"
PROVS_LIST=$(echo "$P" | jqpy "print(','.join(d['providers']))" 2>/dev/null || echo "")
echo "13 ok — console providers=[${PROVS_LIST:-<sandbox, all simulated>}] + 4 provider-labelled rows"

# ── state for cleanup ─────────────────────────────────────────────
cat > "$STATE" << EOF
{"schoolId":"$SID","studentId":"$CHILD","wallet":{"studentId":"$CHILD","kind":"PREPAID","before":$W0,"refs":["$REF1","$REF2"]},
 "invoice":{"id":"$INV","invoiceNo":"$INVNO","balanceBefore":$IB0,"ref":"$REF3"},
 "shop":{"orderId":"$ORDER","orderNo":"$ONO","itemId":"$ITEM","stockBefore":$STOCK0,"ref":"$REF5"},
 "pendingRef":"$REF4","requestIds":["$REQ1","$REQ2","$REQ3","$REQ4","$REQ5"],
 "refs":["$REF1","$REF2","$REF3","$REF4","$REF5"]}
EOF
echo "state → $STATE"
echo "ALL GREEN — wave 78 providers smoke"

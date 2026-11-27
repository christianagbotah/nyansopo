#!/usr/bin/env bash
# ── wave 75 — Transport management & billing end-to-end smoke ──
# The older scripts/test-fare-engine.sh covers the conductor boarding/wallet
# story; this one covers the manager + money story: RBAC (teacher refused,
# conductor conduct-only), fleet registries with compliance ageing, a route
# with structured per-stop fares, assignment resolving the stop fare, trip
# recording, a cash boarding feeding the fare report, bill-term riding the
# receipt engine (dedupe on the second pass), and collect-fare settling the
# term fare with a numbered receipt. Cleans up after itself.
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
CONDUCTOR=$(curl -s -X POST $BASE/api/auth -H 'content-type: application/json' -d '{"email":"conductor@nyansapo.gh","password":"nyansapo"}' | jqpy "print(d['user']['id'])") || fail "conductor login"
HH="x-user-id: $HEAD"; TH="x-user-id: $TEACHER"; CH="x-user-id: $CONDUCTOR"
echo "head=$HEAD teacher=$TEACHER conductor=$CONDUCTOR"

TAG="SMK-TR-$(date +%s)"
echo "tag: $TAG"

# ── 1 — baseline: routes, fleet, unassigned pickers, summary all ride in one GET
G=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/transport")
BASE_OK=$(echo "$G" | jqpy "print('routes' in d and 'drivers' in d and 'vehicles' in d and 'unassigned' in d and 'summary' in d)")
echo "1. baseline shape ok=$BASE_OK routes=$(echo "$G" | jqpy "print(len(d['routes']))") vehicles=$(echo "$G" | jqpy "print(len(d['vehicles']))") drivers=$(echo "$G" | jqpy "print(len(d['drivers']))")"
[ "$BASE_OK" = "True" ] || fail "GET payload missing core collections"

# ── 2 — RBAC: teacher has no transport perms, conductor may conduct but not manage
CODE=$(curl -s -o /dev/null -w "%{http_code}" -H "$TH" "$BASE/api/school/$SID/ops/transport")
echo "2a. teacher GET → $CODE"
[ "$CODE" = "403" ] || fail "teacher should be refused the transport board ($CODE)"
CG=$(curl -s -H "$CH" "$BASE/api/school/$SID/ops/transport")
COK=$(echo "$CG" | jqpy "print('routes' in d)")
[ "$COK" = "True" ] || fail "conductor should read the transport board"
CCODE=$(curl -s -o /dev/null -w "%{http_code}" -H "$CH" -X POST "$BASE/api/school/$SID/ops/transport" -H 'content-type: application/json' -d '{"action":"save-route","name":"x","driverName":"y"}')
echo "2b. conductor GET ok, save-route → $CCODE"
[ "$CCODE" = "403" ] || fail "conductor must not manage routes ($CCODE)"

# ── 3 — fleet registries: expired insurance and a licence due soon age into compliance badges
VEH=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/transport" -H 'content-type: application/json' -d "{\"action\":\"vehicle-save\",\"regNumber\":\"$TAG GR\",\"model\":\"Toyota Hiace\",\"capacity\":18,\"insuranceExpiry\":\"2025-01-01\",\"roadworthyExpiry\":\"2027-06-30\",\"status\":\"ACTIVE\",\"notes\":\"smoke vehicle\"}")
VID=$(echo "$VEH" | jqpy "print(d['vehicle']['id'])")
DRV=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/transport" -H 'content-type: application/json' -d "{\"action\":\"driver-save\",\"name\":\"$TAG Kwame Mensah\",\"phone\":\"+233241000000\",\"licenseNo\":\"DL-$TAG\",\"licenseExpiry\":\"$(date -d '+20 days' +%F 2>/dev/null || date -v+20d +%F)\",\"status\":\"ACTIVE\"}")
DID=$(echo "$DRV" | jqpy "print(d['driver']['id'])")
COMP=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/transport" | jqpy "
v = [x for x in d['vehicles'] if x['id'] == '$VID'][0]
dr = [x for x in d['drivers'] if x['id'] == '$DID'][0]
print(v['insuranceCompliance'], v['roadworthyCompliance'], dr['licenseCompliance'])")
echo "3. compliance: $COMP (insurance EXPIRED · roadworthy OK · licence DUE_SOON)"
[ "$COMP" = "EXPIRED OK DUE_SOON" ] || fail "compliance ageing wrong ($COMP)"

# ── 4 — route with structured per-stop fares (Madina ₵5/trip, Atomic ₵8/trip)
RT=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/transport" -H 'content-type: application/json' -d "{\"action\":\"save-route\",\"name\":\"$TAG Madina Loop\",\"vehicleReg\":\"$TAG GR\",\"vehicleModel\":\"Toyota Hiace\",\"driverName\":\"$TAG Kwame Mensah\",\"driverPhone\":\"+233241000000\",\"capacity\":18,\"farePerTermCents\":45000,\"farePerTripCents\":500,\"stopRecords\":[{\"name\":\"Madina market\",\"fareCents\":500},{\"name\":\"Atomic junction\",\"fareCents\":800}],\"driverId\":\"$DID\",\"vehicleId\":\"$VID\"}")
RID=$(echo "$RT" | jqpy "print(d['route']['id'])")
echo "4. route $RID saved with 2 priced stops"
[ -n "$RID" ] || fail "route save failed ($RT)"
STOPS=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/transport" | jqpy "
r = [x for x in d['routes'] if x['id'] == '$RID'][0]
print(len(r['stopRecords']), r['stopRecords'][1]['name'], r['stopRecords'][1]['fareCents'], r['linkedDriver']['name'].endswith('Kwame Mensah'), r['linkedVehicle']['regNumber'] == '$TAG GR')")
echo "4b. echoed: $STOPS"
[ "$STOPS" = "2 Atomic junction 800 True True" ] || fail "stop/registry echo wrong ($STOPS)"

# ── 5 — assign a learner to the Atomic stop: their one-way fare resolves from the STOP, not the route
STU=$(export DATABASE_URL; bun -e "
import { PrismaClient } from '@prisma/client';
const db = new PrismaClient();
const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId: '$SID' } }, include: { academicYear: true } });
const assigned = await db.transportAssignment.findMany({ where: { route: { schoolId: '$SID' } }, select: { studentId: true } });
const taken = new Set(assigned.map(a => a.studentId));
const invs = await db.invoice.findMany({ where: { schoolId: '$SID', termId: term.id }, include: { student: true } });
const paid = await db.payment.findMany({ where: { schoolId: '$SID', category: 'TRANSPORT', paidAt: { gte: term.startsAt } }, select: { studentId: true } });
const paidS = new Set(paid.map(p => p.studentId));
const fit = invs.find(i => !taken.has(i.studentId) && !paidS.has(i.studentId));
console.log(fit ? fit.studentId : '');
await db.\$disconnect();
") || fail "student resolver"
[ -n "$STU" ] || fail "no unassigned learner with a current-term invoice and clean transport ledger"
AS=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/transport" -H 'content-type: application/json' -d "{\"action\":\"assign\",\"routeId\":\"$RID\",\"studentId\":\"$STU\",\"pickupStop\":\"Atomic junction\"}")
AID=$(echo "$AS" | jqpy "print(d['assignment']['id'])")
FARE=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/transport" | jqpy "
r = [x for x in d['routes'] if x['id'] == '$RID'][0]
rider = [x for x in r['riders'] if x['studentId'] == '$STU'][0]
print(rider['onewayFare'], rider['stopFare'], r['utilisation'])")
echo "5. assigned $AID — onewayFare/stopFare: $FARE"
[ "$(echo $FARE | cut -d' ' -f1,2)" = "800 800" ] || fail "stop fare did not resolve ($FARE)"

# ── 6 — the conductor records the day's trip (conduct-level perm, recordedByName carried)
TR=$(curl -s -H "$CH" -X POST "$BASE/api/school/$SID/ops/transport" -H 'content-type: application/json' -d "{\"action\":\"record-trip\",\"routeId\":\"$RID\",\"date\":\"$(date +%F)\",\"morningCount\":16,\"eveningCount\":14,\"notes\":\"smoke run\"}")
TRN=$(echo "$TR" | jqpy "print(d['trip']['morningCount'], d['trip']['eveningCount'], d['trip']['recordedByName'] is not None)")
echo "6. trip recorded: $TRN"
[ "$TRN" = "16 14 True" ] || fail "record-trip wrong ($TR)"

# ── 7 — cash boarding at the priced stop: fare = stop fare × 1, settled in cash, charge PAID
BD=$(curl -s -H "$CH" -X POST "$BASE/api/school/$SID/ops/transport" -H 'content-type: application/json' -d "{\"action\":\"boarding-save\",\"assignmentId\":\"$AID\",\"date\":\"$(date +%F)\",\"direction\":\"IN\",\"cashCents\":800}")
BS=$(echo "$BD" | jqpy "print(d['fare']['total'], d['settlement']['paid'], d['settlement']['arrearsAdded'], d['boarding']['direction'], d['charge']['status'] if d['charge'] else 'none')")
echo "7. boarding: $BS (fare paid arrears direction chargeStatus)"
[ "$BS" = "800 800 0 IN PAID" ] || fail "cash boarding wrong ($BS)"

# ── 8 — the fare report sees the boarding for today on this route
FR=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/transport?tab=fares&from=$(date +%F)&to=$(date +%F)&routeId=$RID")
FROW=$(echo "$FR" | jqpy "print(len(d['rows']), d['rows'][0]['fareCents'], d['rows'][0]['direction'], d['stats']['totalFareCents'], d['stats']['outstandingCents'])" 2>/dev/null || echo "shape: $FR")
echo "8. fare report: $FROW"
echo "$FR" | jqpy "exit(0 if len(d['rows']) == 1 and d['rows'][0]['fareCents'] == 800 and d['stats']['totalFareCents'] == 800 and d['stats']['outstandingCents'] == 0 else 1)" || fail "fare report wrong ($FROW)"

# ── 9 — bill-term rides the receipt engine: scoped to the smoke route, line
#         appended to the learner's term invoice, dedupe on rerun
B1=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/transport" -H 'content-type: application/json' -d "{\"action\":\"bill-term\",\"routeId\":\"$RID\"}")
echo "9a. bill-term#1 (routeId-scoped): $(echo "$B1" | jqpy "print('billed', d['billed'], 'skipped', d['skipped'], 'noInvoice', d['noInvoice'])")"
[ "$(echo "$B1" | jqpy "print(d['billed'])")" = "1" ] || fail "scoped bill-term should bill exactly the smoke rider ($B1)"
INVCHK=$(export DATABASE_URL; bun -e "
import { PrismaClient } from '@prisma/client';
const db = new PrismaClient();
const items = await db.invoiceItem.findMany({ where: { name: 'Transportation — $TAG Madina Loop' }, include: { invoice: true } });
const mine = items.filter(i => i.invoice.studentId === '$STU');
if (mine.length === 1) console.log('1', mine[0].amountCents, mine[0].invoice.totalCents); else console.log(mine.length);
await db.\$disconnect();
") || fail "invoice check"
INUM=$(echo "$INVCHK" | cut -d' ' -f1)
echo "9b. invoice line: $INVCHK (count, amount, invoiceTotal)"
[ "$INUM" = "1" ] || fail "expected exactly one transport line ($INVCHK)"
[ "$(echo "$INVCHK" | cut -d' ' -f2)" = "45000" ] || fail "line amount wrong ($INVCHK)"
curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/transport" -H 'content-type: application/json' -d "{\"action\":\"bill-term\",\"routeId\":\"$RID\"}" > /dev/null
INVCHK2=$(export DATABASE_URL; bun -e "
import { PrismaClient } from '@prisma/client';
const db = new PrismaClient();
const items = await db.invoiceItem.findMany({ where: { name: 'Transportation — $TAG Madina Loop' }, include: { invoice: true } });
console.log(items.filter(i => i.invoice.studentId === '$STU').length);
await db.\$disconnect();
")
echo "9c. bill-term#2 dedupe → still $INVCHK2 line(s)"
[ "$INVCHK2" = "1" ] || fail "dedupe failed — double-billed ($INVCHK2)"

# ── 10 — collect-fare settles the term fare at the counter (receipt + SMS), then refuses a second pass
CF=$(curl -s -H "$HH" -X POST "$BASE/api/school/$SID/ops/transport" -H 'content-type: application/json' -d "{\"action\":\"collect-fare\",\"assignmentId\":\"$AID\",\"method\":\"CASH\"}")
CFV=$(echo "$CF" | jqpy "print(d['payment']['amountCents'], d['payment']['category'], d['receipt'] is not None, d['payment']['receiptNo'] is not None)" 2>/dev/null || echo "ERR: $CF")
PMT=$(echo "$CF" | jqpy "print(d['payment']['id'])")
echo "10a. collected: $CFV (amount, category, receipt, receiptNo)"
[ "$CFV" = "45000 TRANSPORT True True" ] || fail "collect-fare wrong ($CFV)"
CF2=$(curl -s -o /dev/null -w "%{http_code}" -H "$HH" -X POST "$BASE/api/school/$SID/ops/transport" -H 'content-type: application/json' -d "{\"action\":\"collect-fare\",\"assignmentId\":\"$AID\",\"method\":\"CASH\"}")
echo "10b. second collect → $CF2"
[ "$CF2" = "400" ] || fail "double collection should be refused ($CF2)"

# ── 11 — the route's money story on the board: revenue quotes the term fare, collected counts the receipt
MON=$(curl -s -H "$HH" "$BASE/api/school/$SID/ops/transport" | jqpy "
r = [x for x in d['routes'] if x['id'] == '$RID'][0]
rider = [x for x in r['riders'] if x['studentId'] == '$STU'][0]
print(r['fareRevenue'], rider['farePaid'], min(rider['farePaid'], 45000) == r['fareCollected'])")
echo "11. revenue/collected: $MON"
[ "$MON" = "45000 45000 True" ] || fail "money view wrong ($MON)"

# ── 12 — clean every smoke row: invoice line reverted, boarding/charge/trip/assignment/route/fleet removed
bun /home/z/my-project/scripts/cleanup-transport-smoke.ts "$TAG" "$PMT" "$STU" || fail "cleanup"
echo "12. smoke rows cleaned"

echo "=== ALL WAVE-75 TRANSPORT SMOKE CHECKS PASSED ==="

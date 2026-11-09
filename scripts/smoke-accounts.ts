/* Double-entry accounts + bank reconciliation (task 3-b) API smoke test —
   (a) RBAC (teacher/cashier 403; bursar 200 + dashboard), (b) chart-of-accounts
   validation (type/duplicate/name/self-parent/cycle/referenced delete),
   (c) journal lifecycle (draft → posted → voided) + trial-balance math,
   (d) reports (trial balance, balance sheet, income statement, general
   ledger, CSV), (e) bank accounts & txn feed, (f) reconciliation flow
   (auto-match, manual match, complete, reconciledAt stamps).
   Creates its own state and CLEANS UP afterwards.
   Run: DATABASE_URL=postgresql://… bun scripts/smoke-accounts.ts */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();
const BASE = "http://localhost:3000";
let pass = 0, fail = 0;

async function check(label: string, cond: boolean, extra = "") {
  if (cond) { pass++; console.log(`  ✅ ${label}`); }
  else { fail++; console.log(`  ❌ ${label} ${extra}`); }
}

async function main() {
  const [bursar, teacher, cashier] = await Promise.all([
    db.user.findUnique({ where: { email: "bursar@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "teacher@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "cashier@nyansapo.gh" } }),
  ]);
  if (!bursar || !teacher || !cashier) { console.log("missing demo users — run the seed first"); return; }
  const school = await db.school.findUnique({ where: { code: "GEA" } });
  if (!school) { console.log("GEA school missing"); return; }
  const schoolId = school.id;
  const API = `/api/school/${schoolId}/ops/accounts`;
  const H = (u: typeof bursar) => ({ "content-type": "application/json", "x-user-id": u.id });
  const get = async (u: typeof bursar, url: string) => fetch(BASE + url, { headers: H(u) });
  const post = async (u: typeof bursar, url: string, body: any) => fetch(BASE + url, { method: "POST", headers: H(u), body: JSON.stringify(body) });
  const J = async (r: Response) => r.json().catch(() => ({} as any));

  const today = new Date().toISOString().slice(0, 10);
  const tomorrow = new Date(Date.now() + 86_400_000).toISOString().slice(0, 10);
  const year = new Date().getUTCFullYear();

  // seeded reference accounts (seed-accounts.ts must have run)
  const cash = await db.chartAccount.findFirst({ where: { schoolId, code: "1000" } });
  const otherIncome = await db.chartAccount.findFirst({ where: { schoolId, code: "4500" } });
  const mainBankAcct = await db.chartAccount.findFirst({ where: { schoolId, code: "1010" } });
  if (!cash || !otherIncome || !mainBankAcct) { console.log("accounts seed missing — run prisma/seed-accounts.ts first"); return; }

  // pre-state snapshots — cleanup restores exactly this
  const preAccountIds = new Set((await db.chartAccount.findMany({ where: { schoolId }, select: { id: true } })).map((r) => r.id));
  const preEntryIds = new Set((await db.journalEntry.findMany({ where: { schoolId }, select: { id: true } })).map((r) => r.id));
  const preBankIds = new Set((await db.bankAccount.findMany({ where: { schoolId }, select: { id: true } })).map((r) => r.id));
  const preTxnIds = new Set((await db.bankTxn.findMany({ where: { schoolId }, select: { id: true } })).map((r) => r.id));
  const preReconIds = new Set((await db.reconciliation.findMany({ where: { schoolId }, select: { id: true } })).map((r) => r.id));

  try {
    /* ── (a) RBAC ── */
    console.log("\n── (a) RBAC ──");
    const tGet = await get(teacher, API);
    check("teacher GET → 403", tGet.status === 403, String(tGet.status));
    const cGet = await get(cashier, API);
    check("cashier GET → 403", cGet.status === 403, String(cGet.status));
    const tPost = await post(teacher, API, { action: "save-account", code: "SMOKE-T", name: "Nope", type: "ASSET" });
    check("teacher POST → 403", tPost.status === 403, String(tPost.status));
    const cPost = await post(cashier, API, { action: "save-journal", entryDate: today, description: "Nope", lines: [] });
    check("cashier POST → 403", cPost.status === 403, String(cPost.status));
    const bGet = await get(bursar, API);
    const payload = await J(bGet);
    check("bursar GET → 200 with ≥25 accounts", bGet.status === 200 && payload.accounts?.length >= 25, `${bGet.status} accounts=${payload.accounts?.length}`);
    const d = payload.dashboard ?? {};
    check("dashboard keys present", ["totalAssetsCents", "totalLiabilitiesCents", "totalEquityCents", "revenueMtdCents", "expensesMtdCents", "netIncomeMtdCents", "unmatchedTxnCount", "bankTotalCents", "openReconCount", "activeAccountCount"].every((k) => typeof d[k] === "number") && typeof d.journalCounts === "object", JSON.stringify(d));
    check("seeded state present (2 bank accounts, ≥1 reconciliation, journals)", payload.bankAccounts?.length >= 2 && payload.reconciliations?.length >= 1 && payload.journals?.length >= 10, JSON.stringify({ banks: payload.bankAccounts?.length, recons: payload.reconciliations?.length, journals: payload.journals?.length }));

    /* ── (b) chart of accounts validation ── */
    console.log("\n── (b) chart of accounts ──");
    const badType = await post(bursar, API, { action: "save-account", code: "SMOKE-BAD", name: "Bad type", type: "WIDGET" });
    check("invalid type → 400", badType.status === 400, String(badType.status));
    const dupCode = await post(bursar, API, { action: "save-account", code: "1000", name: "Duplicate cash", type: "ASSET" });
    check("duplicate code 1000 → 409", dupCode.status === 409, String(dupCode.status));
    const noName = await post(bursar, API, { action: "save-account", code: "SMOKE-NONAME", type: "ASSET" });
    check("missing name → 400", noName.status === 400, String(noName.status));
    const noCode = await post(bursar, API, { action: "save-account", name: "No code", type: "ASSET" });
    check("missing code → 400", noCode.status === 400, String(noCode.status));
    const mk = await post(bursar, API, { action: "save-account", code: "SMOKE-ASSET", name: "Smoke Test Asset", type: "ASSET" });
    const smokeAcctId = (await J(mk)).account?.id as string | undefined;
    check("valid create → 200 + row exists", mk.status === 200 && !!smokeAcctId && !!(await db.chartAccount.findUnique({ where: { id: smokeAcctId } })), String(mk.status));
    const selfParent = await post(bursar, API, { action: "save-account", id: smokeAcctId, code: "SMOKE-ASSET", name: "Smoke Test Asset", type: "ASSET", parentId: smokeAcctId });
    check("account as its own parent → 400", selfParent.status === 400, String(selfParent.status));
    // tree cycle: P ← C, then re-parent P under C
    const mkP = await post(bursar, API, { action: "save-account", code: "SMOKE-PARENT", name: "Smoke Parent", type: "EXPENSE" });
    const pId = (await J(mkP)).account?.id as string | undefined;
    const mkC = await post(bursar, API, { action: "save-account", code: "SMOKE-CHILD", name: "Smoke Child", type: "EXPENSE", parentId: pId });
    const cId = (await J(mkC)).account?.id as string | undefined;
    check("parent + child create → 200", mkP.status === 200 && mkC.status === 200 && !!pId && !!cId, `${mkP.status}/${mkC.status}`);
    const cycle = await post(bursar, API, { action: "save-account", id: pId, code: "SMOKE-PARENT", name: "Smoke Parent", type: "EXPENSE", parentId: cId });
    check("parent under its own child → 400 (cycle guard)", cycle.status === 400, String(cycle.status));
    const delRef = await post(bursar, API, { action: "delete-account", id: cash.id });
    check("delete account referenced by journal lines → 409", delRef.status === 409, String(delRef.status));
    const delBank = await post(bursar, API, { action: "delete-account", id: mainBankAcct.id });
    check("delete account linked to a bank account → 409", delBank.status === 409, String(delBank.status));
    // delete what (b) created — children before parents
    const smokeIds = [smokeAcctId, pId, cId].filter((x): x is string => !!x);
    let delOk = true;
    for (const id of [...smokeIds].reverse()) if ((await post(bursar, API, { action: "delete-account", id })).status !== 200) delOk = false;
    const remain = await db.chartAccount.findMany({ where: { id: { in: smokeIds } }, select: { id: true } });
    check("delete unused smoke accounts → ok", delOk && remain.length === 0, `${remain.length} left`);

    /* ── (c) journal entries ── */
    console.log("\n── (c) journal entries ──");
    const unbal = await post(bursar, API, { action: "save-journal", entryDate: today, description: "SMOKE unbalanced", lines: [{ accountId: cash.id, debitCents: 5000, creditCents: 0 }, { accountId: otherIncome.id, debitCents: 0, creditCents: 4000 }] });
    check("unbalanced lines → 400", unbal.status === 400, String(unbal.status));
    const single = await post(bursar, API, { action: "save-journal", entryDate: today, description: "SMOKE single line", lines: [{ accountId: cash.id, debitCents: 5000, creditCents: 0 }] });
    check("single line → 400", single.status === 400, String(single.status));
    const both = await post(bursar, API, { action: "save-journal", entryDate: today, description: "SMOKE both sides", lines: [{ accountId: cash.id, debitCents: 5000, creditCents: 5000 }, { accountId: otherIncome.id, debitCents: 0, creditCents: 5000 }] });
    check("line with debit AND credit → 400", both.status === 400, String(both.status));

    // sequential entry number — mirror the route's JE-YYYY-#### allocation
    const lastJE = await db.journalEntry.findFirst({ where: { entryNumber: { startsWith: `JE-${year}-` } }, orderBy: { entryNumber: "desc" } });
    const expectNum = `JE-${year}-${String(((lastJE ? parseInt(lastJE.entryNumber.slice(-4), 10) || 0 : 0)) + 1).padStart(4, "0")}`;
    const mkJ1 = await post(bursar, API, { action: "save-journal", entryDate: today, description: "SMOKE journal lifecycle", reference: "SMOKE-J1", lines: [{ accountId: cash.id, debitCents: 5000, creditCents: 0, memo: "in" }, { accountId: otherIncome.id, debitCents: 0, creditCents: 5000, memo: "income" }] });
    const j1 = (await J(mkJ1)).entry;
    check("valid 2-line entry → DRAFT with sequential JE number", mkJ1.status === 200 && j1?.status === "DRAFT" && j1?.entryNumber === expectNum && /^JE-\d{4}-\d{4}$/.test(j1?.entryNumber ?? ""), `${mkJ1.status} ${j1?.entryNumber} expected ${expectNum}`);
    const j1Row = j1?.id ? await db.journalEntry.findUnique({ where: { id: j1.id }, include: { lines: true } }) : null;
    check("draft row + 2 lines persisted", !!j1Row && j1Row.status === "DRAFT" && j1Row.lines.length === 2, JSON.stringify(j1Row?.status));

    const postJ1 = await post(bursar, API, { action: "post-journal", id: j1?.id });
    const postBody = (await J(postJ1)).entry;
    check("post-journal → POSTED + postedAt", postJ1.status === 200 && postBody?.status === "POSTED" && !!postBody?.postedAt, `${postJ1.status} ${postBody?.status}`);
    const rePost = await post(bursar, API, { action: "post-journal", id: j1?.id });
    check("post again → 400", rePost.status === 400, String(rePost.status));
    const editPosted = await post(bursar, API, { action: "save-journal", id: j1?.id, entryDate: today, description: "SMOKE edit posted", lines: [{ accountId: cash.id, debitCents: 6000, creditCents: 0 }, { accountId: otherIncome.id, debitCents: 0, creditCents: 6000 }] });
    check("edit posted entry → 400", editPosted.status === 400, String(editPosted.status));
    const delPosted = await post(bursar, API, { action: "delete-journal", id: j1?.id });
    check("delete posted entry → 400", delPosted.status === 400, String(delPosted.status));

    // trial-balance math: post a distinct ₵77.77 entry, then void it
    const tb0 = await J(await get(bursar, `${API}?report=trial-balance`));
    check("trial balance ΣDr === ΣCr (baseline)", tb0.totalDebitCents === tb0.totalCreditCents && tb0.balanced === true, `${tb0.totalDebitCents}/${tb0.totalCreditCents}`);
    const mkJ2 = await post(bursar, API, { action: "save-journal", entryDate: today, description: "SMOKE TB probe", lines: [{ accountId: cash.id, debitCents: 7777, creditCents: 0 }, { accountId: otherIncome.id, debitCents: 0, creditCents: 7777 }] });
    const j2 = (await J(mkJ2)).entry;
    const postJ2 = await post(bursar, API, { action: "post-journal", id: j2?.id });
    check("TB probe entry created + posted", mkJ2.status === 200 && postJ2.status === 200, `${mkJ2.status}/${postJ2.status}`);
    const tb1 = await J(await get(bursar, `${API}?report=trial-balance`));
    check("trial balance grows by exactly the posted amount on both sides", tb1.totalDebitCents === tb0.totalDebitCents + 7777 && tb1.totalCreditCents === tb0.totalCreditCents + 7777 && tb1.balanced === true, `${tb1.totalDebitCents}/${tb1.totalCreditCents} vs ${tb0.totalDebitCents}/${tb0.totalCreditCents}`);
    const voidJ2 = await post(bursar, API, { action: "void-journal", id: j2?.id });
    check("void-journal → VOIDED", voidJ2.status === 200 && (await J(voidJ2)).entry?.status === "VOIDED", String(voidJ2.status));
    const tb2 = await J(await get(bursar, `${API}?report=trial-balance`));
    check("trial balance EXCLUDES voided lines (back to baseline, still balanced)", tb2.totalDebitCents === tb0.totalDebitCents && tb2.totalCreditCents === tb0.totalCreditCents && tb2.balanced === true, `${tb2.totalDebitCents}/${tb2.totalCreditCents} vs ${tb0.totalDebitCents}/${tb0.totalCreditCents}`);
    const voidJ1 = await post(bursar, API, { action: "void-journal", id: j1?.id });
    check("void posted entry → VOIDED", voidJ1.status === 200 && (await J(voidJ1)).entry?.status === "VOIDED", String(voidJ1.status));

    // fresh draft: voiding is for posted entries, deleting is for drafts
    const mkJ3 = await post(bursar, API, { action: "save-journal", entryDate: today, description: "SMOKE draft delete", lines: [{ accountId: cash.id, debitCents: 1100, creditCents: 0 }, { accountId: otherIncome.id, debitCents: 0, creditCents: 1100 }] });
    const j3 = (await J(mkJ3)).entry;
    const voidDraft = await post(bursar, API, { action: "void-journal", id: j3?.id });
    check("void a DRAFT → 400 (drafts are deleted, not voided)", voidDraft.status === 400, String(voidDraft.status));
    const delJ3 = await post(bursar, API, { action: "delete-journal", id: j3?.id });
    const j3Gone = j3?.id ? !(await db.journalEntry.findUnique({ where: { id: j3.id } })) : false;
    check("delete-journal (fresh draft) → gone", delJ3.status === 200 && j3Gone, String(delJ3.status));

    /* ── (d) reports (my journals are now voided/deleted → seed-only state) ── */
    console.log("\n── (d) reports ──");
    const bs = await J(await get(bursar, `${API}?report=balance-sheet`));
    check("balance sheet balanced (A − L − E − netIncome === 0)", bs.balanced === true && bs.checkCents === 0, JSON.stringify({ balanced: bs.balanced, checkCents: bs.checkCents }));
    const is = await J(await get(bursar, `${API}?report=income-statement&from=2020-01-01&to=${tomorrow}`));
    check("income statement totals + surplus keys", typeof is.totalRevenueCents === "number" && is.totalRevenueCents > 0 && typeof is.totalExpenseCents === "number" && is.totalExpenseCents > 0 && is.surplusCents === is.totalRevenueCents - is.totalExpenseCents, JSON.stringify({ r: is.totalRevenueCents, e: is.totalExpenseCents, s: is.surplusCents }));
    const gl = await J(await get(bursar, `${API}?report=general-ledger&accountId=${cash.id}&from=2020-01-01&to=${tomorrow}`));
    const glDr = (gl.lines ?? []).reduce((s: number, l: any) => s + l.debitCents, 0);
    const glCr = (gl.lines ?? []).reduce((s: number, l: any) => s + l.creditCents, 0);
    check("general ledger for 1000 Cash in Hand: opening 0 over full history + lines", gl.account?.code === "1000" && gl.openingCents === 0 && (gl.lines ?? []).length >= 2, `${gl.account?.code} opening=${gl.openingCents} lines=${(gl.lines ?? []).length}`);
    check("general ledger closing === opening + ΣDr − ΣCr (debit-normal)", gl.closingCents === gl.openingCents + glDr - glCr && gl.debitNormal === true, `${gl.closingCents} vs ${gl.openingCents + glDr - glCr}`);
    check("general ledger last running balance === closing", (gl.lines ?? []).length === 0 || gl.lines[gl.lines.length - 1].runningCents === gl.closingCents, String(gl.lines?.[gl.lines.length - 1]?.runningCents));
    const csvRes = await get(bursar, `${API}?report=trial-balance&format=csv`);
    check("trial-balance CSV → text/csv content type", (csvRes.headers.get("content-type") ?? "").startsWith("text/csv"), csvRes.headers.get("content-type") ?? "");
    const csvText = await csvRes.text();
    check("trial-balance CSV has header + TOTAL row", csvText.split("\n")[0].includes("Code") && csvText.includes("TOTAL"), csvText.split("\n")[0]);

    /* ── (e) bank accounts & txn feed ── */
    console.log("\n── (e) bank accounts & txn feed ──");
    const mkBank = await post(bursar, API, { action: "save-bank-account", name: "SMOKE Bank", bankName: "Smoke Bank Ltd", accountNumber: "SMOKE-0001", chartAccountId: mainBankAcct.id, openingBalanceCents: 500000 });
    const bank = (await J(mkBank)).account;
    check("save-bank-account (linked to seeded isBank account) → created", mkBank.status === 200 && !!bank?.id && !!(await db.bankAccount.findUnique({ where: { id: bank.id } })), String(mkBank.status));
    const badTxn0 = await post(bursar, API, { action: "add-bank-txn", bankAccountId: bank?.id, date: today, direction: "DEPOSIT", amountCents: 0, description: "SMOKE zero" });
    check("add-bank-txn amount 0 → 400", badTxn0.status === 400, String(badTxn0.status));
    const badTxnN = await post(bursar, API, { action: "add-bank-txn", bankAccountId: bank?.id, date: today, direction: "DEPOSIT", amountCents: -500, description: "SMOKE negative" });
    check("add-bank-txn negative amount → 400", badTxnN.status === 400, String(badTxnN.status));
    const mkTxnE = await post(bursar, API, { action: "add-bank-txn", bankAccountId: bank?.id, date: today, direction: "DEPOSIT", amountCents: 12500, description: "SMOKE lodgement", reference: "SMOKE-DEP-1" });
    const txnE = (await J(mkTxnE)).txn;
    check("valid DEPOSIT txn → created", mkTxnE.status === 200 && !!txnE?.id, String(mkTxnE.status));
    const feed1 = await J(await get(bursar, `${API}?bankAccountId=${bank?.id}`));
    const bankRow1 = (feed1.bankAccounts ?? []).find((b: any) => b.id === bank?.id);
    check("bank GET balance = opening + deposit", bankRow1?.currentBalanceCents === 500000 + 12500, JSON.stringify(bankRow1?.currentBalanceCents));
    check("txn feed lists the new txn", (feed1.bankTxns ?? []).some((t: any) => t.id === txnE?.id), String((feed1.bankTxns ?? []).length));
    const delTxnE = await post(bursar, API, { action: "delete-bank-txn", id: txnE?.id });
    const feed2 = await J(await get(bursar, `${API}?bankAccountId=${bank?.id}`));
    const bankRow2 = (feed2.bankAccounts ?? []).find((b: any) => b.id === bank?.id);
    check("delete unreferenced bank-txn → ok, balance reverts", delTxnE.status === 200 && bankRow2?.currentBalanceCents === 500000 && !(feed2.bankTxns ?? []).some((t: any) => t.id === txnE?.id), `${delTxnE.status} bal=${bankRow2?.currentBalanceCents}`);

    /* ── (f) reconciliation flow ── */
    console.log("\n── (f) reconciliation flow ──");
    const t1Res = await post(bursar, API, { action: "add-bank-txn", bankAccountId: bank?.id, date: today, direction: "DEPOSIT", amountCents: 12500, description: "SMOKE fee lodgement", reference: "SMOKE-REC-1" });
    const t1 = (await J(t1Res)).txn;
    check("recon feed txn T1 created", t1Res.status === 200 && !!t1?.id, String(t1Res.status));
    const mkRecon = await post(bursar, API, { action: "create-reconciliation", bankAccountId: bank?.id, statementDate: today, statementEndCents: 504500 });
    const recon = (await J(mkRecon)).reconciliation;
    check("create-reconciliation → IN_PROGRESS", mkRecon.status === 200 && recon?.status === "IN_PROGRESS", `${mkRecon.status} ${recon?.status}`);
    const dupRecon = await post(bursar, API, { action: "create-reconciliation", bankAccountId: bank?.id, statementDate: today, statementEndCents: 504500 });
    check("duplicate open reconciliation → 409", dupRecon.status === 409, String(dupRecon.status));
    const itemARes = await post(bursar, API, { action: "add-statement-item", reconciliationId: recon?.id, date: today, description: "SMOKE lodgement — fee batch", amountCents: 12500 });
    const itemA = (await J(itemARes)).item;
    check("statement item A (matches T1) added unmatched", itemARes.status === 200 && itemA?.matched === false, String(itemARes.status));
    const itemBRes = await post(bursar, API, { action: "add-statement-item", reconciliationId: recon?.id, date: today, description: "SMOKE uncleared cheque", amountCents: -8000 });
    const itemB = (await J(itemBRes)).item;
    check("statement item B (no matching txn yet) added unmatched", itemBRes.status === 200 && itemB?.matched === false, String(itemBRes.status));
    const auto = await post(bursar, API, { action: "auto-match", reconciliationId: recon?.id });
    const autoBody = await J(auto);
    const itemARow = itemA?.id ? await db.reconciliationItem.findUnique({ where: { id: itemA.id } }) : null;
    check("auto-match links exactly the matching line", auto.status === 200 && autoBody.matched === 1 && itemARow?.matched === true && itemARow?.bankTxnId === t1?.id, JSON.stringify(autoBody));
    const compEarly = await post(bursar, API, { action: "complete-reconciliation", reconciliationId: recon?.id });
    check("complete with unmatched remaining → 400", compEarly.status === 400, String(compEarly.status));
    const t2Res = await post(bursar, API, { action: "add-bank-txn", bankAccountId: bank?.id, date: today, direction: "WITHDRAWAL", amountCents: 8000, description: "SMOKE uncleared cheque paid", reference: "SMOKE-REC-2" });
    const t2 = (await J(t2Res)).txn;
    check("second system txn T2 created", t2Res.status === 200 && !!t2?.id, String(t2Res.status));
    const matchB = await post(bursar, API, { action: "match-item", itemId: itemB?.id, bankTxnId: t2?.id });
    const matchBBody = await J(matchB);
    check("match-item links B → T2 (amounts agree)", matchB.status === 200 && matchBBody.item?.matched === true && matchBBody.amountsMatch === true, `${matchB.status} matched=${matchBBody.item?.matched}`);
    const ws = await J(await get(bursar, `${API}?action=recon&id=${recon?.id}`));
    check("recon workspace: expected + difference + system txn feed", ws.expectedCents === 504500 && ws.differenceCents === 0 && (ws.systemTxns ?? []).length === 2 && (ws.systemTxns ?? []).some((t: any) => t.id === t1?.id && t.inUse === true), JSON.stringify({ expected: ws.expectedCents, diff: ws.differenceCents, txns: (ws.systemTxns ?? []).length }));
    const comp = await post(bursar, API, { action: "complete-reconciliation", reconciliationId: recon?.id });
    check("complete-reconciliation → 200", comp.status === 200, String(comp.status));
    const reconRow = recon?.id ? await db.reconciliation.findUnique({ where: { id: recon.id } }) : null;
    check("status COMPLETED + completedAt set", reconRow?.status === "COMPLETED" && !!reconRow?.completedAt, JSON.stringify(reconRow?.status));
    const t1Row = t1?.id ? await db.bankTxn.findUnique({ where: { id: t1.id } }) : null;
    const t2Row = t2?.id ? await db.bankTxn.findUnique({ where: { id: t2.id } }) : null;
    check("linked BankTxns stamped reconciledAt", !!t1Row?.reconciledAt && !!t2Row?.reconciledAt, JSON.stringify({ t1: t1Row?.reconciledAt, t2: t2Row?.reconciledAt }));
    const listAfter = await J(await get(bursar, API));
    const reconListed = (listAfter.reconciliations ?? []).find((r: any) => r.id === recon?.id);
    check("reconciliation list shows completed 2/2 matched", reconListed?.status === "COMPLETED" && reconListed?.itemCount === 2 && reconListed?.matchedCount === 2, JSON.stringify(reconListed));
    const delDone = await post(bursar, API, { action: "delete-reconciliation", id: recon?.id });
    check("delete completed reconciliation → 400 (kept as history)", delDone.status === 400, String(delDone.status));
  } finally {
    /* ── cleanup: remove everything the test created (API where possible,
       Prisma where the API refuses by design) ── */
    console.log("\n── cleanup ──");
    // completed reconciliations are API-undeletable → Prisma (items cascade)
    await db.reconciliation.deleteMany({ where: { schoolId, id: { notIn: [...preReconIds] } } });
    check("test reconciliations removed", (await db.reconciliation.count({ where: { schoolId, id: { notIn: [...preReconIds] } } })) === 0, "");
    // bank txns via the API (Prisma fallback if the dev server died mid-run)
    for (const t of await db.bankTxn.findMany({ where: { schoolId, id: { notIn: [...preTxnIds] } }, select: { id: true } })) {
      let ok = false;
      try { ok = (await post(bursar, API, { action: "delete-bank-txn", id: t.id })).status === 200; } catch { ok = false; }
      if (!ok) await db.bankTxn.delete({ where: { id: t.id } }).catch(() => null);
    }
    check("test bank txns removed", (await db.bankTxn.count({ where: { schoolId, id: { notIn: [...preTxnIds] } } })) === 0, "");
    // bank account via the API (now txn- and recon-free)
    for (const b of await db.bankAccount.findMany({ where: { schoolId, id: { notIn: [...preBankIds] } }, select: { id: true } })) {
      let ok = false;
      try { ok = (await post(bursar, API, { action: "delete-bank-account", id: b.id })).status === 200; } catch { ok = false; }
      if (!ok) await db.bankAccount.delete({ where: { id: b.id } }).catch(() => null);
    }
    check("test bank account removed", (await db.bankAccount.count({ where: { schoolId, id: { notIn: [...preBankIds] } } })) === 0, "");
    // journals: posted/voided are API-undeletable by design → Prisma (lines cascade)
    const delEntries = await db.journalEntry.deleteMany({ where: { schoolId, id: { notIn: [...preEntryIds] } } });
    check("test journal entries removed (lines cascade)", delEntries.count >= 1 && (await db.journalEntry.count({ where: { schoolId, id: { notIn: [...preEntryIds] } } })) === 0, String(delEntries.count));
    // any leftover chart accounts (children before parents)
    const leftoverIds = (await db.chartAccount.findMany({ where: { schoolId, id: { notIn: [...preAccountIds] } }, select: { id: true } })).map((a) => a.id);
    if (leftoverIds.length) {
      await db.chartAccount.deleteMany({ where: { parentId: { in: leftoverIds } } });
      await db.chartAccount.deleteMany({ where: { id: { in: leftoverIds } } });
    }
    check("test chart accounts removed", (await db.chartAccount.count({ where: { schoolId, id: { notIn: [...preAccountIds] } } })) === 0, "");
  }

  const residue = {
    accounts: await db.chartAccount.count({ where: { schoolId, id: { notIn: [...preAccountIds] } } }),
    entries: await db.journalEntry.count({ where: { schoolId, id: { notIn: [...preEntryIds] } } }),
    banks: await db.bankAccount.count({ where: { schoolId, id: { notIn: [...preBankIds] } } }),
    txns: await db.bankTxn.count({ where: { schoolId, id: { notIn: [...preTxnIds] } } }),
    recons: await db.reconciliation.count({ where: { schoolId, id: { notIn: [...preReconIds] } } }),
  };
  check("zero residue — db back to pre-test state", Object.values(residue).every((v) => v === 0), JSON.stringify(residue));

  console.log(`\n${fail === 0 ? "🎉" : "⚠️ "} ${pass} passed, ${fail} failed`);
  if (fail > 0) process.exitCode = 1;
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

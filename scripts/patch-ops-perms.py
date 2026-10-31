#!/usr/bin/env python3
"""Patch ops routes with RBAC per-action permissions."""
import re, sys

BASE = "/home/z/my-project/src/app/api/school/[id]/ops"

# file -> (get_perms, post_perms_expr)
SPEC = {
    "payroll": ('["payroll.view"]', '(action === "save-staff" || action === "create-staff") ? ["staff.manage", "payroll.process"] : ["payroll.process"]'),
    "inventory": ('["inventory.view"]', 'action === "sale" ? ["inventory.pos", "inventory.manage"] : ["inventory.manage"]'),
    "sms": ('["sms.view"]', '["sms.send"]'),
    "admissions": ('["admissions.manage"]', '["admissions.manage"]'),
    "discounts": ('["discounts.manage"]', '["discounts.manage"]'),
    "classes": ('["classes.view"]', '["classes.manage"]'),
    "finance": ('["finance.view"]', 'action === "save-expense" || action === "delete-expense" ? ["finance.manage"] : action === "cashier-report" ? ["cashier.reports"] : action === "terminal-bills" ? ["fees.view", "cashier.reports", "daily.view"] : ["fees.view"]'),
}

for name, (get_perms, post_perms) in SPEC.items():
    path = f"{BASE}/{name}/route.ts"
    with open(path) as f:
        src = f.read()
    orig = src

    # 1. GET perms
    src = src.replace(
        'const auth = await opsAuth(req, schoolId, "GET");',
        f'const auth = await opsAuth(req, schoolId, "GET", {{ perms: {get_perms} }});',
        1,
    )

    # 2. POST restructure — pattern with user destructure
    pat_user = (
        '  const auth = await opsAuth(req, schoolId, "POST");\n'
        '  if (!auth.ok) return auth.res;\n'
        '  const user = auth.user;\n'
        '  const body = await req.json().catch(() => ({}));\n'
        '  const action: string = body.action;\n'
    )
    rep_user = (
        '  const body = await req.json().catch(() => ({}));\n'
        '  const action: string = body.action;\n'
        '  const auth = await opsAuth(req, schoolId, "POST", { perms: ' + post_perms + ' });\n'
        '  if (!auth.ok) return auth.res;\n'
        '  const user = auth.user;\n'
    )
    # pattern without user destructure
    pat_plain = (
        '  const auth = await opsAuth(req, schoolId, "POST");\n'
        '  if (!auth.ok) return auth.res;\n'
        '  const body = await req.json().catch(() => ({}));\n'
        '  const action: string = body.action;\n'
    )
    rep_plain = (
        '  const body = await req.json().catch(() => ({}));\n'
        '  const action: string = body.action;\n'
        '  const auth = await opsAuth(req, schoolId, "POST", { perms: ' + post_perms + ' });\n'
        '  if (!auth.ok) return auth.res;\n'
    )

    if pat_user in src:
        src = src.replace(pat_user, rep_user, 1)
    elif pat_plain in src:
        src = src.replace(pat_plain, rep_plain, 1)
    else:
        print(f"!! {name}: POST pattern not found"); sys.exit(1)

    if src == orig:
        print(f"!! {name}: no change"); sys.exit(1)
    with open(path, "w") as f:
        f.write(src)
    print(f"ok {name}")
print("all patched")

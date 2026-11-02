#!/usr/bin/env python3
"""Build the nyansapo CI3 view-usage report (XLSX + MD) from trace_all_portals.json."""
import json
import os
import re
from collections import defaultdict

from openpyxl import Workbook
from openpyxl.styles import Alignment, Border, Font, PatternFill, Side
from openpyxl.utils import get_column_letter

ROOT = "/home/z/my-project/upload/ci3_full"
OUT = "/home/z/my-project/scripts/ci3-view-trace/out"
DL = "/home/z/my-project/download"
os.makedirs(DL, exist_ok=True)

d = json.load(open(os.path.join(OUT, "trace_all_portals.json")))
adm, tea = d["admin"], d["teacher"]

SHARED_PREFIXES = ("backend/attendance/", "backend/examination/", "backend/modal/",
                   "backend/components/", "backend/shared/", "backend/includes/",
                   "backend/portfolio_assessment/")

def in_admin_scope(v):
    return v.startswith("backend/admin/") or v.startswith(SHARED_PREFIXES)

def in_teacher_scope(v):
    return v.startswith("backend/teacher/") or v.startswith(SHARED_PREFIXES)

# ---------------------------------------------------------------- menu labels
def nav_labels(relpath):
    """Pair each menu URL with its visible label: first get_phrase within a
    260-char window after the URL occurrence (robust for nested <li> menus).
    site_url and navigation passes run separately so one does not consume
    the other's matches (navigation('<?php echo site_url('x')...'))."""
    src = open(os.path.join(ROOT, "application/views", relpath),
               encoding="utf-8", errors="replace").read()
    labels = {}
    matches = []
    for rx in (r"site_url\(\s*['\"]([^'\"]+)['\"]", r"navigation\(\s*['\"]([^'\"]+)['\"]"):
        for m in re.finditer(rx, src):
            matches.append((m.end(), m.group(1)))
    for pos, u in sorted(matches):
        if u in labels or "<?" in u or "$" in u or not u.strip("/"):
            continue
        window = src[pos:pos + 260]
        lm = re.search(r"get_phrase\(\s*'([^']+)'", window)
        if lm:
            labels[u] = lm.group(1)
    return labels

LBL_ADMIN = {**nav_labels("backend/admin/navigation.php"),
             **nav_labels("backend/admin/metro_menu.php"),
             **nav_labels("backend/admin/navigation_cashier.php"),
             **nav_labels("backend/header.php")}
LBL_TEACHER = {**nav_labels("backend/teacher/navigation.php"),
               **nav_labels("backend/header.php")}
def pretty_label(u, table):
    l = table.get(u) or table.get(u.rstrip("/"))
    return (l or "").replace("_", " ").strip()

# ---------------------------------------------------------------- menu maps
NAV_SRC_ADMIN = {
    "sidebar": "Admin sidebar (admin/navigation.php)",
    "metro-menu": "Metro mega menu (admin/metro_menu.php)",
    "cashier-sidebar": "Cashier sidebar (level-4 admin)",
    "header": "Shared header quick links",
    "landing": "Portal landing page",
}
NAV_SRC_TEACHER = {
    "sidebar": "Teacher sidebar (teacher/navigation.php)",
    "header": "Shared header links (many are admin-gated)",
    "landing": "Portal landing page",
}

def menu_rows(portal, label_table, src_names):
    seen = set()
    rows = []
    for e in portal["edges"]:
        if e["depth"] != 1 or e["srckind"] not in src_names:
            continue
        key = (e["srckind"], e["url"])
        if key in seen:
            continue
        seen.add(key)
        views = ", ".join(e.get("views", [])) or "(JSON/AJAX only)"
        rows.append({
            "nav_source": src_names[e["srckind"]],
            "menu_url": e["url"],
            "menu_label": pretty_label(e["url"], label_table),
            "endpoint": f"{e['ctrl']}::{e['method']}",
            "method_exists": "yes" if e["method_exists"] else "MISSING",
            "renders_views": views.replace("backend/", ""),
        })
    def rank(r):
        s = r["nav_source"]
        if "sidebar" in s or "Metro" in s:
            return (0, s, r["menu_url"])
        return (1, s, r["menu_url"])
    rows.sort(key=rank)
    return rows

admin_menu = menu_rows(adm, LBL_ADMIN, NAV_SRC_ADMIN)
teacher_menu = menu_rows(tea, LBL_TEACHER, NAV_SRC_TEACHER)

# ---------------------------------------------------------------- connections
def connection_rows(portal, scope_filter):
    rows = []
    for e in portal["edges"]:
        fv = e["from_view"]
        if not scope_filter(fv):
            continue
        if e["depth"] == 1:
            continue  # menu rows live in the menu sheet
        views = ", ".join(e.get("views", [])) or "(endpoint only)"
        rows.append({
            "page_view": fv,
            "hop": e["depth"],
            "link_kind": e["kind"],
            "target_url": e["url"],
            "endpoint": f"{e['ctrl']}::{e['method']}" if e["ctrl"] else "-",
            "method_exists": "yes" if e["method_exists"] else "MISSING",
            "opens_view": views.replace("backend/", ""),
        })
    rows.sort(key=lambda r: (r["page_view"], r["hop"], r["target_url"]))
    return rows

admin_conn = connection_rows(adm, in_admin_scope)
teacher_conn = connection_rows(tea, in_teacher_scope)

# ---------------------------------------------------------------- used views
def used_view_rows(portal, folder_prefix):
    # reverse: which menu URL/edge first points at each view
    first_ref = {}
    for e in portal["edges"]:
        for v in e.get("views", []):
            if v not in first_ref:
                first_ref[v] = (e["from_view"], e["url"])
    rows = []
    for v, depth in sorted(portal["views"].items()):
        if not v.startswith(folder_prefix) and not v.startswith(SHARED_PREFIXES):
            continue
        if v in NAV_FILES:
            continue  # nav/layout files themselves
        ref, url = first_ref.get(v, ("(nav/layout)", ""))
        rows.append({
            "view_file": v.replace("backend/", ""),
            "scope": "shared" if v.startswith(SHARED_PREFIXES) else folder_prefix.strip("/"),
            "first_reached_from": ref.replace("backend/", ""),
            "via_url": url,
        })
    return rows

NAV_FILES = {"backend/admin/navigation.php", "backend/admin/metro_menu.php",
             "backend/admin/navigation_cashier.php", "backend/teacher/navigation.php",
             "backend/header.php", "backend/main.php", "backend/index.php",
             "backend/include_main.php", "backend/accountant/dashboard.php"}

admin_used = used_view_rows(adm, "backend/admin/")
teacher_used = used_view_rows(tea, "backend/teacher/")

# ---------------------------------------------------------------- unused views
def all_views_under(sub):
    base = os.path.join(ROOT, "application/views/backend", sub)
    out = []
    for dirpath, _, files in os.walk(base):
        for fn in files:
            if fn.endswith(".php"):
                out.append(os.path.relpath(os.path.join(dirpath, fn),
                            os.path.join(ROOT, "application/views")))
    return sorted(out)

reachable_any = set()
for p in d.values():
    reachable_any |= set(p["views"])

# also treat *referenced-from-controllers* as used: any view loaded by ANY
# controller method that is itself reachable in any portal trace
reachable_methods_any = set()
for p in d.values():
    reachable_methods_any |= {m.replace(":", "::") for m in p["methods"]}

modern_pairs = []
for sub in ("admin", "teacher"):
    for v in all_views_under(sub):
        if v.endswith("_modern.php"):
            modern_pairs.append(v)

unused_rows = []
for sub in ("admin", "teacher"):
    for v in all_views_under(sub):
        if v in reachable_any:
            continue
        note = ""
        stem = v[:-4]
        if stem.endswith("_modern"):
            base = stem[:-7]
            note = "modernized variant — base version in use" if (base + ".php") in reachable_any else "modernized variant, not wired"
        else:
            if (stem + "_modern.php") in all_views_under(sub):
                note = "replaced by _modern variant" if (stem + "_modern.php") in reachable_any else ""
        unused_rows.append({
            "view_file": v.replace("backend/", ""),
            "folder": sub,
            "note": note,
        })

# ---------------------------------------------------------------- findings
admin_views_total = len(all_views_under("admin"))
teacher_views_total = len(all_views_under("teacher"))
n_admin_scope = len([v for v in adm["views"] if in_admin_scope(v)])
n_teacher_scope = len([v for v in tea["views"] if in_teacher_scope(v)])

findings = [
    ["Accounts & Bookkeeping module switched OFF in the UI",
     "The entire 'SECTION 2: PROFESSIONAL ACCOUNTS MODULE' block in admin navigation.php "
     "(accounts/dashboard, chart_of_accounts, journal_entries, bank_accounts, budgets, "
     "fiscal_year, all 5 reports, audit_trail) is wrapped in an HTML comment — invisible in "
     "the browser. No other live view links to accounts/*. The controller/models still exist."],
    ["Modernized view pairs — only 3 of 8 modern admin variants are wired",
     "discount_profiles_modern, students_daily_attendance_modern and system_settings_modern "
     "are the live pages (their old counterparts are unused). curriculum_content_standards_modern, "
     "curriculum_strands_modern, curriculum_sub_strands_modern, manage_attendance_modern and "
     "student_bulk_add_modern are NOT reachable from any live menu. teacher/marks_manage still "
     "uses the OLD view (marks_manage_modern unused); teacher navigation_modern.php is dead code."],
    ["3 broken flows: missing view files",
     "user_permissions::module_overview renders backend/admin/user_permissions_module.php "
     "(missing; linked from the User Permissions page). interest_items::create and "
     "conduct_items::create render backend/admin/{interest,conduct}_items/create.php "
     "(missing; linked from their modal forms). conduct_items::edit is equally missing."],
    ["Broken SMS button on students invoice info page",
     "backend/admin/students_invoice_info.php line 119: the Send-SMS dropdown link emits "
     "navigation(http://…/admin/message/sms_send…) — the URL is not quoted in the JS onclick, "
     "so the button throws a SyntaxError and does nothing."],
    ["Teacher portal cross-links into admin pages (permission-gated)",
     "Teacher-reachable shared views link to admin/* URLs (fee_collection portal, inventory, "
     "discount management, user permissions, conduct/interest items). Those pages render only "
     "under backend/admin/ — for a teacher session they would fail permission checks or miss "
     "their views. They belong to the ADMIN migration scope, not the teacher scope."],
]

# ================================================================ write XLSX
wb = Workbook()
wb.remove(wb.active)

HDR_FILL = PatternFill("solid", fgColor="1F4E79")
HDR_FONT = Font(color="FFFFFF", bold=True, size=10)
TITLE_FONT = Font(bold=True, size=13, color="1F4E79")
BORDER = Border(*[Side(style="thin", color="D9D9D9")] * 4)
WARN_FILL = PatternFill("solid", fgColor="FCE4E4")
OK_FILL = PatternFill("solid", fgColor="E2EFDA")

def add_sheet(name, title, headers, rows, widths, wrap_cols=()):
    ws = wb.create_sheet(name)
    ws["A1"] = title
    ws["A1"].font = TITLE_FONT
    ws.append([])
    ws.append(headers)
    for c in range(1, len(headers) + 1):
        cell = ws.cell(row=3, column=c)
        cell.fill = HDR_FILL
        cell.font = HDR_FONT
        cell.border = BORDER
        cell.alignment = Alignment(vertical="center")
    for r in rows:
        ws.append(list(r.values()) if isinstance(r, dict) else list(r))
    for i, w in enumerate(widths, start=1):
        ws.column_dimensions[get_column_letter(i)].width = w
    for row in ws.iter_rows(min_row=4, max_row=ws.max_row):
        for cell in row:
            cell.border = BORDER
            cell.alignment = Alignment(vertical="top",
                                       wrap_text=cell.column_letter in wrap_cols)
            if isinstance(cell.value, str) and "MISSING" in cell.value:
                cell.fill = WARN_FILL
    ws.freeze_panes = "A4"
    return ws

# -- Summary
ws = wb.create_sheet("Summary")
ws["A1"] = "Nyansapo — CI3 view usage trace (admin & teacher portals)"
ws["A1"].font = Font(bold=True, size=14, color="1F4E79")
summary_lines = [
    "",
    f"Trace method: live navigation files (admin sidebar + metro mega menu + cashier sidebar + shared header; "
    f"teacher sidebar) followed page-by-page (links, forms, AJAX, popups, redirects) up to 3 hops from the menu.",
    "",
    "ADMIN PORTAL",
    f"  Menu entries (unique URLs): {len(set(r['menu_url'] for r in admin_menu))}",
    f"  Controller endpoints reachable: {len(adm['methods'])}",
    f"  View files in scope (admin folder + shared): {n_admin_scope} of {admin_views_total} admin-folder files",
    "",
    "TEACHER PORTAL",
    f"  Sidebar menu entries (unique URLs): {len(set(r['menu_url'] for r in teacher_menu if 'navigation.php' in r['nav_source'] or 'Metro' in r['nav_source']))}",
    f"  Controller endpoints reachable: {len(tea['methods'])}",
    f"  View files in scope (teacher folder + shared): {n_teacher_scope} of {teacher_views_total} teacher-folder files",
    "",
    "UNUSED VIEW FILES (not reachable from ANY portal trace)",
    f"  backend/admin:    {len([r for r in unused_rows if r['folder']=='admin'])} of {admin_views_total} files",
    f"  backend/teacher:  {len([r for r in unused_rows if r['folder']=='teacher'])} of {teacher_views_total} files",
    "",
    "KEY FINDINGS",
]
for i, (t, _) in enumerate(findings, start=1):
    summary_lines.append(f"  {i}. {t}")
for r, line in enumerate(summary_lines, start=2):
    ws.cell(row=r, column=1, value=line)
    if line.isupper() and line:
        ws.cell(row=r, column=1).font = Font(bold=True, size=11, color="1F4E79")
ws.column_dimensions["A"].width = 120
row0 = len(summary_lines) + 3
ws.cell(row=row0, column=1, value="FINDING DETAILS").font = Font(bold=True, size=11, color="1F4E79")
r = row0 + 1
for t, detail in findings:
    c = ws.cell(row=r, column=1, value=f"{t}\n\n{detail}")
    c.alignment = Alignment(wrap_text=True, vertical="top")
    c.border = BORDER
    ws.row_dimensions[r].height = 90
    r += 2

add_sheet("Admin Menu Map", "Admin portal — every menu item and the page it opens",
          ["Nav source", "Menu URL", "Menu label", "Controller endpoint",
           "Method exists", "View(s) rendered"],
          admin_menu, [30, 42, 30, 34, 12, 60], wrap_cols=("B", "C", "F"))

add_sheet("Teacher Menu Map", "Teacher portal — sidebar menu items and the pages they open",
          ["Nav source", "Menu URL", "Menu label", "Controller endpoint",
           "Method exists", "View(s) rendered"],
          teacher_menu, [30, 42, 30, 34, 12, 60], wrap_cols=("B", "C", "F"))

add_sheet("Admin Page Connections", "Admin portal — what each page connects to (2nd/3rd hop links, forms, AJAX)",
          ["From page (view)", "Hop", "Kind", "Target URL", "Endpoint", "Method exists", "Opens view(s)"],
          admin_conn, [42, 6, 10, 44, 34, 12, 50], wrap_cols=("A", "D", "G"))

add_sheet("Teacher Page Connections", "Teacher portal — what each page connects to (2nd/3rd hop links, forms, AJAX)",
          ["From page (view)", "Hop", "Kind", "Target URL", "Endpoint", "Method exists", "Opens view(s)"],
          teacher_conn, [42, 6, 10, 44, 34, 12, 50], wrap_cols=("A", "D", "G"))

add_sheet("Admin Used Views", f"Admin portal scope — {len(admin_used)} view files actually in use",
          ["View file", "Scope", "First reached from", "Via URL"],
          admin_used, [52, 14, 46, 40], wrap_cols=("A", "C"))

add_sheet("Teacher Used Views", f"Teacher portal scope — {len(teacher_used)} view files actually in use",
          ["View file", "Scope", "First reached from", "Via URL"],
          teacher_used, [52, 14, 46, 40], wrap_cols=("A", "C"))

add_sheet("Unused Views", "View files NOT reachable from any portal's live navigation (do NOT migrate)",
          ["View file", "Folder", "Note"],
          unused_rows, [60, 12, 46], wrap_cols=("A", "C"))

xlsx_path = os.path.join(DL, "nyansapo_ci3_view_usage.xlsx")
wb.save(xlsx_path)
print("saved", xlsx_path)
print("admin menu rows:", len(admin_menu), "| teacher menu rows:", len(teacher_menu))
print("admin conn:", len(admin_conn), "| teacher conn:", len(teacher_conn))
print("admin used:", len(admin_used), "| teacher used:", len(teacher_used))
print("unused:", len(unused_rows))

# stash computed data for the MD report
json.dump({
    "admin_menu": admin_menu, "teacher_menu": teacher_menu,
    "admin_conn_count": len(admin_conn), "teacher_conn_count": len(teacher_conn),
    "admin_used": admin_used, "teacher_used": teacher_used,
    "unused_rows": unused_rows, "findings": findings,
    "numbers": {
        "admin_methods": len(adm["methods"]), "teacher_methods": len(tea["methods"]),
        "admin_scope_views": n_admin_scope, "teacher_scope_views": n_teacher_scope,
        "admin_total": admin_views_total, "teacher_total": teacher_views_total,
        "admin_unused": len([r for r in unused_rows if r["folder"] == "admin"]),
        "teacher_unused": len([r for r in unused_rows if r["folder"] == "teacher"]),
    },
}, open(os.path.join(OUT, "report_data.json"), "w"), indent=1)

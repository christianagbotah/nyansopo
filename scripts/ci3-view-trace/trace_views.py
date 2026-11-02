#!/usr/bin/env python3
"""
CI3 view-usage tracer for the nyansapo migration scope.

Answers: starting from the LIVE navigation files (admin sidebar + metro menu +
cashier sidebar; teacher sidebar) and the shared header, which controller
methods and which view files are actually reachable, page by page, and which
view files under backend/admin and backend/teacher are never reached.

Outputs JSON graphs + a plain-text report to scripts/ci3-view-trace/out/.
"""
import json
import os
import re
import sys
from collections import defaultdict

ROOT = "/home/z/my-project/upload/ci3_full"
APP = os.path.join(ROOT, "application")
VIEWS = os.path.join(APP, "views")
OUT = "/home/z/my-project/scripts/ci3-view-trace/out"
os.makedirs(OUT, exist_ok=True)

# ----------------------------------------------------------------------------
# 1. PHP preprocessing: strip comments (keep strings), so //echo ... is ignored
# ----------------------------------------------------------------------------

def strip_php_comments(src: str) -> str:
    """Remove PHP comments (//, #, /* */) and HTML comments, keeping strings.
    Also neutralizes <?php //... ?> echo-dead blocks by removing the comment text."""
    out = []
    i = 0
    n = len(src)
    in_php = False          # inside <?php ... ?>
    while i < n:
        if not in_php:
            if src.startswith("<!--", i):
                j = src.find("-->", i + 4)
                i = n if j == -1 else j + 3
                continue
            if src.startswith("<?", i):
                in_php = True
                out.append("<?")
                i += 2
                continue
            out.append(src[i]); i += 1
        else:
            if src.startswith("?>", i):
                in_php = False
                out.append("?>"); i += 2
                continue
            c = src[i]
            if c in ("'", '"'):
                q = c
                out.append(c); i += 1
                while i < n:
                    if src[i] == "\\" and i + 1 < n:
                        out.append(src[i:i+2]); i += 2; continue
                    out.append(src[i])
                    if src[i] == q:
                        i += 1; break
                    i += 1
                continue
            if src.startswith("//", i) or c == "#":
                j = src.find("\n", i)
                # keep the newline
                i = n if j == -1 else j
                continue
            if src.startswith("/*", i):
                j = src.find("*/", i + 2)
                i = n if j == -1 else j + 2
                continue
            out.append(c); i += 1
    return "".join(out)


def read_clean(relpath: str) -> str:
    with open(os.path.join(ROOT, relpath), encoding="utf-8", errors="replace") as f:
        return strip_php_comments(f.read())

# ----------------------------------------------------------------------------
# 2. Function extractor for controllers (string-aware brace matching)
# ----------------------------------------------------------------------------

def extract_functions(src: str):
    """Return {lowercase_name: body_source} for top-level (and nested) functions."""
    funcs = {}
    for m in re.finditer(r"function\s+([A-Za-z0-9_]+)\s*\(", src):
        name = m.group(1)
        # find opening brace
        j = src.find("{", m.end())
        if j == -1:
            continue
        depth = 0
        i = j
        n = len(src)
        while i < n:
            c = src[i]
            if c in ("'", '"'):
                q = c
                i += 1
                while i < n:
                    if src[i] == "\\":
                        i += 2; continue
                    if src[i] == q:
                        i += 1; break
                    i += 1
                continue
            if c == "{":
                depth += 1
            elif c == "}":
                depth -= 1
                if depth == 0:
                    funcs.setdefault(name.lower(), src[j + 1:i])
                    break
            i += 1
    return funcs

# ----------------------------------------------------------------------------
# 3. Controller index + routes
# ----------------------------------------------------------------------------

CONTROLLERS = {}   # lower name -> file relpath
for fn in os.listdir(os.path.join(APP, "controllers")):
    if not fn.endswith(".php"):
        continue
    stem = fn[:-4].lower()
    CONTROLLERS[stem] = "application/controllers/" + fn

CONTROLLER_FUNCS = {}  # relpath -> {lname: body}
_cache = {}

def funcs_of(relpath):
    if relpath not in CONTROLLER_FUNCS:
        CONTROLLER_FUNCS[relpath] = extract_functions(read_clean(relpath))
    return CONTROLLER_FUNCS[relpath]

# routes: pattern -> target
ROUTES = []
with open(os.path.join(APP, "config/routes.php"), encoding="utf-8", errors="replace") as f:
    for line in f:
        m = re.search(r"\$route\['([^']+)'\]\s*=\s*'([^']+)'", line)
        if m and not line.strip().startswith("//"):
            ROUTES.append((m.group(1), m.group(2)))

def route_match(url):
    for pat, target in ROUTES:
        rx = re.escape(pat)
        rx = rx.replace(r"\(:any\)", r"(.+)").replace(r"\(:num\)", r"([0-9]+)")
        m = re.fullmatch(rx, url)
        if m:
            t = target
            for idx, g in enumerate(m.groups() or [], start=1):
                t = t.replace("$" + str(idx), g)
            return t
    return None

# ----------------------------------------------------------------------------
# 4. Link extraction from any cleaned PHP source
# ----------------------------------------------------------------------------

ASSET_RX = re.compile(r"\.(css|js|png|jpe?g|gif|svg|ico|woff2?|ttf|pdf|mp3|mp4|csv|xls|xlsx|docx?|zip|json)(\?|$)|^assets/|^uploads/|^node_modules/|^cdn/", re.I)

TEMPLATE_URL_RX = re.compile(
    r"<\?=?\s*echo\s+(?:base_url|site_url)\(\s*\)\s*;?\s*\?>",
    re.I)

def extract_targets(src, kinds_out=None):
    """Return set of app URLs (non-asset) found in source."""
    # neutralize template-echoed base/site_url so hrefs capture clean paths
    src = TEMPLATE_URL_RX.sub("", src)
    targets = set()
    def add(t, kind):
        if not t:
            return
        t = t.strip()
        if not t or t.startswith(("$", "{", "javascript:", "#", "mailto:", "tel:", "data:", "<?")):
            return
        if "<?" in t or "$" in t:
            return  # dynamic PHP-built URL — unresolvable statically
        if t.startswith("http://") or t.startswith("https://") or t.startswith("//"):
            return
        if ASSET_RX.search(t):
            return
        targets.add(t)
        if kinds_out is not None:
            kinds_out[t] = kind

    patterns = [
        (r"site_url\(\s*['\"]([^'\"]*)['\"]", "link"),
        (r"navigation\(\s*['\"]([^'\"]+)['\"]", "link"),
        (r"window\.open\(\s*['\"]([^'\"]+)['\"]", "popup"),
        (r"location\.href\s*=\s*['\"]([^'\"]+)['\"]", "link"),
        (r"location\s*=\s*['\"]([^'\"]+)['\"]", "link"),
        (r"url\s*:\s*['\"]([^'\"]+)['\"]", "ajax"),
        (r"action=[\"']([^\"']+)[\"']", "form"),
        (r"redirect\(\s*['\"]([^'\"]+)['\"]", "redirect"),
        (r"showAjaxModal\(\s*['\"]([^'\"]+)['\"]", "modal"),
        (r"href=[\"']([^\"']+)[\"']", "link"),
    ]
    VERBS = {"edit", "delete", "view", "add", "update", "save", "cancel",
             "close", "toggle", "create", "new", "show", "hide", "submit"}
    for rx, kind in patterns:
        for m in re.finditer(rx, src):
            t = m.group(1)
            if kind == "form" and "/" not in t and t.lower() in VERBS:
                continue  # DataTables button action attr, not a URL
            if "(" in t or t.startswith("."):
                continue
            add(t, kind)
    return targets

def extract_view_loads(src):
    """Direct $this->load->view('X') targets."""
    out = set()
    for m in re.finditer(r"load->view\(\s*['\"]([^'\"]+)['\"]", src):
        out.add(m.group(1))
    return out

def extract_includes(src, cur_view_dir):
    """Relative include/require inside view files -> resolved view relpaths."""
    out = set()
    for m in re.finditer(r"(?:include|require)(?:_once)?\s*\(?\s*['\"]([^'\"]+\.php)['\"]", src):
        inc = m.group(1)
        if inc.startswith("APPPATH"):
            inc = inc.replace("APPPATH . 'views/", "").replace("' ", "").strip("' ")
        if "$" in inc or inc.startswith("/"):
            continue
        cand = os.path.normpath(os.path.join(cur_view_dir, inc))
        out.append(cand) if isinstance(out, list) else out.add(cand)
    return out

# ----------------------------------------------------------------------------
# 5. URL -> (controller, method) resolution
# ----------------------------------------------------------------------------

def resolve_url(url):
    """Return (controller_lower, method, is_index) or None."""
    url = url.lstrip("/").split("?")[0]
    if url.startswith("index.php/"):
        url = url[len("index.php/"):]
    if not url:
        return None
    r = route_match(url)
    if r:
        url = r
    segs = [s for s in url.split("/") if s and not s.startswith("$")]
    if not segs:
        return None
    # drop obvious params (numeric or long slugs) when >2 segs
    ctrl = segs[0].lower()
    if ctrl not in CONTROLLERS:
        # maybe first seg is a folder (sub-dir controllers)?
        if len(segs) > 1 and (ctrl + "/" + segs[1].lower()) in CONTROLLERS:
            ctrl = ctrl + "/" + segs[1].lower()
            segs = segs[1:]
        else:
            return None
    method = segs[1] if len(segs) > 1 else "index"
    return ctrl, method.lower()

def method_exists(ctrl, method):
    rp = CONTROLLERS.get(ctrl)
    if not rp:
        return False
    return method in funcs_of(rp)

# ----------------------------------------------------------------------------
# 6. page_name -> view file
# ----------------------------------------------------------------------------

LAYOUTS = {"backend/main", "backend/index"}

# role controllers always render under their own folder (include_main resolves
# by the session's account_type, and a role controller's flow implies that role)
ROLE_CONTROLLERS = {
    "admin": "admin", "teacher": "teacher", "student": "student",
    "parent": "parent", "librarian": "librarian", "accountant": "accountant",
}

def render_portal_for(ctrl, session_portal):
    return ROLE_CONTROLLERS.get(ctrl, session_portal)

def resolve_page_views(ctrl, body, portal):
    """From a controller method body, derive the view files it renders for `portal`."""
    views = []
    loads = extract_view_loads(body)
    loads_layout = any(lv in LAYOUTS for lv in loads)
    concrete = [lv for lv in loads if lv not in LAYOUTS
                and not lv.endswith("/") and not lv.endswith("_")]
    # dynamic family loads like 'backend/admin/online_exam_add_' . $type
    families = [lv for lv in loads if lv.endswith("_")]
    if concrete:
        # method directly loads concrete views (AJAX partial / standalone page):
        # page_name is then only a menu-highlight key, not a view to include
        for lv in concrete:
            if not lv.endswith(".php"):
                lv = lv + ".php"
            views.append((lv, "(direct)"))
        return views
    if families:
        # expand dynamic families: 'backend/admin/online_exam_add_' . $type
        import glob as _g
        for fam in families:
            for hit in sorted(_g.glob(os.path.join(VIEWS, fam + "*.php"))):
                rel = os.path.relpath(hit, VIEWS)
                views.append((rel, "(family:" + fam.rsplit("/", 1)[-1] + ")"))
        return views
    if not loads_layout:
        return views
    for m in re.finditer(r"\$page_data\['page_name'\]\s*=\s*['\"]([^'\"]+)['\"]", body):
        pn = m.group(1)
        if pn.endswith("_") or pn.endswith("/"):
            continue  # dynamic suffix concatenation
        v = page_name_to_view(pn, portal)
        if v:
            views.append((v, pn))
    for m in re.finditer(r"\$data\['page_name'\]\s*=\s*['\"]([^'\"]+)['\"]", body):
        pn = m.group(1)
        if pn.endswith("_") or pn.endswith("/"):
            continue
        v = page_name_to_view(pn, portal)
        if v:
            views.append((v, pn))
    return views

def page_name_to_view(pn, portal):
    if pn.startswith("attendance/"):
        return "backend/attendance/" + pn[len("attendance/"):] + ".php"
    if pn.startswith("examination/"):
        return "backend/examination/" + pn[len("examination/"):] + ".php"
    if "/" in pn and pn.split("/")[0] in ("admin", "teacher", "parent", "student",
                                           "accountant", "librarian", "cashier", "hod",
                                           "employee", "conductor"):
        # page_name already carries its role folder -> used as-is
        return "backend/" + pn + ".php"
    return "backend/" + portal + "/" + pn + ".php"

def view_exists(vrel):
    p = os.path.join(VIEWS, vrel)
    return os.path.isfile(p)

# ----------------------------------------------------------------------------
# 7. BFS per portal
# ----------------------------------------------------------------------------

PORTAL_SEEDS = {
    "admin": [
        ("backend/admin/navigation.php", "sidebar"),
        ("backend/admin/metro_menu.php", "metro-menu"),
        ("backend/admin/navigation_cashier.php", "cashier-sidebar"),
        ("backend/header.php", "header"),
    ],
    "teacher": [
        ("backend/teacher/navigation.php", "sidebar"),
        ("backend/header.php", "header"),
    ],
    "student": [("backend/student/navigation.php", "sidebar"), ("backend/header.php", "header")],
    "parent": [("backend/parent/navigation.php", "sidebar"), ("backend/header.php", "header")],
    "librarian": [("backend/librarian/navigation.php", "sidebar"), ("backend/header.php", "header")],
    "accountant": [("backend/header.php", "header"),
                   ("backend/accountant/dashboard.php", "landing")],  # no sidebar file: land on dashboard
}

MAX_DEPTH = 3  # menu -> page -> secondary -> tertiary

def trace_portal(portal):
    edges = []          # dicts: src, srckind, kind, url, ctrl, method, view
    seen_views = {}     # viewrel -> depth
    seen_methods = {}   # (ctrl,method) -> depth
    dead_urls = []      # menu/link targets with no controller or method
    missing_views = []  # (ctrl, method, viewrel) view file absent
    queue = []          # (viewrel, depth)

    for seed, srckind in PORTAL_SEEDS[portal]:
        if not view_exists(seed):
            continue
        seen_views[seed] = 0
        queue.append((seed, 0, srckind))

    qi = 0
    while qi < len(queue):
        vrel, depth, srckind = queue[qi]; qi += 1
        if depth > MAX_DEPTH:
            continue
        vdir = os.path.dirname(vrel)
        src = read_clean("application/views/" + vrel)
        kinds = {}
        urls = extract_targets(src, kinds)

        # includes inside the view
        for inc in extract_includes(src, vdir):
            if "$" in inc:
                continue
            rel = os.path.relpath(inc, VIEWS)
            if view_exists(rel) and rel not in seen_views:
                seen_views[rel] = depth
                queue.append((rel, depth, srckind))

        # direct load->view inside the view
        for lv in extract_view_loads(src):
            if lv in LAYOUTS or "$" in lv or lv.endswith("/"):
                continue
            rel = lv if lv.endswith(".php") else lv + ".php"
            if view_exists(rel) and rel not in seen_views:
                seen_views[rel] = depth
                queue.append((rel, depth, srckind))

        if depth >= MAX_DEPTH:
            continue

        for url in urls:
            res = resolve_url(url)
            if not res:
                if kinds.get(url) in ("link", "popup", "form", "modal"):
                    dead_urls.append({"from_view": vrel, "url": url, "kind": kinds.get(url)})
                continue
            ctrl, method = res
            ok = method_exists(ctrl, method)
            edge = {"from_view": vrel, "srckind": srckind if depth == 0 else "page",
                    "kind": kinds.get(url, "link"), "url": url,
                    "ctrl": ctrl, "method": method, "method_exists": ok, "depth": depth + 1}
            # resolve views for this method
            if ok:
                rp = CONTROLLERS[ctrl]
                body = funcs_of(rp).get(method, "")
                eff_portal = render_portal_for(ctrl, portal)
                pviews = resolve_page_views(ctrl, body, eff_portal)
                edge["views"] = [v for v, _ in pviews]
                edge["render_folder"] = eff_portal
                for v, _ in pviews:
                    if "$" in v:
                        continue
                    if not view_exists(v):
                        missing_views.append({"ctrl": ctrl, "method": method, "view": v})
                    elif v not in seen_views:
                        seen_views[v] = depth + 1
                        queue.append((v, depth + 1, "page"))
            edges.append(edge)
            if ok and (ctrl, method) not in seen_methods:
                seen_methods[(ctrl, method)] = depth + 1

    return {
        "portal": portal,
        "edges": edges,
        "views": seen_views,
        "methods": {f"{c}:{m}": d for (c, m), d in seen_methods.items()},
        "dead_urls": dedupe_by(dead_urls, ("from_view", "url")),
        "missing_views": dedupe_by(missing_views, ("ctrl", "method", "view")),
    }

def dedupe_by(items, keys):
    seen = set()
    out = []
    for it in items:
        k = tuple(it[x] for x in keys)
        if k not in seen:
            seen.add(k)
            out.append(it)
    return out

# ----------------------------------------------------------------------------
# 8. Run + reports
# ----------------------------------------------------------------------------

results = {}
for portal in ("admin", "teacher", "student", "parent", "librarian", "accountant"):
    results[portal] = trace_portal(portal)
    print(f"[{portal}] views={len(results[portal]['views'])} "
          f"methods={len(results[portal]['methods'])} "
          f"edges={len(results[portal]['edges'])} "
          f"dead={len(results[portal]['dead_urls'])} "
          f"missing={len(results[portal]['missing_views'])}")

with open(os.path.join(OUT, "trace_all_portals.json"), "w") as f:
    json.dump(results, f, indent=1)

# unused analysis: union of every portal's reachability for fairness
def all_views_under(sub):
    base = os.path.join(VIEWS, "backend", sub)
    out = []
    for dirpath, _, files in os.walk(base):
        for fn in files:
            if fn.endswith(".php"):
                rel = os.path.relpath(os.path.join(dirpath, fn), VIEWS)
                out.append(rel)
    return sorted(out)

reachable_any = set()
for p, r in results.items():
    reachable_any |= set(r["views"])

report = []
for sub in ("admin", "teacher"):
    files = all_views_under(sub)
    used_admin = set(results["admin"]["views"])
    used_teacher = set(results["teacher"]["views"])
    unused = [v for v in files if v not in reachable_any]
    admin_only = [v for v in files if v in used_admin and v not in used_teacher]
    both = [v for v in files if v in used_admin and v in used_teacher]
    report.append({
        "folder": sub, "total": len(files), "unused_any_portal": unused,
        "unused_count": len(unused),
        "used_by_admin": sorted(used_admin & set(files)),
        "used_by_teacher": sorted(used_teacher & set(files)),
    })

with open(os.path.join(OUT, "unused_views.json"), "w") as f:
    json.dump(report, f, indent=1)

print("\n=== USAGE SUMMARY ===")
for r in report:
    print(f"backend/{r['folder']}: total={r['total']} "
          f"used_by_admin={len(r['used_by_admin'])} used_by_teacher={len(r['used_by_teacher'])} "
          f"UNUSED(any portal)={r['unused_count']}")
print("\nDead menu links per portal:")
for p, r in results.items():
    print(f"  {p}: {len(r['dead_urls'])}")

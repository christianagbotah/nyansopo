#!/usr/bin/env python3
"""Sequential tokenizer: walk navigation.php, track <ul> depth, emit label/href for each <a>."""
import re, sys

path = sys.argv[1]
src = open(path, encoding='utf-8', errors='replace').read()
src = re.sub(r'<!--.*?-->', '', src, flags=re.S)

phrase = re.compile(r"get_phrase\('([^']*)'\)")
siteurl = re.compile(r"site_url\(\s*'([^']*)'\s*\)")
perm = re.compile(r"getAdminPermissions\(\$_level,\s*'([^']*)'\)")
header = re.compile(r'nav-section-header[^>]*>\s*([^<]+?)\s*<')
a_tag = re.compile(r'<a\b[^>]*>(.*?)</a>', re.S)
php_if = re.compile(r'<\?php\s*if\s*\((.*?)\)\s*:\s*\?>', re.S)

# Build event stream: (pos, type, payload)
events = []
for m in header.finditer(src):
    events.append((m.start(), 'H', m.group(1).strip()))
for m in perm.finditer(src):
    events.append((m.start(), 'P', m.group(1)))
for m in php_if.finditer(src):
    cond = ' '.join(m.group(1).split())[:80]
    events.append((m.start(), 'C', cond))
for m in re.finditer(r'<ul[^>]*>', src):
    events.append((m.start(), 'UL+', None))
for m in re.finditer(r'</ul>', src):
    events.append((m.start(), 'UL-', None))
for m in a_tag.finditer(src):
    events.append((m.start(), 'A', m.group(0)))
events.sort(key=lambda e: e[0])

depth = 1
pending_perm = None
pending_conds = []
out = []
seen_headers = set()

def label_of(a_html):
    m = re.search(r'<span[^>]*>(.*?)</span>', a_html, re.S)
    txt = m.group(1) if m else re.sub(r'<[^>]+>', '', a_html)
    ph = phrase.findall(txt)
    if ph:
        return ' '.join(p.replace('_', ' ').strip().title() for p in ph)
    t = re.sub(r'<[^>]+>', '', txt).strip()
    return t[:60]

for pos, typ, payload in events:
    if typ == 'H':
        if payload.upper() not in seen_headers:
            seen_headers.add(payload.upper())
        out.append(f"\n### {payload.upper()}")
    elif typ == 'P':
        pending_perm = payload
    elif typ == 'C':
        pending_conds.append(payload)
    elif typ == 'UL+':
        depth += 1
    elif typ == 'UL-':
        depth -= 1
    elif typ == 'A':
        lbl = label_of(payload)
        if not lbl:
            continue
        href = siteurl.search(payload)
        href_s = href.group(1) if href else ''
        perms = f"  [perm:{pending_perm}]" if pending_perm else ''
        conds = f"  [if:{' | '.join(pending_conds)}]" if pending_conds else ''
        out.append('  ' * (depth - 1) + f"- {lbl}  ->  {href_s}{perms}{conds}")
        pending_perm = None
        pending_conds = []

print('\n'.join(out))

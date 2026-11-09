#!/usr/bin/env python3
"""Cross-reference controller view loads with actual view files."""
import os, re, glob

BASE = '/home/z/my-project/upload/ci3_full/application'

# Collect all view files (relative paths without .php)
view_files = set()
for root, dirs, files in os.walk(os.path.join(BASE, 'views')):
    for f in files:
        if f.endswith('.php'):
            rel = os.path.relpath(os.path.join(root, f), os.path.join(BASE, 'views'))
            view_files.add(rel[:-4])

# Find all load->view calls in controllers
ctrl_dir = os.path.join(BASE, 'controllers')
missing = {}
pattern = re.compile(r"load->view\(\s*['\"]([^'\"]+)['\"]")

for path in glob.glob(ctrl_dir + '/**/*.php', recursive=True):
    if any(x in path for x in ('.bak', '.backup', '_backup', '.broken', '.txt')):
        continue
    try:
        with open(path, 'r', errors='ignore') as fh:
            content = fh.read()
    except Exception:
        continue
    for m in pattern.finditer(content):
        v = m.group(1)
        # skip partials/variables
        if '$' in v or '(' in v:
            continue
        # CI3 accepts view names with or without .php extension
        if v.endswith('.php'):
            v = v[:-4]
        if v not in view_files:
            missing.setdefault(v, set()).add(os.path.basename(path))

print(f"Total view files: {len(view_files)}")
print(f"\n=== MISSING VIEWS ({len(missing)}) referenced by controllers ===")
for v in sorted(missing):
    ctrls = ', '.join(sorted(missing[v]))
    print(f"  {v}  <-  {ctrls}")

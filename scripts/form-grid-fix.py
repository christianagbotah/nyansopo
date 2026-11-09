#!/usr/bin/env python3
"""Responsive form-grid conversion across module dialogs.
- cramped 'grid grid-cols-2 gap-3' (2 cols even on phones) -> 'grid gap-3 sm:grid-cols-2'
- 'space-y-1.5 col-span-2' full-width form fields -> 'space-y-1.5 sm:col-span-2'
  (bare col-span-2 on a 1-col mobile grid would spill into an implicit column)
"""
import re
from pathlib import Path

root = Path("/home/z/my-project/src/components/modules")
changed = {}
for f in root.glob("*.tsx"):
    src = f.read_text()
    out = src.replace('className="grid grid-cols-2 gap-3"', 'className="grid gap-3 sm:grid-cols-2"')
    out = out.replace('className="space-y-1.5 col-span-2"', 'className="space-y-1.5 sm:col-span-2"')
    if out != src:
        f.write_text(out)
        changed[f.name] = (src.count('className="grid grid-cols-2 gap-3"'), src.count('className="space-y-1.5 col-span-2"'))
for name, (a, b) in sorted(changed.items()):
    print(f"{name}: {a} grids, {b} spans")

# report any remaining bare col-span-2 (not sm:/md:/lg: prefixed) for manual review
print("\n-- remaining bare col-span-2 (review) --")
for f in sorted(root.glob("*.tsx")):
    for i, line in enumerate(f.read_text().splitlines(), 1):
        if re.search(r'className="[^"](?<!sm:)(?<!md:)(?<!lg:)col-span-2', line) or re.search(r'(?<=[ "])(?<!sm:)(?<!md:)(?<!lg:)col-span-2[" ]', line):
            print(f"{f.name}:{i}: {line.strip()[:130]}")

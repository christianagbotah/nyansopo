#!/usr/bin/env python3
"""Parametrize concatenated admin/ URLs in admin views to login_type-based."""
import re
import glob

pat = re.compile(r"site_url\('admin/([^'/]+)'\s*\.\s*")

for path in glob.glob('/home/z/my-project/upload/ci3_full/application/views/backend/admin/*.php'):
    src = open(path, 'r', newline='', errors='ignore').read()
    if "site_url('admin/" not in src:
        continue
    matches = pat.findall(src)
    if not matches:
        continue
    repl = lambda m: "site_url($this->session->userdata('login_type') . '/" + m.group(1) + "/' . "
    new = pat.sub(repl, src)
    open(path, 'w', newline='').write(new)
    print(f'{path.split("/")[-1]}: parametrized {len(matches)} concat-URL(s)')

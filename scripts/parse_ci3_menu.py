#!/usr/bin/env python3
"""Parse CI3 navigation.php files into a clean menu tree (parent -> children -> grandchildren)."""
import re, sys, json

def parse_navigation(path):
    with open(path, encoding='utf-8', errors='replace') as f:
        src = f.read()

    # Remove PHP comments and block conditions tracking
    lines = src.split('\n')

    # State: stack of open items
    stack = []  # each: {'depth': n, 'label': str, 'href': str, 'children': []}
    root = {'label': 'ROOT', 'children': [], 'href': ''}
    stack = [(0, root)]  # (indent-level based on <li>/<ul> count)

    ul_depth = 0
    tree_stack = [root]

    li_re = re.compile(r'<li[^>]*>')
    ul_re = re.compile(r'<ul[^>]*>')
    ul_close = re.compile(r'</ul>')
    a_re = re.compile(r'<a[^>]*href="([^"]*)"[^>]*>(.*?)</a>', re.S)
    span_re = re.compile(r'<span[^>]*>(.*?)</span>', re.S)
    phrase_re = re.compile(r"get_phrase\('([^']*)'\)")
    site_url_re = re.compile(r"site_url\('([^']*)'\)")
    header_re = re.compile(r'nav-section-header[^>]*>([^<]*)<')

    def clean_label(m):
        # extract label from <a> inner html
        inner = m
        sp = span_re.search(inner)
        if sp:
            inner = sp.group(1)
        ph = phrase_re.findall(inner)
        if ph:
            return ' '.join(p.replace('_', ' ').title() for p in ph)
        inner = re.sub(r'<[^>]+>', '', inner).strip()
        return inner if inner else '(no label)'

    def extract_href(inner):
        # find site_url in onclick or href
        m = site_url_re.search(inner)
        return m.group(1) if m else ''

    i = 0
    results = []
    for ln in lines:
        # section headers
        h = header_re.search(ln)
        if h:
            tree_stack[-1]['children'].append({'label': '### ' + h.group(1).strip().upper(), 'href': '', 'children': [], 'header': True})
            continue
        # find <li ...> opening
        if li_re.search(ln) and not ul_close.search(ln):
            # gather until </li>
            # look ahead: content of this li (may span lines)
            block = ln
            j = i
            while '</li>' not in block and j + 1 < len(lines) and j - i < 30:
                j += 1
                block += '\n' + lines[j]
            # skip if this <li> contains a nested <ul> directly as submenu container -> it's a parent
            a = a_re.search(block)
            label = clean_label(a.group(2)) if a else '(item)'
            href = extract_href(block)
            node = {'label': label, 'href': href, 'children': [], 'header': False}
            tree_stack[-1]['children'].append(node)
            # if this li contains <ul> then subsequent <li> belong to it until its </ul>
            # crude: count <ul> vs </ul> in block after the <a>
            if '<ul>' in block:
                tree_stack.append(node)
            i = j
        if '</ul>' in ln and len(tree_stack) > 1:
            # pop one level for each </ul>
            for _ in range(ln.count('</ul>')):
                if len(tree_stack) > 1:
                    tree_stack.pop()
        i += 1

    return root

def print_tree(node, depth=0, out=None):
    for c in node['children']:
        prefix = '  ' * depth
        if c.get('header'):
            out.append(f"{prefix}{'='*8} {c['label']} {'='*8}")
        else:
            href = f"  ->  {c['href']}" if c['href'] else ''
            out.append(f"{prefix}- {c['label']}{href}")
        if c['children']:
            print_tree(c, depth + 1, out)

if __name__ == '__main__':
    path = sys.argv[1]
    tree = parse_navigation(path)
    out = []
    print_tree(tree, 0, out)
    print('\n'.join(out))

#!/usr/bin/env python3
"""PHP-aware structural sanity checker (no PHP binary needed).
Only parses code between <?php ... ?> / <?= ... ?> tags, so HTML/CSS/JS braces
are ignored. Checks balanced braces/parens/brackets outside strings/comments.
Usage: php_syntax_check.php file1.php [file2.php ...]
"""
import sys

def php_segments(src):
    """Yield (start_idx, code) for each PHP code segment."""
    i, n = 0, len(src)
    while i < n:
        start = src.find('<?', i)
        if start == -1:
            return
        # find end
        j = start + 2
        if src[start:start+5] == '<?php':
            j = start + 5
        elif src[start:start+3] == '<?=':
            j = start + 3
        end = src.find('?>', j)
        if end == -1:
            yield start, src[start+ (5 if src[start:start+5]=='<?php' else 3):]
            return
        yield start, src[j:end]
        i = end + 2

def check(path):
    with open(path, 'r', encoding='utf-8', errors='replace') as f:
        src = f.read()
    errors = []
    for seg_start, code in php_segments(src):
        # compute line offset of segment start
        line_offset = src[:seg_start].count('\n')
        stack = []
        line = line_offset
        i, n = 0, len(code)
        state = None
        heredoc_tag = ''
        while i < n:
            c = code[i]
            nxt = code[i+1] if i + 1 < n else ''
            if c == '\n':
                line += 1
                if state == 'line_comment':
                    state = None
            if state == 'sq':
                if c == '\\':
                    i += 2
                    continue
                if c == "'":
                    state = None
            elif state == 'dq':
                if c == '\\':
                    i += 2
                    continue
                if c == '"':
                    state = None
            elif state == 'line_comment':
                pass
            elif state == 'block_comment':
                if c == '*' and nxt == '/':
                    state = None
                    i += 2
                    continue
            elif state == 'heredoc':
                if c == '\n':
                    ls = code.rfind('\n', 0, i - 1) + 1
                    stripped = code[ls:i].strip()
                    if stripped == heredoc_tag or stripped == heredoc_tag + ';':
                        state = None
            else:
                if c == "'":
                    state = 'sq'
                elif c == '"':
                    state = 'dq'
                elif c == '/' and nxt == '/':
                    state = 'line_comment'
                    i += 2
                    continue
                elif c == '#':
                    state = 'line_comment'
                elif c == '/' and nxt == '*':
                    state = 'block_comment'
                    i += 2
                    continue
                elif c == '<' and code.startswith('<<<', i):
                    rest = code[i+3:].lstrip()
                    tag = ''
                    for ch in rest:
                        if ch.isalnum() or ch == '_':
                            tag += ch
                        else:
                            break
                    if tag:
                        heredoc_tag = tag
                        state = 'heredoc'
                        i += 3
                        continue
                elif c in '([{':
                    stack.append((c, line))
                elif c in ')]}':
                    if not stack:
                        errors.append(f'line {line+1}: unmatched closing {c}')
                    else:
                        o, ol = stack.pop()
                        if '([{'.index(o) != ')]}'.index(c):
                            errors.append(f'line {line+1}: mismatched {o} (opened line {ol+1}) closed by {c}')
            i += 1
        if state in ('sq', 'dq'):
            errors.append(f'unterminated string near line {line+1}')
        for o, ol in stack:
            errors.append(f'line {ol+1}: unclosed {o}')
    return errors

if __name__ == '__main__':
    rc = 0
    for p in sys.argv[1:]:
        errs = check(p)
        if errs:
            rc = 1
            print(f'FAIL {p}')
            for e in errs[:10]:
                print(f'  {e}')
        else:
            print(f'OK   {p}')
    sys.exit(rc)

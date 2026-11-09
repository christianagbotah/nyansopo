#!/usr/bin/env python3
"""Wrap top-level const/let eval bodies in verify-interactions.sh with IIFEs."""
import re

p = "/home/z/my-project/scripts/verify-interactions.sh"
src = open(p).read()

pattern = re.compile(r'(\$AB eval ")([^"]*?\b(?:const|let) [^"]*?)(")', re.S)

def repl(m):
    return m.group(1) + "(() => { " + m.group(2) + " })()" + m.group(3)

src2, n = pattern.subn(repl, src)
open(p, "w").write(src2)
print("wrapped", n, "evals")

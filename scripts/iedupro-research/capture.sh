#!/bin/bash
# Capture iedupro pages: text content + screenshot
BASE="https://iedupro.lightworldtech.com"
OUT="/home/z/my-project/scripts/iedupro-research/pages"
mkdir -p "$OUT"

capture() {
  local name="$1"
  local url="$2"
  echo "=== Capturing: $name ==="
  agent-browser open "$url" > /dev/null 2>&1
  agent-browser wait --load networkidle > /dev/null 2>&1
  sleep 1.5
  # dump main content text
  agent-browser eval "const m = document.querySelector('#main_page') || document.body; m.innerText.substring(0, 12000)" > "$OUT/$name.txt" 2>&1
  agent-browser screenshot --full "$OUT/$name.png" > /dev/null 2>&1
  echo "saved $OUT/$name.txt ($(wc -c < "$OUT/$name.txt") bytes)"
}

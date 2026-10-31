#!/bin/bash
# Ensure the Nyansapo dev server is up; wait until it responds.
if curl -s -o /dev/null --max-time 3 http://127.0.0.1:3000 2>/dev/null; then
  echo "server already up"
else
  cd /home/z/my-project
  pkill -f "next dev" 2>/dev/null
  pkill -f "next-server" 2>/dev/null
  sleep 1
  rm -f dev.log
  setsid nohup bun run dev > /dev/null 2>&1 < /dev/null &
  for i in $(seq 1 40); do
    if curl -s -o /dev/null --max-time 3 http://127.0.0.1:3000 2>/dev/null; then
      echo "server started"
      exit 0
    fi
    sleep 1
  done
  echo "server failed to start"
  exit 1
fi

#!/bin/bash
# Ensure the production server is up (the sandbox reaps background processes
# between tool calls), then run the given command.
cd /home/z/my-project
export DATABASE_URL='postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public'
if ! curl -s -o /dev/null --max-time 3 http://localhost:3000; then
  pkill -f "standalone/server.js" 2>/dev/null
  sleep 1
  nohup bun run start > /tmp/server.log 2>&1 &
  disown
  for i in $(seq 1 15); do
    sleep 1
    curl -s -o /dev/null --max-time 2 http://localhost:3000 && break
  done
fi
exec "$@"

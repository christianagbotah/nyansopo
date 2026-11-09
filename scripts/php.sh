#!/bin/bash
export LD_LIBRARY_PATH=/tmp/phppkg/root/usr/lib/x86_64-linux-gnu:/tmp/phppkg/root/lib/x86_64-linux-gnu:$LD_LIBRARY_PATH
exec /tmp/phppkg/root/usr/bin/php8.4 "$@"

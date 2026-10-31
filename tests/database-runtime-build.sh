#!/bin/bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")/../.zscripts" && pwd)"
TEST_ROOT="$(mktemp -d)"
trap 'rm -rf "$TEST_ROOT"' EXIT

# PostgreSQL 打包流程测试：
# 1. 项目里存在嵌入式 PostgreSQL（scripts/pg）时，应打包 bin/lib/share/data + start-postgres.sh，
#    且产物 data 目录不含运行时残留（postmaster.pid）。
# 2. 项目里没有 PostgreSQL 时，跳过打包且不报错（生产需外部 DATABASE_URL）。

# ── Case 1: 有 PostgreSQL ──────────────────────────────────────
EXISTING_PROJECT="$TEST_ROOT/existing-project"
EXISTING_BUILD="$TEST_ROOT/existing-build"
mkdir -p "$EXISTING_PROJECT/scripts/pg/bin" \
         "$EXISTING_PROJECT/scripts/pg/lib" \
         "$EXISTING_PROJECT/scripts/pg/share" \
         "$EXISTING_PROJECT/scripts/pg/data" \
         "$EXISTING_PROJECT/scripts/pg/sockets"

printf 'postgres-binary\n' >"$EXISTING_PROJECT/scripts/pg/bin/postgres"
printf 'pg-lib\n' >"$EXISTING_PROJECT/scripts/pg/lib/libpq.so.5"
printf 'pg-share\n' >"$EXISTING_PROJECT/scripts/pg/share/postgresql.conf-sample"
printf 'pg-data\n' >"$EXISTING_PROJECT/scripts/pg/data/PG_VERSION"
printf 'stale-pid\n' >"$EXISTING_PROJECT/scripts/pg/data/postmaster.pid"
printf '#!/bin/bash\necho start\n' >"$EXISTING_PROJECT/scripts/pg/start-postgres.sh"
chmod +x "$EXISTING_PROJECT/scripts/pg/start-postgres.sh"

PROJECT_DIR="$EXISTING_PROJECT" BUILD_DIR="$EXISTING_BUILD" \
    bash "$SCRIPT_DIR/database-runtime-build.sh" >/dev/null

test -f "$EXISTING_BUILD/pg/bin/postgres"
test -f "$EXISTING_BUILD/pg/lib/libpq.so.5"
test -f "$EXISTING_BUILD/pg/share/postgresql.conf-sample"
test -f "$EXISTING_BUILD/pg/data/PG_VERSION"
test -x "$EXISTING_BUILD/pg/start-postgres.sh"
test ! -e "$EXISTING_BUILD/pg/data/postmaster.pid"       # 运行时 pid 不应被打包
test "$(cat "$EXISTING_BUILD/pg/start-postgres.sh")" = "$(cat "$EXISTING_PROJECT/scripts/pg/start-postgres.sh")"
# 源目录不被修改
test -f "$EXISTING_PROJECT/scripts/pg/data/postmaster.pid"

# ── Case 2: 没有 PostgreSQL ────────────────────────────────────
EMPTY_PROJECT="$TEST_ROOT/empty-project"
EMPTY_BUILD="$TEST_ROOT/empty-build"
mkdir -p "$EMPTY_PROJECT"

PROJECT_DIR="$EMPTY_PROJECT" BUILD_DIR="$EMPTY_BUILD" \
    bash "$SCRIPT_DIR/database-runtime-build.sh" >/dev/null

test ! -e "$EMPTY_BUILD/pg"

echo "database runtime build tests passed"

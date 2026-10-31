#!/bin/bash

set -euo pipefail

PROJECT_DIR="${PROJECT_DIR:-/home/z/my-project}"
BUILD_DIR="${BUILD_DIR:?BUILD_DIR is required}"

# ── PostgreSQL (embedded, no root required) ────────────────────
# The app runs on PostgreSQL 17. We bundle the embedded server
# binaries + data directory into the build artifact so production
# boots self-contained (unless an external DATABASE_URL is given).

SOURCE_PG_DIR="$PROJECT_DIR/scripts/pg"
TARGET_PG_DIR="$BUILD_DIR/pg"

if [ -d "$SOURCE_PG_DIR/bin" ] && [ -d "$SOURCE_PG_DIR/lib" ] && [ -d "$SOURCE_PG_DIR/data" ]; then
    echo "🐘 复制 PostgreSQL 17 服务器与数据目录到构建产物..."
    mkdir -p "$TARGET_PG_DIR"
    cp -a "$SOURCE_PG_DIR/bin" "$TARGET_PG_DIR/"
    cp -a "$SOURCE_PG_DIR/lib" "$TARGET_PG_DIR/"
    cp -a "$SOURCE_PG_DIR/share" "$TARGET_PG_DIR/"
    # data 目录单独处理：清掉运行时 pid/sock，保留完整数据库
    mkdir -p "$TARGET_PG_DIR/data" "$TARGET_PG_DIR/sockets"
    # 用 pg_basebackup 不可用时退化为文件复制（需先停库或容忍 crash recovery）
    cp -a "$SOURCE_PG_DIR/data/." "$TARGET_PG_DIR/data/" 2>/dev/null || true
    rm -f "$TARGET_PG_DIR/data/postmaster.pid" 2>/dev/null || true
    rm -rf "$TARGET_PG_DIR/sockets"/* 2>/dev/null || true
    if [ -f "$SOURCE_PG_DIR/start-postgres.sh" ]; then
        cp "$SOURCE_PG_DIR/start-postgres.sh" "$TARGET_PG_DIR/start-postgres.sh"
        chmod +x "$TARGET_PG_DIR/start-postgres.sh"
    fi
    echo "✅ PostgreSQL 已打包进构建产物"
    ls -lah "$TARGET_PG_DIR"
else
    echo "ℹ️  未找到嵌入式 PostgreSQL（scripts/pg），跳过打包。生产环境需通过 DATABASE_URL 指定外部 PostgreSQL。"
fi

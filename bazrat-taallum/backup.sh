#!/usr/bin/env bash
set -Eeuo pipefail

: "${DB_HOST:=127.0.0.1}"
: "${DB_USER:?DB_USER is required}"
: "${DB_PASS:?DB_PASS is required}"
: "${DB_NAME:?DB_NAME is required}"
BACKUP_DIR="${BACKUP_DIR:-./backups}"
STAMP="$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

MYSQL_PWD="$DB_PASS" mysqldump \
  --host="$DB_HOST" \
  --user="$DB_USER" \
  --single-transaction --quick --routines --triggers \
  "$DB_NAME" | gzip > "$BACKUP_DIR/${DB_NAME}_${STAMP}.sql.gz"

tar -czf "$BACKUP_DIR/uploads_${STAMP}.tar.gz" assets/uploads
find "$BACKUP_DIR" -type f -mtime +14 -delete
printf 'Backup completed: %s\n' "$STAMP"

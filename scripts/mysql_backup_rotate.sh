#!/usr/bin/env bash
set -euo pipefail

# Required env vars:
#   DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
# Optional env vars:
#   BACKUP_ROOT (default: ./storage/backups/mysql)
#   DAILY_RETENTION_DAYS (default: 7)
#   WEEKLY_RETENTION_DAYS (default: 14)
#   MONTHLY_RETENTION_DAYS (default: 30)

BACKUP_ROOT="${BACKUP_ROOT:-./storage/backups/mysql}"
DAILY_RETENTION_DAYS="${DAILY_RETENTION_DAYS:-7}"
WEEKLY_RETENTION_DAYS="${WEEKLY_RETENTION_DAYS:-14}"
MONTHLY_RETENTION_DAYS="${MONTHLY_RETENTION_DAYS:-30}"

: "${DB_HOST:?DB_HOST is required}"
: "${DB_PORT:?DB_PORT is required}"
: "${DB_DATABASE:?DB_DATABASE is required}"
: "${DB_USERNAME:?DB_USERNAME is required}"
: "${DB_PASSWORD:?DB_PASSWORD is required}"

DAILY_DIR="${BACKUP_ROOT}/daily"
WEEKLY_DIR="${BACKUP_ROOT}/weekly"
MONTHLY_DIR="${BACKUP_ROOT}/monthly"
mkdir -p "${DAILY_DIR}" "${WEEKLY_DIR}" "${MONTHLY_DIR}"

TIMESTAMP="$(date +%F_%H%M%S)"
DAILY_FILE="${DAILY_DIR}/${DB_DATABASE}_${TIMESTAMP}.sql.gz"

MYSQL_PWD="${DB_PASSWORD}" mysqldump \
  --host="${DB_HOST}" \
  --port="${DB_PORT}" \
  --user="${DB_USERNAME}" \
  --databases "${DB_DATABASE}" \
  --single-transaction \
  --quick \
  --routines \
  --triggers \
  --events \
  | gzip -9 > "${DAILY_FILE}"

# Weekly snapshot: every Sunday
if [ "$(date +%u)" = "7" ]; then
  cp "${DAILY_FILE}" "${WEEKLY_DIR}/week_$(date +%G-%V)_${DB_DATABASE}.sql.gz"
fi

# Monthly snapshot: first day of month
if [ "$(date +%d)" = "01" ]; then
  cp "${DAILY_FILE}" "${MONTHLY_DIR}/month_$(date +%Y-%m)_${DB_DATABASE}.sql.gz"
fi

# Retention cleanup
find "${DAILY_DIR}" -type f -name '*.sql.gz' -mtime +"${DAILY_RETENTION_DAYS}" -delete
find "${WEEKLY_DIR}" -type f -name '*.sql.gz' -mtime +"${WEEKLY_RETENTION_DAYS}" -delete
find "${MONTHLY_DIR}" -type f -name '*.sql.gz' -mtime +"${MONTHLY_RETENTION_DAYS}" -delete

echo "Backup complete: ${DAILY_FILE}"

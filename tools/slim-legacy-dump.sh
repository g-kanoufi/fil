#!/usr/bin/env bash
# Stream-filter a multisite MySQL dump to site 9 + network tables (fast, single pass).
#
# Usage:
#   ./tools/slim-legacy-dump.sh [input.sql] [output.sql.gz]
#
# Default input:  mysql.sql or data/mysql.sql at repo root
# Default output: data/client-site9.sql.gz
#
# Keeps:
#   - all tables matching vnzokz0zw_9_*
#   - vnzokz0zw_users, vnzokz0zw_usermeta
#   - vnzokz0zw_site, vnzokz0zw_sitemeta, vnzokz0zw_blogs, vnzokz0zw_blogmeta
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
INPUT="${1:-}"
OUTPUT="${2:-$ROOT/data/client-site9.sql.gz}"

if [[ -z "$INPUT" ]]; then
  if [[ -f "$ROOT/data/mysql.sql" ]]; then
    INPUT="$ROOT/data/mysql.sql"
  elif [[ -f "$ROOT/mysql.sql" ]]; then
    INPUT="$ROOT/mysql.sql"
  else
    echo "No input dump found. Pass path as first argument." >&2
    exit 1
  fi
fi

if [[ ! -r "$INPUT" ]]; then
  echo "Dump not readable: $INPUT" >&2
  exit 1
fi

mkdir -p "$(dirname "$OUTPUT")"

echo "Input:  $INPUT ($(du -h "$INPUT" | awk '{print $1}'))"
echo "Output: $OUTPUT"

# Copy mysqldump header (through first table comment) + stream table blocks.
HEADER_LINES=24

{
  head -n "$HEADER_LINES" "$INPUT"
  awk '
    function want(name) {
      return name ~ /^vnzokz0zw_9_/ \
        || name == "vnzokz0zw_users" \
        || name == "vnzokz0zw_usermeta" \
        || name == "vnzokz0zw_site" \
        || name == "vnzokz0zw_sitemeta" \
        || name == "vnzokz0zw_blogs" \
        || name == "vnzokz0zw_blogmeta"
    }
    /^DROP TABLE IF EXISTS `/ {
      name = $0
      sub(/^DROP TABLE IF EXISTS `/, "", name)
      sub(/`;$/, "", name)
      keep = want(name) ? 1 : 0
    }
    keep { print }
  ' "$INPUT"
  # mysqldump footer (restore session vars)
  tail -n 8 "$INPUT"
} | gzip -1 > "$OUTPUT"

echo "Done: $(du -h "$OUTPUT" | awk '{print $1}')"
echo "Tables kept:"
gzip -dc "$OUTPUT" | grep -oE 'CREATE TABLE `[^`]+`' | sed 's/CREATE TABLE `//;s/`$//' | wc -l | awk '{print "  " $1 " tables"}'

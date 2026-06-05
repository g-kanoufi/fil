#!/usr/bin/env bash
# Phase 4 — import client legacy dump into FIL (local or staging).
#
# Usage:
#   ./scripts/phase4-import-client.sh [path/to/client-site9.sql.gz]
#
# Env:
#   EXECUTE=1     — run legacy:import --execute (default: dry-run only)
#   FORCE=1       — pass --force on execute (required for staging/production)
#   SKIP_FRESH=1  — skip migrate:fresh (dangerous on shared DBs)
#
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DUMP="${1:-$ROOT/data/client-site9.sql.gz}"
PREFIX="${FIL_LEGACY_TABLE_PREFIX:-vnzokz0zw_9_}"

if [[ ! -r "$DUMP" ]]; then
  echo "Dump not readable: $DUMP" >&2
  echo "Create it: ./tools/slim-legacy-dump.sh mysql.sql data/client-site9.sql.gz" >&2
  exit 1
fi

cd "$ROOT/backend"

export FIL_LEGACY_DUMP_PATH="$DUMP"
export FIL_LEGACY_TABLE_PREFIX="$PREFIX"

echo "==> Dump: $DUMP"
echo "==> Prefix: $PREFIX"

if [[ "${SKIP_FRESH:-}" != "1" ]]; then
  echo "==> migrate:fresh (no demo seed — client data only)"
  php artisan migrate:fresh --force --no-interaction
  echo "==> seed interest regions (US/CA defaults for term sync)"
  php artisan db:seed --class=Database\\Seeders\\InterestRegionSeeder --no-interaction
fi

echo "==> legacy:inventory"
php artisan legacy:inventory "$DUMP" --prefix="$PREFIX"

echo "==> legacy:import-acf"
php artisan legacy:import-acf

echo "==> legacy:sync-interest-region-terms"
php artisan legacy:sync-interest-region-terms "$DUMP" --prefix="$PREFIX" --execute

echo "==> legacy:import-access"
php artisan legacy:import-access --execute

if [[ "${EXECUTE:-}" != "1" ]]; then
  echo "==> legacy:import (dry-run)"
  php artisan legacy:import --prefix="$PREFIX"
else
  echo "==> legacy:import --execute"
  if [[ "${FORCE:-}" == "1" ]]; then
    php artisan legacy:import --prefix="$PREFIX" --execute --force --confirm=legacy-import
  else
    php artisan legacy:import --prefix="$PREFIX" --execute
  fi

  echo "==> legacy:parity-report"
  php artisan legacy:parity-report --prefix="$PREFIX" --samples

  echo "==> legacy:spot-check"
  php artisan legacy:spot-check --samples=10

  echo "==> legacy:finalize"
  php artisan legacy:finalize

  echo "==> mvp:staging-check (local)"
  php artisan mvp:staging-check || true
fi

echo "==> Phase 4 import pipeline finished"

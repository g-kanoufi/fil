#!/usr/bin/env bash
# FIL Forge deploy script — run from repo root on the server.
# Wire this in Forge → Site → Deployment Script (adjust paths if needed).
set -euo pipefail

ROOT="${FORGE_SITE_ROOT:-$(cd "$(dirname "$0")/.." && pwd)}"
NPM="$ROOT/scripts/npm.sh"
cd "$ROOT"

echo "==> FIL deploy @ $(date -u +"%Y-%m-%dT%H:%M:%SZ")"

echo "==> Backend dependencies"
cd backend
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Migrations"
php artisan migrate --force

echo "==> Roles, permissions, UI access"
php artisan legacy:import-access --execute

echo "==> Frontend build"
cd ../frontend
if command -v npm >/dev/null 2>&1; then
  "$NPM" ci --no-audit --no-fund
  "$NPM" run build
else
  echo "WARN: npm not found — skip frontend build (ensure assets committed or build in CI)"
fi

echo "==> Widget build"
cd widget
if command -v npm >/dev/null 2>&1; then
  "$NPM" ci --no-audit --no-fund
  "$NPM" run build
else
  echo "WARN: npm not found — skip widget build"
fi

echo "==> Laravel caches"
cd ../../backend
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Staging gate"
php artisan mvp:staging-check

echo "==> Deploy complete"

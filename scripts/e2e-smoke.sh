#!/usr/bin/env bash
# Run Playwright MVP smoke against a local Laravel server with seeded demo data.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
E2E_DB="$ROOT/backend/database/e2e.sqlite"
BASE_URL="${E2E_BASE_URL:-http://127.0.0.1:8000}"
PORT="${E2E_PORT:-8000}"

cleanup() {
  if [[ -n "${SERVER_PID:-}" ]] && kill -0 "$SERVER_PID" 2>/dev/null; then
    kill "$SERVER_PID" 2>/dev/null || true
    wait "$SERVER_PID" 2>/dev/null || true
  fi
}
trap cleanup EXIT

echo "==> Prepare backend (sqlite + seed)"
cd "$ROOT/backend"
export APP_ENV=local
export APP_KEY="${APP_KEY:-base64:$(openssl rand -base64 32)}"
export DB_CONNECTION=sqlite
export DB_DATABASE="$E2E_DB"
export QUEUE_CONNECTION=sync
export SESSION_DRIVER=database
export CACHE_STORE=file
rm -f "$E2E_DB"
touch "$E2E_DB"
php artisan migrate:fresh --seed --force --no-interaction

echo "==> Build frontend assets"
cd "$ROOT/frontend"
npm run build --silent

echo "==> Start Laravel on :$PORT"
cd "$ROOT/backend"
php artisan serve --host=127.0.0.1 --port="$PORT" --no-reload >/tmp/fil-e2e-serve.log 2>&1 &
SERVER_PID=$!

for _ in $(seq 1 30); do
  if curl -sf "$BASE_URL/api/health" >/dev/null; then
    break
  fi
  sleep 1
done

if ! curl -sf "$BASE_URL/api/health" >/dev/null; then
  echo "Laravel server failed to start. Log:"
  tail -50 /tmp/fil-e2e-serve.log || true
  exit 1
fi

echo "==> Playwright smoke"
cd "$ROOT/e2e"
npm ci --no-audit --no-fund
npx playwright install chromium
E2E_BASE_URL="$BASE_URL" npm test

echo "==> E2E smoke passed"

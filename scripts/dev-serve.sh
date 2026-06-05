#!/usr/bin/env bash
# Local dev server for browser testing (Laravel + built SPA on one origin).
# Uses backend/.env (PostgreSQL). Restart after backend or frontend build changes.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BACKEND="$ROOT/backend"
PID_FILE="${FIL_DEV_PID_FILE:-/tmp/fil-dev-serve.pid}"
LOG_FILE="${FIL_DEV_LOG_FILE:-/tmp/fil-dev-serve.log}"
PORT="${FIL_DEV_PORT:-8000}"
HOST="${FIL_DEV_HOST:-127.0.0.1}"
BASE_URL="http://${HOST}:${PORT}"

usage() {
  cat <<EOF
Usage: $(basename "$0") <start|stop|restart|status>

  start    — stop any stale :${PORT} listener, start \`php artisan serve\`, wait for /api/health
  stop     — stop the managed dev server
  restart  — stop + start (use after PHP or \`npm run build\` changes)
  status   — show PID, health, and log tail hint

Browser: ${BASE_URL}/  (staff)  ${BASE_URL}/portal/login  (prospect)
Logs:    ${LOG_FILE}
EOF
}

stop_server() {
  if [[ -f "$PID_FILE" ]]; then
    local pid
    pid="$(cat "$PID_FILE")"
    if kill -0 "$pid" 2>/dev/null; then
      kill "$pid" 2>/dev/null || true
      wait "$pid" 2>/dev/null || true
    fi
    rm -f "$PID_FILE"
  fi

  if command -v lsof >/dev/null 2>&1; then
    local port_pids
    port_pids="$(lsof -ti :"${PORT}" 2>/dev/null || true)"
    if [[ -n "$port_pids" ]]; then
      echo "==> Stopping other process(es) on :${PORT}: ${port_pids}"
      # shellcheck disable=SC2086
      kill ${port_pids} 2>/dev/null || true
      sleep 0.5
    fi
  fi
}

wait_for_health() {
  local i
  for i in $(seq 1 40); do
    if curl -sf "${BASE_URL}/api/health" >/dev/null 2>&1; then
      return 0
    fi
    sleep 0.25
  done
  return 1
}

ensure_postgres() {
  if [[ -f "$ROOT/docker-compose.yml" ]] && command -v docker >/dev/null 2>&1; then
    if ! docker compose -f "$ROOT/docker-compose.yml" ps postgres 2>/dev/null | grep -qE 'running|Up'; then
      echo "==> Starting Postgres (docker compose)"
      docker compose -f "$ROOT/docker-compose.yml" up -d postgres
    fi
  fi
}

start_server() {
  if curl -sf "${BASE_URL}/api/health" >/dev/null 2>&1; then
    echo "==> Already running at ${BASE_URL}"
    if command -v lsof >/dev/null 2>&1; then
      lsof -ti :"${PORT}" 2>/dev/null | head -1 >"$PID_FILE" || true
    fi
    cmd_status
    return 0
  fi

  stop_server
  ensure_postgres

  if [[ ! -f "$BACKEND/.env" ]]; then
    echo "Missing $BACKEND/.env — copy .env.example and configure DB_* first." >&2
    exit 1
  fi

  if [[ ! -f "$BACKEND/public/fil-assets/.vite/manifest.json" ]]; then
    echo "==> Building frontend (first run)"
    (cd "$ROOT/frontend" && npm run build --silent)
  fi

  echo "==> Starting Laravel dev server at ${BASE_URL}"
  : >"$LOG_FILE"
  # exec keeps one long-lived PHP process (artisan serve otherwise loses the listener on macOS when the shell exits)
  nohup bash -c "cd '$BACKEND' && exec php artisan serve --host='$HOST' --port='$PORT' --no-reload" >>"$LOG_FILE" 2>&1 < /dev/null &
  local launcher_pid=$!
  disown -h "${launcher_pid}" 2>/dev/null || disown "${launcher_pid}" 2>/dev/null || true
  echo "${launcher_pid}" >"$PID_FILE"

  if ! wait_for_health; then
    echo "Dev server failed health check. Last log lines:" >&2
    tail -30 "$LOG_FILE" >&2 || true
    stop_server
    exit 1
  fi

  if command -v lsof >/dev/null 2>&1; then
    local listener_pid
    listener_pid="$(lsof -ti :"${PORT}" 2>/dev/null | head -1 || true)"
    if [[ -n "$listener_pid" ]]; then
      echo "$listener_pid" >"$PID_FILE"
    fi
  fi

  if php "$BACKEND/artisan" list --raw 2>/dev/null | grep -q '^fil:ensure-demo-prospect$'; then
    (cd "$BACKEND" && php artisan fil:ensure-demo-prospect --no-interaction) >/dev/null 2>&1 || true
  fi

  echo "==> Ready: ${BASE_URL}"
  echo "    Staff:    ${BASE_URL}/login  (admin@fil.test / password)"
  echo "    Portal:   ${BASE_URL}/portal/login  (prospect@fil.test / password)"
  echo "    Logs:     ${LOG_FILE}"
}

cmd_status() {
  local pid=""
  if command -v lsof >/dev/null 2>&1; then
    pid="$(lsof -ti :"${PORT}" 2>/dev/null | head -1 || true)"
  fi
  if [[ -z "$pid" && -f "$PID_FILE" ]]; then
    pid="$(cat "$PID_FILE")"
  fi

  if [[ -n "$pid" ]] && kill -0 "$pid" 2>/dev/null; then
    echo "PID ${pid} — ${BASE_URL}"
  else
    echo "Not running on :${PORT}"
  fi

  if curl -sf "${BASE_URL}/api/health" >/dev/null 2>&1; then
    echo "Health: ok"
  else
    echo "Health: unreachable"
  fi
}

main() {
  case "${1:-start}" in
    start) start_server ;;
    stop) stop_server; echo "Stopped." ;;
    restart) start_server ;;
    status) cmd_status ;;
    -h|--help|help) usage ;;
    *)
      usage >&2
      exit 1
      ;;
  esac
}

main "$@"

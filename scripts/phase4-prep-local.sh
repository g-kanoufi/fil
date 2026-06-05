#!/usr/bin/env bash
# Phase 4 prep — everything we can verify without a client SQL dump.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"

echo "==> Pint"
cd "$ROOT/backend"
composer pint:test --quiet

echo "==> Legacy prep (no dump required)"
php artisan legacy:prep

echo "==> Legacy console tests (fixtures)"
php artisan test --compact \
  tests/Feature/Console/LegacyPrepCommandTest.php \
  tests/Feature/Console/LegacySpotCheckTest.php \
  tests/Feature/Console/LegacyImportGuardTest.php \
  tests/Feature/Console/MvpStagingCheckCommandTest.php

echo "==> API smoke tests (areas, UI, grow/earn)"
php artisan test --compact \
  tests/Feature/Api/AreaControllerTest.php \
  tests/Feature/Api/UiAccessTest.php \
  tests/Unit/Services/Stores/StoreStatusMenuServiceTest.php

echo "==> OpenAPI"
php artisan openapi:audit --fail-on-drift

echo "==> Frontend"
cd "$ROOT/frontend"
npm run test:run --silent
npm run build --silent

echo "==> E2E smoke (optional — needs Playwright browsers)"
if [[ "${SKIP_E2E:-}" != "1" ]]; then
  cd "$ROOT"
  ./scripts/e2e-smoke.sh
else
  echo "SKIP_E2E=1 — skipped Playwright"
fi

echo "==> Phase 4 prep local passed"

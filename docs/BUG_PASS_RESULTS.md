# Bug pass results (Zorzees gap review execution)

**Run date:** 2026-06-01  
**Environment:** local PostgreSQL (demo seed) + E2E sqlite server

## Automated baseline

| Command | Result | Notes |
| --- | --- | --- |
| `cd backend && php artisan test --compact` | **400 passed** | Includes `LegacySpotCheckTest`, franchisor closings nav |
| `php artisan openapi:audit --fail-on-drift` | **Pass** | 121 live routes = spec |
| `cd frontend && npm run test:run` | **72 passed** (26 files) | |
| `cd frontend && npm run build` | **Pass** | |

## Legacy import + spot check

| Step | Result | Notes |
| --- | --- | --- |
| `legacy:import --execute` on client dump | **Skipped** | No `data/local.sql.gz` in workspace |
| `legacy:parity-report … --samples` | **Skipped** | Requires dump |
| `legacy:spot-check` | **Pass** | Demo: 4 leads, 2 stores; no staged extras |

On staging after Phase 4:

```bash
php artisan legacy:import --execute --force
php artisan legacy:parity-report /path/to/dump.sql.gz --samples
php artisan legacy:spot-check --leads=10 --stores=5 --fail-on-extras
```

## Staging integration

| Check | Result | Notes |
| --- | --- | --- |
| `php artisan mvp:staging-check` (local) | **Pass with WARN** | `APP_URL` localhost; embed/webhook/CSP checks skipped outside staging |
| `mvp:staging-check` on Forge | **Ops** | Run on server per [NEXT_LOCAL_WORK.md](./NEXT_LOCAL_WORK.md) |
| `./scripts/e2e-smoke.sh` | **20/20 passed** (2026-06-03) | MVP + auth + **Plan 3** (`plan3-platform.spec.ts`); install browsers via `PLAYWRIGHT_BROWSERS_PATH=0 npx playwright install chromium` |
| Mailgun / Twilio / Dwolla+CSP on staging | **Pending ops** | Not verifiable without credentials |

### E2E notes

- Fixed: `expired session` expected heading `Sign in` (not `Staff sign in`).
- Fixed: `product-polish` navigates to `/app/reports/leads/1` instead of grid link by name.
- Re-run: `cd e2e && PLAYWRIGHT_BROWSERS_PATH=0 npx playwright install chromium && cd .. && ./scripts/e2e-smoke.sh`

## Manual checklist ([LOCAL_DEV.md](./LOCAL_DEV.md))

Run on **https://fil.on-forge.com** (or local `php artisan serve`) with franchisor credentials.

| # | Area | Automated coverage | Manual |
| --- | --- | --- | --- |
| 1 | Staff login & navigation | E2E smoke + session API test (closings in nav) | Confirm **Closings** in sidebar |
| 2 | Leads grid | E2E smoke | Facets, bulk FDD |
| 3 | Stores & contacts | Partial E2E | Grid filters |
| 4 | AI assistant | — | Stub reply without AI URL |
| 5 | Widget | E2E widget demo (1 fail) | Submit → Mailhog |
| 6 | Mailhog | — | Local only |
| 7 | Lead composer & activity | — | |
| 8 | Bulk FDD | — | |

## Role matrix

| Role | API / E2E | Notes |
| --- | --- | --- |
| franchisor | Session nav includes `closings` | `StaffAccessTest` |
| area_rep | E2E territory leads | Passed |
| lead_owner | — | Manual |
| franchisee | E2E store view | Passed |
| prospect | E2E blocked login | Passed |

## Findings (bugs to triage)

| ID | Severity | Area | Description |
| --- | --- | --- | --- |
| E2E-1 | Low | e2e | `product-polish` expects lead "Jane Smith" not in sqlite seed |
| E2E-2 | Low | e2e | Expired session redirect test flaky or broken |

## v1 scope

[V1_SCOPE_DECISIONS.md](./V1_SCOPE_DECISIONS.md) — prospect **long-form portal post-v1** until client confirms.

## Optional stretch status

| Item | Status |
| --- | --- |
| Closings in nav | **Done** — [fil.php](../backend/config/fil.php) |
| `legacy:import-stream` | Not implemented |
| ACH reconciliation UI | Post-v1 |
| PII export bundle | Post-v1 |

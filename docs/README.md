# FIL documentation

Single index for product, engineering, and operations docs. **Roadmap to production:** start with [`PRODUCTION_READINESS.md`](./PRODUCTION_READINESS.md).

## Start here

| Doc | Purpose |
| --- | --- |
| [**PRODUCTION_READINESS.md**](./PRODUCTION_READINESS.md) | Master roadmap, progress %, phases 0–8, next actions |
| [**AGENTS.md**](../AGENTS.md) | Agent/coding conventions (repo root) |
| [**CLAUDE.md**](../CLAUDE.md) | Claude Code entry point (imports AGENTS.md + `.claude/rules/`) |
| [**NEXT_LOCAL_WORK.md**](./NEXT_LOCAL_WORK.md) | Local-only task queue (no Forge / API keys) |

## Operations & deploy

| Doc | Purpose |
| --- | --- |
| [LOCAL_DEV.md](./LOCAL_DEV.md) | Local setup, demo logins, browser test checklist |
| [MVP_DEPLOY.md](./MVP_DEPLOY.md) | Ship gate, Forge runbook, staging env template, CSP validation |
| [../backend/.env.staging.example](../backend/.env.staging.example) | Forge staging `.env` template (maps to `mvp:staging-check`) |
| [../scripts/forge-deploy.sh](../scripts/forge-deploy.sh) | Forge deployment script (migrate, build, staging check) |
| [../scripts/e2e-smoke.sh](../scripts/e2e-smoke.sh) | Playwright MVP smoke (login → leads grid) |
| [../e2e/](../e2e/) | Playwright test specs |
| [DEPLOYMENT.md](./DEPLOYMENT.md) | Minimal stack philosophy (one VPS, no Redis/ES by default) |

## Product & parity

| Doc | Purpose |
| --- | --- |
| [parity-checklist.md](./parity-checklist.md) | Legacy CRM parity IDs (P-001 …) |
| [ACTIVITY_HISTORY.md](./ACTIVITY_HISTORY.md) | Activity feed design (Phases A–C complete) |
| [schema-mapping.md](./schema-mapping.md) | Legacy → FIL table mapping |
| [LEGACY_IMPORT_DRY_RUN.md](./LEGACY_IMPORT_DRY_RUN.md) | Phase 4 import dry-run + execute checklist |

## Engineering reference

| Doc | Purpose |
| --- | --- |
| [DESIGN.md](./DESIGN.md) | Color system, light-mode rules, UI polish roadmap |
| [STACK.md](./STACK.md) | PHP, Laravel, Node, React versions |
| [NAMING.md](./NAMING.md) | FIL naming (no legacy CMS terms) |
| [AUTH.md](./AUTH.md) | Roles, permissions, Sanctum SPA auth |
| [ACCESS.md](./ACCESS.md) | UI restrictions, field rules, nav catalog |
| [METADATA.md](./METADATA.md) | Typed columns + `field_values` (no EAV) |
| [SEARCH.md](./SEARCH.md) | Grid filters, AI search proxy |

## Security & compliance

| Doc | Purpose |
| --- | --- |
| [SECURITY_AUDIT.md](./SECURITY_AUDIT.md) | SEC-001…025 findings + verified remediation status |
| [SECRETS_ROTATION.md](./SECRETS_ROTATION.md) | Credential inventory + rotation runbook |
| [PII_RETENTION.md](./PII_RETENTION.md) | PII inventory, retention schedule, SAR/erasure |

## API

| Doc | Purpose |
| --- | --- |
| [api.openapi.yaml](./api.openapi.yaml) | OpenAPI 3 spec for `/api/v1` |
| [PEST_STANDARD.md](./PEST_STANDARD.md) | Pest 4 testing standard + conventions for backend tests |

Route-drift check: `php artisan openapi:audit --fail-on-drift` (CI gate).

## Doc maintenance

- **One source of truth for “what’s next”:** update `PRODUCTION_READINESS.md` when phases complete.
- **Deploy runbooks** live in `MVP_DEPLOY.md`; architecture constraints in `DEPLOYMENT.md`.
- Cross-link with relative paths (`./OTHER.md`) from this folder.

# FIL documentation

Single index for product, engineering, and operations docs.

**Roadmap:** [`PRODUCTION_READINESS.md`](./PRODUCTION_READINESS.md) is the **only** doc to update when phases complete.

## Start here

| Doc | Purpose |
| --- | --- |
| [**PRODUCTION_READINESS.md**](./PRODUCTION_READINESS.md) | Master roadmap, progress %, phases 0–8, next actions |
| [**FORGE_STAGING_CHECKLIST.md**](./FORGE_STAGING_CHECKLIST.md) | Step-by-step Forge staging + services setup |
| [**STAGING.example.md**](./STAGING.example.md) | Staging URL/path template → copy to `STAGING.local.md` (gitignored) |
| [**NEXT_LOCAL_WORK.md**](./NEXT_LOCAL_WORK.md) | Staging leftovers + blocked items |
| [**AGENTS.md**](../AGENTS.md) | Agent/coding conventions (repo root) |
| [**CLAUDE.md**](../CLAUDE.md) | Claude Code entry point |

## Operations & deploy

| Doc | Purpose |
| --- | --- |
| [LOCAL_DEV.md](./LOCAL_DEV.md) | Local setup, demo logins, browser test checklist |
| [MVP_DEPLOY.md](./MVP_DEPLOY.md) | Ship gate, Forge runbook, CSP validation |
| [../backend/.env.staging.example](../backend/.env.staging.example) | Forge staging `.env` template |
| [../scripts/forge-deploy.sh](../scripts/forge-deploy.sh) | Forge deployment script |
| [../scripts/e2e-smoke.sh](../scripts/e2e-smoke.sh) | Playwright MVP smoke |
| [DEPLOYMENT.md](./DEPLOYMENT.md) | Minimal stack philosophy |

## Product & parity

| Doc | Purpose |
| --- | --- |
| [parity-checklist.md](./parity-checklist.md) | Legacy CRM parity IDs |
| [PLAN_3_STAGE_PLATFORM.md](./PLAN_3_STAGE_PLATFORM.md) | Stage-complete platform roadmap |
| [ZORZEES_GAP_REVIEW.md](./ZORZEES_GAP_REVIEW.md) | Zorzees → FIL gap summary |
| [V1_SCOPE_DECISIONS.md](./V1_SCOPE_DECISIONS.md) | v1 vs post-v1 scope (client sign-off) |
| [ACTIVITY_HISTORY.md](./ACTIVITY_HISTORY.md) | Activity feed design |
| [schema-mapping.md](./schema-mapping.md) | Legacy → FIL table mapping |
| [LEGACY_IMPORT_DRY_RUN.md](./LEGACY_IMPORT_DRY_RUN.md) | Phase 4 import checklist |

## Engineering reference

| Doc | Purpose |
| --- | --- |
| [DESIGN.md](./DESIGN.md) | Color system and UI tokens |
| [STACK.md](./STACK.md) | PHP, Laravel, Node, React versions |
| [NAMING.md](./NAMING.md) | FIL naming |
| [AUTH.md](./AUTH.md) | Roles, permissions, Sanctum SPA auth |
| [ACCESS.md](./ACCESS.md) | UI restrictions, field rules, nav catalog |
| [METADATA.md](./METADATA.md) | Typed columns + `field_values` |
| [SEARCH.md](./SEARCH.md) | Grid filters, AI search proxy |

## Security & compliance

| Doc | Purpose |
| --- | --- |
| [SECURITY_AUDIT.md](./SECURITY_AUDIT.md) | SEC status table + live-money checklist |
| [SECRETS_ROTATION.md](./SECRETS_ROTATION.md) | Credential rotation runbook |
| [PII_RETENTION.md](./PII_RETENTION.md) | PII inventory and retention |

## API

| Doc | Purpose |
| --- | --- |
| [api.openapi.yaml](./api.openapi.yaml) | OpenAPI 3 spec for `/api/v1` |
| [PEST_STANDARD.md](./PEST_STANDARD.md) | Pest 4 testing standard |

Route-drift check: `php artisan openapi:audit --fail-on-drift` (CI gate).

## Doc maintenance

- Update **`PRODUCTION_READINESS.md`** when a phase completes — not scattered checklists elsewhere.
- Staging URLs for agents: **`docs/STAGING.local.md`** (gitignored; copy from `STAGING.example.md`).
- Deploy runbooks: **`MVP_DEPLOY.md`** + **`FORGE_STAGING_CHECKLIST.md`**.
- Architecture constraints: **`DEPLOYMENT.md`**.

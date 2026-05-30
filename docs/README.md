# FIL documentation

Single index for product, engineering, and operations docs. **Roadmap to production:** start with [`PRODUCTION_READINESS.md`](./PRODUCTION_READINESS.md).

## Start here

| Doc | Purpose |
| --- | --- |
| [**PRODUCTION_READINESS.md**](./PRODUCTION_READINESS.md) | Master roadmap, progress %, phases 0–8, next actions |
| [**MVP_STATUS.md**](./MVP_STATUS.md) | Short snapshot of what ships today vs deferred |
| [**AGENTS.md**](../AGENTS.md) | Agent/coding conventions (repo root) |
| [**CLAUDE.md**](../CLAUDE.md) | Claude Code entry point (imports AGENTS.md + `.claude/rules/`) |

## Operations & deploy

| Doc | Purpose |
| --- | --- |
| [LOCAL_DEV.md](./LOCAL_DEV.md) | Local setup, demo logins, browser test checklist |
| [MVP_DEPLOY.md](./MVP_DEPLOY.md) | Ship gate, Forge runbook, cutover, smoke tests |
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

## API

| Doc | Purpose |
| --- | --- |
| [api.openapi.yaml](./api.openapi.yaml) | OpenAPI 3 spec for `/api/v1` |

## Doc maintenance

- **One source of truth for “what’s next”:** update `PRODUCTION_READINESS.md` when phases complete.
- **MVP_STATUS.md** is a brief mirror — don’t duplicate long checklists there.
- **Deploy runbooks** live in `MVP_DEPLOY.md`; architecture constraints in `DEPLOYMENT.md`.
- Cross-link with relative paths (`./OTHER.md`) from this folder.

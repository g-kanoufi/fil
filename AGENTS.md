# FIL — Agent Guide

FIL (Franchise Intelligence Layer) is a per-client Laravel + React franchise CRM.

## Migration reference (behavior only)

- PrimeIV migration dump: `data/local.sql.gz` (table prefix `vnzokz0zw_9_`)
- Bundled field schema: `backend/resources/legacy-acf/`

Use **FIL naming** in all product code. See `docs/NAMING.md`.

## Principles

1. **DRY / KISS** — search existing services and components before adding new ones.
2. **TDD** — failing test first; backend PHPUnit, frontend Vitest.
3. **OpenAPI-first** — update `docs/api.openapi.yaml` before API changes.
4. **Single tenant** — no multisite concepts in runtime code.
5. **Structured metadata** — typed columns + `field_values`; no EAV key-value blobs (see `docs/METADATA.md`).
6. **Minimal scope** — only features listed in the plan; reference legacy behavior for parity, not for structure.
7. **Chunk gate** — simplify, de-duplicate, test green before the next iteration (see `.cursor/rules/fil-core.mdc`).

## Coding style

- **Backend:** Jeffrey Way / Laracasts Laravel — Actions, Form Requests, Resources, Policies, feature tests. See `.cursor/rules/fil-backend.mdc`.
- **Frontend:** Wes Bos / modern React — hooks, typed API layer, small components, Vitest. See `.cursor/rules/fil-frontend.mdc`.

## Repository layout

```
backend/     Laravel 13 API (PHP 8.4)
frontend/    @fil/app staff SPA — React 19, Vite 8
frontend/widget/  @fil/widget embeddable lead form
tools/       dump inventory, migration helpers
docs/        schema, API, parity checklist, METADATA.md, STACK.md
```

## Verification commands

```bash
# Backend
cd backend && php artisan test --compact

# Frontend (when present)
cd frontend && npm run test:run

# Legacy dump inventory
php tools/inventory-dump.php data/local.sql.gz
```

## Browser testing

Documentation index: **`docs/README.md`**. Production roadmap: **`docs/PRODUCTION_READINESS.md`**.

After frontend or auth changes, follow **`docs/LOCAL_DEV.md`**. MVP deploy checklist: **`docs/MVP_DEPLOY.md`**.

## Scope (in)

Pipeline/leads, FDD, drips (Laravel queues on **database** driver), PDF, fast grids (**PostgreSQL query API**, no Elasticsearch), stores, royalties, ACH, POS, embed widget, AI assistant proxy.

**Staff SPA:** logged-in only; role + permission gates on web, API, and routes. See `docs/AUTH.md`.

## Deployment (minimal)

**One VPS per client** (Forge): PHP + Nginx + PostgreSQL + queue worker on the same box. No Redis, no Elasticsearch, no separate object store unless required. See `docs/DEPLOYMENT.md`.

## Scope (out)

LearnDash, Amity community, ticketing, SaaS billing UI, legacy CMS runtime.

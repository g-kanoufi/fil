# FIL naming conventions

Use these in all new code. Legacy import identifiers appear only in migration tools.

## API

| Purpose | Path |
| -------- | ----- |
| Authenticated API | `/api/v1/*` |
| Public embed API | `/api/public/v1/*` |
| Webhooks | `/api/webhooks/{provider}` |

### Resources (REST)

- `leads`, `stores`, `areas`, `organizations`, `closings`
- `fdds`, `fdd-deliveries`
- `documents`, `communications`
- `drip-campaigns`, `notification-rules`
- `royalties`, `ach/*`
- `query/{resource}` (POST) — grid search (`leads`, `stores`, `contacts`); replaces legacy ES proxy
- `session` — `GET /api/v1/session`, `POST /api/v1/session` (login)

Do not expose legacy CMS API paths (`wp/v2`, vendor-specific REST namespaces, etc.).

## PHP

- Models: `App\Models\Lead`, `Store`, `Fdd`, …
- Services: `App\Services\Lead\PipelineService`, `App\Services\Query\GridQueryService`
- Jobs: `App\Jobs\Comms\SendDripStepJob`
- Commands: `legacy:inventory`, `legacy:import`

## Database

- Tables: plural snake_case (`lead_phase_events`)
- Legacy lineage: `legacy_post_id`, `legacy_user_id` (nullable bigint, indexed)
- **Metadata tiers:** entity columns (filter/sort) → relation tables → `field_values` (typed) → `extras` (import-only)
- Custom fields: `field_groups`, `fields` (with `storage`, `maps_to_column`), `field_values` — not `meta_key`/`meta_value`
- Do not add generic `meta` JSON columns for domain data; provider payloads (webhooks) may use `meta` on log tables only

## Frontend

- Package names: `@fil/app`, `@fil/widget`
- API client module: `src/lib/api/client.ts` with base URL from `VITE_API_URL`
- No `blog_ID` or vendor-specific env constants in new code

## Environment

- `FIL_AI_SERVICE_URL` — external AI/RAG service (formerly ZAI per client)
- `FIL_EMBED_ALLOWED_ORIGINS` — comma-separated CORS origins for widget
- Standard Laravel: `APP_*`, `DB_*`, `REDIS_*`, `MAIL_*`, etc.

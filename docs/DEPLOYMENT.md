# FIL deployment — minimal stack

> **Docs:** [README.md](./README.md) · **Runbook:** [MVP_DEPLOY.md](./MVP_DEPLOY.md) · **Roadmap:** [PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md)

One **per-client** install. Optimize for **fewest moving parts** and **lowest cost**.

## Production (target)

| Layer | Choice | Why |
| ----- | ------ | --- |
| Host | **1 VPS** via [Laravel Forge](https://forge.laravel.com) (Hetzner/DO ~$6–12/mo) | App, web, queue worker, scheduler on one box |
| Database | **PostgreSQL on same VPS** (Forge-managed) | No separate managed DB bill unless client outgrows single server |
| Queue | **`database` driver** | Uses existing `jobs` table — **no Redis** |
| Cache | **`file` or `database`** | Facet counts / options; short TTL where needed |
| Sessions | **`database`** | Already in Laravel default stack |
| Files | **Local disk** (`storage/app`) + Forge backups | Skip S3/Spaces until required |
| Search / grids | **PostgreSQL** (indexes + `pg_trgm`) | **No Elasticsearch** |
| Email (dev) | Mailhog locally only | — |
| Email (prod) | **Mailgun** (external API) | Required integration, not infra you run |
| SMS | **Twilio** (external API) | Same |
| ACH | **Dwolla + Plaid** (external APIs) | Same |
| POS | **Square / Clover / Booker** APIs | Same |
| AI | **External HTTP service** (`FIL_AI_SERVICE_URL`) | FIL proxies; no vector DB in FIL |

### What we deliberately do NOT run

- Elasticsearch / OpenSearch
- Redis (unless a client proves they need it at scale)
- Separate worker servers
- Kubernetes
- MinIO / object storage (until file volume warrants it)
- Managed Postgres as a second bill (optional upgrade path only)

### Forge setup (per client)

1. Create server (Ubuntu, **PHP 8.4**).
2. Install **PostgreSQL 17** on server (Forge recipe).
3. Create site → deploy `backend/` (Nginx serves `public/`; SPA shell via Laravel, assets in `public/fil-assets/`).
4. Enable **queue worker** daemon → `php artisan queue:work database --sleep=3`.
5. Enable **scheduler** → `* * * * * php artisan schedule:run`.
6. Env: `QUEUE_CONNECTION=database`, `CACHE_STORE=file`, `SESSION_DRIVER=database`, `DB_CONNECTION=pgsql`.
7. SSL via Forge (Let’s Encrypt).

**Estimated infra per client: ~$15–25/mo** (VPS + Forge) plus usage-based Mailgun/Twilio.

### Upgrade path (only when needed)

| Trigger | Add |
| ------- | --- |
| Queue backlog / slow workers | Redis + `QUEUE_CONNECTION=redis` on same VPS |
| Disk / backup requirements | DO Spaces or S3 for documents |
| DB CPU saturated | Managed Postgres or read replica |
| Full-text beyond ~10k leads | Revisit Meilisearch on same VPS (still not Elastic) |

## Local development

See **`docs/LOCAL_DEV.md`** for setup, demo logins, and browser test checklist.

```bash
docker compose up -d   # postgres + mailhog only
cd backend && cp .env.example .env && php artisan migrate --seed
cd frontend && npm run dev   # or npm run build + php artisan serve
```

## MVP go-live

See **`docs/MVP_DEPLOY.md`** for ship gate, Forge runbook, cutover, and smoke tests.

See root `docker-compose.yml` — intentionally minimal (Postgres + Mailhog only).

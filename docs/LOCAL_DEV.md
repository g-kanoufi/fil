# Local development & browser testing

> **Docs:** [README.md](./README.md) · **Deploy:** [MVP_DEPLOY.md](./MVP_DEPLOY.md)

Use this guide to run FIL locally and verify features in the browser.

## Prerequisites

- PHP 8.4, Composer
- Node 22+
- Docker (Postgres + Mailhog)

## First-time setup

```bash
# From repo root
docker compose up -d

cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed

cd ../frontend
npm install
npm run build
cd widget && npm install && npm run build
```

The seed creates demo users and sample CRM data (local/testing only).

### Database (PostgreSQL)

Local dev uses **PostgreSQL only** (same as Forge staging/production). SQLite is reserved for PHPUnit and E2E smoke tests.

```bash
docker compose up -d postgres   # from repo root
```

Ensure `backend/.env` matches `.env.example`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=fil
DB_USERNAME=fil
DB_PASSWORD=fil
```

**TablePlus:** create a PostgreSQL connection with the values above. Do not open `database/database.sqlite` for day-to-day dev — that file is legacy from earlier local runs.

**Client data:** after `migrate`, run `legacy:import --execute` (see [LEGACY_IMPORT_DRY_RUN.md](./LEGACY_IMPORT_DRY_RUN.md)) to load `data/local.sql.gz`.

### Demo credentials

| Email | Role | Scope | Password |
| ----- | ---- | ----- | -------- |
| `admin@fil.test` | admin | unrestricted | `password` |
| `franchisor@fil.test` | franchisor | unrestricted | `password` |
| `owner@fil.test` | lead_owner | unrestricted | `password` |
| `area_rep@fil.test` | area_rep | Southwest territory | `password` |
| `franchisee@fil.test` | franchisee | PrimeIV Scottsdale only | `password` |
| `storemanager@fil.test` | storemanager | PrimeIV Phoenix only | `password` |
| `employee@fil.test` | employee | PrimeIV Scottsdale only | `password` |
| `prospect@fil.test` | prospect | *(staff login blocked)* | `password` |

Sample data: 4 leads, 2 stores, 1 area (Southwest). Franchise roles have store/area assignments for scope testing.

## Run the stack

**Option A — production-like (single origin, simplest for first test)**

```bash
# Terminal 1 — API + built SPA
cd backend && php artisan serve

# Terminal 2 — queue worker (drips)
cd backend && php artisan queue:work database --sleep=3
```

Open **http://127.0.0.1:8000/app** → login → explore nav.

Assets are served from `/fil-assets/` (Laravel blade shell); deep links like `/app/reports/leads` work correctly.

**Option B — frontend hot reload (Vite dev server)**

```bash
# Terminal 1
cd backend && php artisan serve

# Terminal 2
cd frontend && npm run dev

# Terminal 3 (optional)
cd backend && php artisan queue:work database --sleep=3
```

Open **http://localhost:5173/app** (Vite proxies `/api` to `:8000`).

Ensure `.env` includes:

```env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:5173,127.0.0.1,127.0.0.1:8000
```

## What to test in the browser

### 1. Staff login & navigation

1. Go to `/app/login`
2. Sign in as `franchisor@fil.test` / `password`
3. Confirm home shows API status **ok** and nav links (Leads, Stores, Contacts, Assistant, …)
4. Sign out; try `owner@fil.test` — should see fewer nav items (no Stores/Royalties)
5. Try `franchisee@fil.test` — Stores yes, Leads no; direct `/app/reports/leads` → Access denied
6. Try `prospect@fil.test` — login rejected at sign-in form

### 2. Leads grid

1. Nav → **Leads** (`/app/reports/leads`)
2. Expect **4 records** and a table of application names
3. DevTools → Network → `POST /api/v1/query/leads` returns `hits.total.value: 4` and `aggregations`

### 3. Stores & contacts grids

- **Stores** (`/app/reports/stores`) — 2 stores
- **Contacts** (`/app/reports/contacts`) — staff users (admin, franchisor, owner)

### 4. AI assistant

1. Nav → **Assistant** (`/app/ai`)
2. Type a message → Send
3. Expect user + assistant bubbles (stub reply until `FIL_AI_SERVICE_URL` is set)

### 5. Public lead widget

1. Open **http://127.0.0.1:8000/embed-demo**
2. Fill first name, last name, email → Submit
3. Expect “Thanks — we received your inquiry.”
4. Confirm new lead: re-login → Leads grid shows 5 records, or check Mailhog for drip email attempt

### 6. Mailhog (email)

Open **http://localhost:8025** after widget submit or drip jobs — outbound mail appears here locally.

### 7. Lead detail — composer & activity

1. Open a lead from the Leads grid
2. Send an **email** or **SMS** via the composer (requires `communications.manage`)
3. Confirm the message appears in the activity timeline

### 8. Bulk FDD (optional)

1. Leads grid → select rows via checkboxes
2. **Send FDD** → pick document → confirm send

### 9. Staging gate (before deploy)

```bash
cd backend && php artisan mvp:staging-check
```

```bash
bash scripts/e2e-smoke.sh   # Playwright: smoke + auth flows
```

Resolves database, migrations, roles, queue, mail guard, demo accounts (staging/prod), embed keys, and Sanctum domains.

## Useful artisan commands

```bash
php artisan test --compact
php artisan legacy:inventory                    # dump row counts
php artisan legacy:import                       # dry-run (default; no --execute)
php artisan legacy:import --execute             # import from dump
php artisan legacy:parity-report
php artisan legacy:finalize --strict
php artisan db:seed --class=DemoSeeder       # re-seed demo data only
```

See [LEGACY_IMPORT_DRY_RUN.md](./LEGACY_IMPORT_DRY_RUN.md) for the full Phase 4 checklist.

## Troubleshooting

| Symptom | Fix |
| ------- | --- |
| Login succeeds but session lost on refresh | Add your host to `SANCTUM_STATEFUL_DOMAINS`; use same origin (Option A) |
| Blank `/app` page | Run `cd frontend && npm run build` |
| Widget 401 | Set `FIL_EMBED_SITE_KEYS=pk_dev` and use `data-site-key="pk_dev"` |
| Grid empty after seed | Confirm `DB_CONNECTION=pgsql` and Postgres is running (`docker compose ps`); run `php artisan migrate --seed` |
| `SQLSTATE[08006]` / connection refused | `docker compose up -d postgres` |
| Artisan uses wrong DB (sqlite / e2e) | Unset shell overrides: `unset DB_CONNECTION DB_DATABASE` — E2E script exports sqlite only inside its subshell, but a parent shell may still have them |
| Drip emails not sent | Start `php artisan queue:work database` |

## When to notify the team

Browser verification is worth doing after:

- Auth / session changes
- New SPA routes or grid wiring
- Widget or public API changes
- Anything visible on `/app`, `/embed-demo`, or Mailhog

Run backend tests first (`php artisan test --compact`), then the checklist above.

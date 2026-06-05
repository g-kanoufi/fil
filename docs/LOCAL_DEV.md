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
| `prospect@fil.test` | prospect | Portal only — linked to **Jane Smith Application** | `password` |

Sample data: 4 leads, 2 stores, 1 area (Southwest). Franchise roles have store/area assignments for scope testing.

**Prospect portal:** **http://127.0.0.1:8000/portal/login** (or `http://localhost:5173/portal/login` with Vite). Sign in as `prospect@fil.test` / `password` to open the long-form application.

If you see **“No active application is linked to this account”**, repair demo data (same DB your `php artisan serve` uses):

```bash
cd backend && php artisan fil:ensure-demo-prospect
```

Run **`./scripts/dev-serve.sh restart`** afterward so the running PHP process picks up code and DB changes.

## Run the stack

**Recommended — managed dev server (browser testing)**

From repo root:

```bash
./scripts/dev-serve.sh start    # first time, or after a long break
./scripts/dev-serve.sh status   # PID + /api/health
./scripts/dev-serve.sh restart  # after backend changes or `cd frontend && npm run build`
./scripts/dev-serve.sh stop
```

Keeps **http://127.0.0.1:8000** on PostgreSQL (`backend/.env`), builds the SPA once if needed, and repairs demo prospect portal login. Logs: `/tmp/fil-dev-serve.log`.

**Option A — production-like (manual terminals)**

```bash
# Terminal 1 — API + built SPA
cd backend && php artisan serve

# Terminal 2 — queue worker (drips)
cd backend && php artisan queue:work database --sleep=3
```

Open **http://127.0.0.1:8000/** → login → explore nav.

Assets are served from `/fil-assets/` (Laravel blade shell); deep links like `/reports/leads` work correctly. Legacy `/app/*` URLs redirect to the same paths without the prefix.

**Option B — frontend hot reload (Vite dev server)**

```bash
# Terminal 1
cd backend && php artisan serve

# Terminal 2
cd frontend && npm run dev

# Terminal 3 (optional)
cd backend && php artisan queue:work database --sleep=3
```

Open **http://localhost:5173/** (Vite proxies `/api` to `:8000`).

Ensure `.env` includes:

```env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:5173,127.0.0.1,127.0.0.1:8000
```

## What to test in the browser

### 1. Staff login & navigation

1. Go to `/login`
2. Sign in as `franchisor@fil.test` / `password`
3. Confirm home shows API status **ok** and nav links (Leads, Stores, Contacts, Assistant, …)
4. Sign out; try `owner@fil.test` — should see fewer nav items (no Stores/Royalties)
5. Try `franchisee@fil.test` — Stores yes, Leads no; direct `/reports/leads` → Access denied
6. Try `prospect@fil.test` — login rejected at staff sign-in form

### 2. Prospect portal

1. Open **http://127.0.0.1:8000/portal/login**
2. Sign in as `prospect@fil.test` / `password`
3. Confirm branded sign-in page (same shell as staff) and redirect to **Application**
4. Sign out from the portal header

### 3. Leads grid

1. Nav → **Leads** (`/reports/leads`)
2. Expect **4 records** and a table of application names
3. DevTools → Network → `POST /api/v1/query/leads` returns `hits.total.value: 4` and `aggregations`

### 4. Stores & contacts grids

- **Stores** (`/reports/stores`) — 2 stores
- **Contacts** (`/reports/contacts`) — staff users (admin, franchisor, owner)

### 5. AI assistant

1. Nav → **Assistant** (`/ai`)
2. Type a message → Send
3. Expect user + assistant bubbles (stub reply until `FIL_AI_SERVICE_URL` is set)

### 6. Public lead widget

1. Log in to the staff app, then open **Settings → Widget form → Preview embed widget** (`/settings/widget/demo`) — staff-only; not for client sites
2. Or use **Settings → Widget form → Open staff preview** for the embed snippet and site key
3. Fill first name, last name, email → Submit
4. Expect “Thanks — we received your inquiry.”
5. Confirm new lead: Leads grid, or check Mailhog for drip email attempt

### 7. Mailhog (email)

Open **http://localhost:8025** after widget submit or drip jobs — outbound mail appears here locally.

### 8. Lead detail — composer & activity

1. Open a lead from the Leads grid
2. Send an **email** or **SMS** via the composer (requires `communications.manage`)
3. Confirm the message appears in the activity timeline

### 9. Bulk FDD (optional)

1. Leads grid → select rows via checkboxes
2. **Send FDD** → pick document → confirm send

### 10. Staging gate (before deploy)

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
| Blank staff home page | Run `cd frontend && npm run build` |
| Widget 401 | Set `FIL_EMBED_SITE_KEYS=pk_dev` and use `data-site-key="pk_dev"` |
| Grid empty after seed | Confirm `DB_CONNECTION=pgsql` and Postgres is running (`docker compose ps`); run `php artisan migrate --seed` |
| `SQLSTATE[08006]` / connection refused | `docker compose up -d postgres` |
| Artisan uses wrong DB (sqlite / e2e) | Unset shell overrides: `unset DB_CONNECTION DB_DATABASE` — E2E script exports sqlite only inside its subshell, but a parent shell may still have them |
| Drip emails not sent | Start `php artisan queue:work database` |
| History **Page visits** empty; `POST …/page-views` **503** | **Postgres:** `unset DB_CONNECTION DB_DATABASE` then `php artisan migrate --force` (migration `2026_06_03_120000_create_activity_navigation_table`). **Restart** `php artisan serve` — a long-running server started with `DB_CONNECTION=sqlite` in the shell only sees the test DB (no `activity_navigation`). In Network, page-views should return **202**, not 503. On History, use **Page visits** (not **Staff actions**). Browse detail routes (not grid list URLs); tracker waits ~3s. |

## When to notify the team

Browser verification is worth doing after:

- Auth / session changes
- New SPA routes or grid wiring
- Widget or public API changes
- Anything visible on `/`, `/settings/widget/demo`, or Mailhog

Run backend tests first (`php artisan test --compact`), then the checklist above.

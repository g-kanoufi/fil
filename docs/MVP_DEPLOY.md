# FIL MVP — deployment plan

> **Docs:** [README.md](./README.md) · **Architecture:** [DEPLOYMENT.md](./DEPLOYMENT.md) · **Roadmap:** [PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md)

Per-client install on a single VPS (Laravel Forge). Use this when core CRM flows are green in local browser testing and PHPUnit/Vitest.

## MVP definition (ship gate)

Ship when **all** of these pass in staging:

| Area | MVP requirement | Verify |
| ---- | --------------- | ------ |
| Auth | Staff login, role nav, prospect blocked | Browser + `PolicyTest` |
| Leads | Grid, show, update, phase transition | API tests + `/app/reports/leads` |
| Widget | Public intake creates phase-1 lead | Settings → Widget form → **Preview embed widget** (`/app/settings/widget/demo`) + parity test |
| Drips | Queue sends email step (Mailgun in prod) | Mailgun sandbox + job logs |
| FDD | List, single send, **bulk send from grid** | API tests + manual send |
| Comms | **SMS/email composer** on lead detail | `POST /api/v1/communications` + browser |
| Activity | **Timeline** on lead + contact detail | `GET /api/v1/activity` + browser |
| Stores | CRUD + grid | API + `/app/reports/stores` |
| Contacts | Staff grid with role facets | `/app/reports/contacts` |
| Royalties / ACH | Read APIs + royalty calculate stub | API tests |
| AI | Thread + message (proxy or stub) | `/app/ai` |
| Import | `legacy:import --execute` on client dump | Parity report within agreed delta |
| Ops | Migrations, seed (no demo in prod), queue worker, scheduler, backups | Forge checklist below |

**Not required for MVP v1:** full legacy UI parity, PDF/signature, Dwolla/Plaid live ACH, POS sync, Elasticsearch, Redis.

## Pre-deploy checklist (1–2 days before)

### Code & assets

- [ ] `cd backend && php artisan test --compact` — all green
- [ ] `cd frontend && npm run test:run && npm run build`
- [ ] `cd frontend/widget && npm run build` → `backend/public/widget/form.js`
- [ ] OpenAPI / docs updated for any API changes
- [ ] Remove or disable `DemoSeeder` in production (`APP_ENV=production` already skips it)
- [ ] Set strong passwords; no `password` demo accounts in prod DB

### Client data

- [ ] Run `legacy:import-acf` with client ACF path
- [ ] Complete dry-run checklist — [LEGACY_IMPORT_DRY_RUN.md](./LEGACY_IMPORT_DRY_RUN.md)
- [ ] Run `legacy:import --execute` against client dump
- [ ] Run `legacy:parity-report` — document acceptable gaps
- [ ] Spot-check 10 leads, 5 stores in staging UI

### Secrets & integrations

| Variable | Production value |
| -------- | ---------------- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://crm.clientdomain.com` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | `127.0.0.1` (Forge: localhost or socket) |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | Forge database name |
| `DB_USERNAME` | Forge database user |
| `DB_PASSWORD` | Forge database password |
| `QUEUE_CONNECTION` | `database` |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `file` |
| `SANCTUM_STATEFUL_DOMAINS` | `crm.clientdomain.com` |
| `FIL_EMBED_SITE_KEYS` | Optional extra allowlist; each widget form also gets an auto-generated `pk_live_*` key in Settings → Widget form |
| `FIL_EMBED_ALLOWED_ORIGINS` | Client marketing site origins — **required** in staging/production; enforced on `/api/public/v1/leads` and `form-config` |
| `MAIL_*` | Mailgun SMTP/API |
| `MAILGUN_WEBHOOK_SIGNING_KEY` | Mailgun event + inbound route signing |
| `FIL_AI_SERVICE_URL` | Client AI service (optional for v1) |

## Staging environment template

Forge **Environment** should start from [`backend/.env.staging.example`](../backend/.env.staging.example). Copy it into Forge, replace `CHANGE_ME` / `clientdomain.com` placeholders, then:

```bash
cd backend
php artisan config:cache
php artisan mvp:staging-check
```

The deploy script ([`scripts/forge-deploy.sh`](../scripts/forge-deploy.sh)) runs `mvp:staging-check` automatically — a FAIL row blocks deploy.

### `mvp:staging-check` gate

| Check | Env / config | Staging | Production |
| ----- | ------------ | ------- | ---------- |
| Database | `DB_*` | OK required | OK required |
| Migrations | — | Core tables present | Core tables present |
| Roles & permissions | Seed `RolesAndPermissionsSeeder` + admin user | OK required | OK required |
| Queue worker | `QUEUE_CONNECTION=database` | WARN if `sync` | WARN if `sync` |
| Failed jobs | — | WARN if backlog | WARN if backlog |
| Mail delivery | `MAIL_MAILER=mailgun` (not `log`) | OK | FAIL if `log` |
| APP_URL | `APP_URL=https://…` | WARN if localhost | WARN if localhost |
| APP_DEBUG | `APP_DEBUG=false` | OK | FAIL if `true` |
| Session cookies | `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax` | FAIL if insecure | FAIL if insecure |
| Demo accounts | No `@fil.test` users | FAIL if present | FAIL if present |
| Embed site keys | `FIL_EMBED_SITE_KEYS=pk_live_*` (not `pk_dev`) | FAIL if missing/dev | FAIL if missing/dev |
| Embed allowed origins | `FIL_EMBED_ALLOWED_ORIGINS` | FAIL if empty | FAIL if empty |
| Sanctum domains | `SANCTUM_STATEFUL_DOMAINS` includes APP_URL host | WARN if mismatch | WARN if mismatch |
| Mailgun webhook | `MAILGUN_WEBHOOK_SIGNING_KEY` when `MAIL_MAILER=mailgun` | WARN if unset | WARN if unset |
| Dwolla webhook | `DWOLLA_WEBHOOK_SECRET` | FAIL if unset | FAIL if unset |
| Content Security Policy | `FIL_CSP_ENABLED=true` | WARN if report-only; FAIL if disabled | FAIL if disabled |
| SPA assets | `npm run build` (staff + widget) | FAIL if manifest missing | FAIL if manifest missing |

Exit code **0** = pass (WARN rows allowed). Exit code **1** = at least one FAIL — fix before go-live smoke tests.

Preview the active CSP header without a browser:

```bash
php artisan security:csp
```

## CSP live validation (Plaid / Dwolla / reCAPTCHA)

When sandbox keys are available, validate CSP **before** enforcing:

1. On staging Forge env, set `FIL_CSP_REPORT_ONLY=true` (see `.env.staging.example`).
2. Deploy and run `php artisan security:csp` — confirm Plaid/Dwolla/reCAPTCHA domains are listed.
3. In browser (logged-in staff):
   - Open **ACH** page — Plaid Link should load without CSP console errors.
   - Open any Dwolla enrollment UI — same.
   - Submit widget with reCAPTCHA enabled — challenge iframe loads.
4. Fix violations by appending domains to `FIL_CSP_SCRIPT_SRC`, `FIL_CSP_CONNECT_SRC`, or `FIL_CSP_FRAME_SRC` (comma-separated). Defaults cover standard Plaid/Dwolla/reCAPTCHA hosts — override only when console shows blocked URLs.
5. Set `FIL_CSP_REPORT_ONLY=false` and redeploy. `mvp:staging-check` should show CSP **OK** (not WARN).

Local note: CSP is off when `APP_ENV=local` unless `FIL_CSP_ENABLED=true`. Use staging for integration CSP testing.

## Forge deployment runbook

### 1. Provision server

1. Forge → New server (Ubuntu 24.04, **PHP 8.4**)
2. Install **PostgreSQL 17** on same server
3. Create database + user; note credentials

### 2. Create site

- Root: `/home/forge/crm.clientdomain.com/current/backend/public`
- PHP 8.4, HTTPS via Let’s Encrypt
- Deploy repo; **build script** — use [`scripts/forge-deploy.sh`](../scripts/forge-deploy.sh) or inline:

```bash
# Recommended: repo script (runs migrate, builds, caches, mvp:staging-check)
bash scripts/forge-deploy.sh
```

Or inline:

```bash
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

cd ../frontend
npm ci
npm run build

cd widget
npm ci
npm run build
```

### 3. Nginx

- Document root: `backend/public`
- SPA fallback: existing Laravel routes serve `/app` via `app.blade.php`
- Static: `/fil-assets/*`, `/widget/form.js`
- SPA deep links (`/app/reports/leads`, etc.) must fall through to `index.php` — do **not** place a `public/app/` directory (conflicts with routes)

### 4. Daemons (Forge)

| Daemon | Command |
| ------ | ------- |
| Queue | `php artisan queue:work database --sleep=3 --tries=3 --max-time=3600` |
| Scheduler | `* * * * * cd /home/forge/.../current/backend && php artisan schedule:run` |

### 5. Backups

- Forge daily database backup
- Weekly snapshot of `storage/app` (documents when enabled)
- Store `.env` in Forge encrypted env (not in git)

### 6. Post-deploy smoke test (15 min)

Run `php artisan mvp:staging-check` first — it validates database, migrations, roles, queue, and mail guard before manual checks.

1. `GET https://crm.clientdomain.com/api/health` → 200
2. `/app/login` → staff login
3. Leads grid loads
4. Client embed page → widget submit → lead appears
5. `php artisan queue:work` processing (or Forge daemon logs clean)
6. Mailgun receives test drip (sandbox domain OK for staging)

## Environments

| Stage | URL | Data | Demo seed |
| ----- | --- | ---- | --------- |
| Local | `:8000` / Vite `:5173` | `migrate --seed` | Yes |
| Staging | `staging.crm.client.com` | Import from anonymized dump | No |
| Production | `crm.client.com` | Import + cutover | No |

## Cutover from legacy CRM (client go-live)

1. **Freeze** legacy system writes (maintenance mode)
2. Final `legacy:import --execute` on fresh FIL staging
3. Parity report sign-off
4. Point DNS / embed script to FIL widget URL
5. Train staff on `/app` (login, leads grid, assistant)
6. Keep legacy system read-only 2 weeks for rollback reference
7. Decommission legacy stack after acceptance

## Rollback

- DNS revert to legacy CRM
- FIL remains read-only; no dual-write
- Document delta since cutover for re-import if needed

## Cost estimate (per client)

| Item | Monthly |
| ---- | ------- |
| VPS (Hetzner/DO 2GB+) | $6–12 |
| Forge | $12–19 |
| Mailgun (usage) | $0–35 |
| Twilio (if SMS enabled) | usage |
| **Infra subtotal** | **~$20–40** |

### AI (optional — `FIL_AI_SERVICE_URL`)

FIL does **not** run an LLM or vector DB. When `FIL_AI_SERVICE_URL` is set, Laravel proxies two endpoints on an external service (typically Cloud Run + provider API — see `docs/SEARCH.md`, `docs/DEPLOYMENT.md`):

| FIL feature | External endpoint | Without AI URL |
| ----------- | ----------------- | -------------- |
| Staff assistant (`/app/ai`) | `POST {service}/chat` | Stub reply; messages saved — **$0** |
| NL grid search (toolbar) | `POST {service}/interpret-grid` | Local heuristics — **$0** |
| Document RAG (assistant context) | Handled inside AI service | Not in FIL |

**MVP v1 default:** leave `FIL_AI_SERVICE_URL` empty → **$0/mo** AI line item. Ship gate still passes (stub + heuristics).

**When AI is enabled**, cost is usage-based on the provider behind the proxy (OpenAI, Anthropic, etc.) plus minimal proxy hosting. Ballpark for a **GPT-4o-mini–class** model (~$0.15/1M input, ~$0.60/1M output) and a small franchise staff team:

| Usage profile | Staff | Assistant chat | NL grid search | Proxy (Cloud Run) | **AI subtotal/mo** |
| ------------- | ----- | ---------------- | -------------- | ----------------- | ------------------ |
| **Light** | ~5 | ~100 turns (~3K in / 800 out each) | ~50 queries (~1.5K in / 300 out) | ~$0–2 | **~$1–3** |
| **Typical** | ~10–15 | ~400 turns | ~200 queries | ~$2–5 | **~$5–15** |
| **Heavy** | ~25+ | ~1,500+ turns | ~800+ queries | ~$5–15 | **~$25–60** |

Assumptions for the table above:

- One “turn” = one user message + one assistant reply (RAG adds ~1–2K input tokens when documents are indexed in the AI service).
- Grid interpret sends the resource schema each call (~1–2K tokens); staff use it occasionally, not on every grid load.
- No separate FIL charge for embeddings or vector storage — that lives in the external service (refresh cost depends on document volume; often **$0–5/mo** for a few hundred PDFs re-indexed monthly).

**All-in with AI (typical):** infra **~$20–40** + AI **~$5–15** → **~$25–55/mo** per client.

To cap spend: rate-limit `/interpret-grid` per user, set provider budget alerts, or disable `FIL_AI_SERVICE_URL` and rely on heuristics until needed.

## Upgrade triggers (post-MVP)

See `docs/DEPLOYMENT.md` for Redis, managed Postgres, object storage, and search upgrades.

## Related docs

- `docs/LOCAL_DEV.md` — browser testing locally
- `docs/DEPLOYMENT.md` — minimal stack philosophy
- `docs/parity-checklist.md` — behavior mapping
- `docs/AUTH.md` — roles and policies

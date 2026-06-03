# Forge staging checklist

Operator checklist for first client staging VPS. Complements [MVP_DEPLOY.md](./MVP_DEPLOY.md) and [PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md).

**Prerequisites:** Forge account, GitHub repo access, client domain DNS (or staging subdomain), Mailgun account (sandbox OK).

---

## Phase 0 — VPS and site

### 0.1 Provision server

- [ ] Forge → New server: **Ubuntu 24.04**, **PHP 8.4**
- [ ] Install **PostgreSQL 17** on same server
- [ ] Create database + user; note credentials

### 0.2 Create site

- [ ] New site: `staging.crm.clientdomain.com` (or agreed hostname)
- [ ] Web directory: `/home/forge/staging.crm.clientdomain.com/current/backend/public`
- [ ] Connect Git repo; deploy branch (e.g. `staging` or `dev`)
- [ ] Enable **Let's Encrypt** HTTPS

### 0.3 Environment

- [ ] Copy [`backend/.env.staging.example`](../backend/.env.staging.example) into Forge → Site → Environment
- [ ] Replace placeholders: `APP_KEY` (`php artisan key:generate --show`), `DB_*`, `APP_URL`, `SANCTUM_STATEFUL_DOMAINS`
- [ ] Set embed vars (after first deploy + widget settings):
  - `FIL_EMBED_ALLOWED_ORIGINS` — client marketing site origins
  - `FIL_EMBED_SITE_KEYS=pk_live_*` — from Settings → Widget form
- [ ] Set webhook secrets (required by `mvp:staging-check`):
  - `MAILGUN_WEBHOOK_SIGNING_KEY`
  - `DWOLLA_WEBHOOK_SECRET`
- [ ] CSP: `FIL_CSP_ENABLED=true`, `FIL_CSP_REPORT_ONLY=true` initially

### 0.4 Deployment script

- [ ] Forge → Deployment script:

```bash
cd /home/forge/staging.crm.clientdomain.com/current
bash scripts/forge-deploy.sh
```

- [ ] First deploy: verify `composer install`, `npm ci`, migrations, frontend + widget build succeed

### 0.5 Daemons

| Daemon | Forge command |
| ------ | ------------- |
| Queue | `php artisan queue:work database --sleep=3 --tries=3 --max-time=3600` |
| Scheduler | `* * * * * cd /home/forge/staging.crm.clientdomain.com/current/backend && php artisan schedule:run` |

- [ ] Enable both in Forge → Daemons / Scheduler

### 0.6 Backups

- [ ] Enable Forge **daily database backup**
- [ ] Confirm `.env` stored in Forge encrypted env only (not in git)

### 0.7 Staging gate

```bash
cd backend
php artisan mvp:staging-check
php artisan security:csp
```

- [ ] Exit code **0** (fix all FAIL rows before smoke)
- [ ] No `@fil.test` demo users in DB

### 0.8 Manual smoke (~15 min)

- [ ] `GET https://staging.crm.clientdomain.com/api/health` → 200
- [ ] `/app/login` → staff login (create admin via seeder or import)
- [ ] Leads grid loads
- [ ] Lead detail: FDD send, composer UI
- [ ] Settings → Widget demo → submit → lead appears
- [ ] Queue daemon processing (check Forge logs)

---

## Phase 2 — Mail and SMS

### Mailgun (sandbox OK for staging)

- [ ] Create Mailgun domain (sandbox or client subdomain)
- [ ] DNS: SPF, DKIM, MX (if inbound)
- [ ] Set env: `MAIL_MAILER=mailgun`, `MAILGUN_DOMAIN`, `MAILGUN_SECRET`, `MAILGUN_WEBHOOK_SIGNING_KEY`
- [ ] Configure Mailgun route → `POST https://staging.crm.clientdomain.com/api/webhooks/mailgun`
- [ ] Test: send email from lead composer; verify delivery + webhook

### Twilio (if SMS enabled)

- [ ] Twilio account + messaging service
- [ ] Set `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_FROM_NUMBER`
- [ ] Webhooks → `/api/webhooks/twilio/inbound`, `/api/webhooks/twilio/status`
- [ ] Test: send SMS from composer; verify inbound reply matching

### Drip verification

- [ ] Enroll test lead in drip; confirm `SendDripStepJob` processes (queue logs)
- [ ] Re-run `mvp:staging-check` after mail config

---

## Phase 4 — Client data import

**Runbook:** [LEGACY_IMPORT_DRY_RUN.md](./LEGACY_IMPORT_DRY_RUN.md)

- [ ] Obtain latest client `.sql.gz`; upload to server (not in git)
- [ ] Set `FIL_LEGACY_DUMP_PATH`, `FIL_LEGACY_TABLE_PREFIX`
- [ ] Fresh DB: `php artisan migrate --force` (no demo seed on staging)
- [ ] Pipeline:
  1. `php artisan legacy:import-acf`
  2. `php artisan legacy:sync-interest-region-terms --execute`
  3. `php artisan legacy:import --execute --force`
  4. `php artisan legacy:import-documents --execute`
  5. `php artisan legacy:drain-extras --execute`
  6. `php artisan legacy:finalize --strict`
- [ ] `php artisan legacy:parity-report /path/to/dump.sql.gz --samples`
- [ ] UI spot-check: 10 leads, 5 stores, 3 contacts
- [ ] Client sign-off on parity report

---

## Phase 2b — Financial sandbox + CSP

When Plaid/Dwolla sandbox keys are available:

- [ ] Set `PLAID_*`, `DWOLLA_*` env vars (see `.env.staging.example`)
- [ ] Browser: ACH page → Plaid Link loads (no CSP console errors)
- [ ] Browser: Dwolla enrollment UI loads
- [ ] Widget reCAPTCHA iframe loads (if enabled)
- [ ] Fix CSP: append blocked domains to `FIL_CSP_*_SRC` env vars
- [ ] Set `FIL_CSP_REPORT_ONLY=false`; redeploy; `mvp:staging-check` CSP **OK**

Full steps: [MVP_DEPLOY.md § CSP live validation](./MVP_DEPLOY.md#csp-live-validation-plaid--dwolla--recaptcha)

---

## Phase 5 — Observability and UAT

### Sentry

- [ ] Create Sentry project(s) for Laravel + React
- [ ] Set `SENTRY_LARAVEL_DSN` and frontend `VITE_SENTRY_DSN` in Forge env
- [ ] Trigger test error; confirm event in Sentry

### Logging

- [ ] `LOG_LEVEL=info` on staging
- [ ] Confirm Forge log rotation enabled for `storage/logs`

### Backup restore drill

- [ ] Restore latest Forge DB backup to a scratch database
- [ ] Verify row counts match production staging snapshot
- [ ] Document restore steps for on-call

### Client UAT

- [ ] Walk through: login → leads → FDD → comms → stores → closings
- [ ] Collect sign-off before production DNS cutover

### Production cutover prep

- [ ] Duplicate checklist for production hostname + env
- [ ] Freeze legacy CRM writes
- [ ] Final import on production DB
- [ ] DNS / embed script cutover
- [ ] Monitor queue 48h post-launch

See [MVP_DEPLOY.md § Cutover](./MVP_DEPLOY.md#cutover-from-legacy-crm-client-go-live).

---

## Quick reference

| Check | Command / URL |
| ----- | ------------- |
| Health | `GET /api/health` |
| Staging gate | `php artisan mvp:staging-check` |
| CSP preview | `php artisan security:csp` |
| E2E smoke (local against staging) | `FIL_BASE_URL=https://staging... ./scripts/e2e-smoke.sh` |

**Related:** [DEPLOYMENT.md](./DEPLOYMENT.md) · [SECURITY_AUDIT.md](./SECURITY_AUDIT.md) financial checklist

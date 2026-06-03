# Forge staging checklist

Operator checklist for first client staging VPS. Complements [MVP_DEPLOY.md](./MVP_DEPLOY.md) and [PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md).

**Prerequisites:** Forge account, GitHub repo access, client domain DNS (or staging subdomain), Mailgun account (sandbox OK).

---

## Phase 0 — VPS and site

### 0.1 Provision server

- [x] Forge → New server: **Ubuntu 24.04**, **PHP 8.4**
- [x] Install **PostgreSQL 17** on same server
- [x] Create database + user; note credentials

### 0.2 Create site

- [x] New site: `fil.on-forge.com` (or agreed hostname)
- [x] Web directory: `/home/forge/fil.on-forge.com/backend/public`
- [x] Connect Git repo; deploy branch (e.g. `staging` or `dev`)
- [x] Enable **Let's Encrypt** HTTPS

### 0.3 Environment

Forge SSH: site root is **`/home/forge/fil.on-forge.com`** (no `current/` on this site). Laravel lives in **`backend/`** — run artisan from `/home/forge/fil.on-forge.com/backend`. See [STAGING.local.md](./STAGING.local.md) for URLs (gitignored).

- [x] Copy [`backend/.env.staging.example`](../backend/.env.staging.example) into Forge → Site → Environment
- [x] Replace placeholders: `APP_KEY` (generate locally or on server):

```bash
cd /home/forge/fil.on-forge.com/backend
php artisan key:generate --show
```

Paste the output into Forge env as `APP_KEY=base64:…`. Also set `DB_*`, `APP_URL`, `SANCTUM_STATEFUL_DOMAINS`.
- [ ] Set embed vars (after first deploy + widget settings):
  - `FIL_EMBED_ALLOWED_ORIGINS` — client marketing site origins
  - `FIL_EMBED_SITE_KEYS=pk_live_*` — from Settings → Widget form
- [ ] Set webhook secrets (required by `mvp:staging-check`):
  - `MAILGUN_WEBHOOK_SIGNING_KEY`
  - `DWOLLA_WEBHOOK_SECRET`
- [x] CSP: `FIL_CSP_ENABLED=true`, `FIL_CSP_REPORT_ONLY=true` initially

### 0.3b First-time database bootstrap (before first deploy)

Fresh Postgres has tables after migrate but **no roles** until seeded. The deploy script runs `legacy:import-access --execute`, but you still need **at least one real admin user** (not `@fil.test` — those fail the staging gate).

SSH once before (or right after) the first deploy attempt:

```bash
cd /home/forge/fil.on-forge.com/backend
php artisan migrate --force
php artisan legacy:import-access --execute
```

Create a staging admin (use a real email and strong password):

```bash
php artisan tinker --execute="
use App\Models\User;
use Illuminate\Support\Facades\Hash;
\$email = 'you@client.com';
\$user = User::query()->updateOrCreate(['email' => \$email], [
  'name' => 'Staging Admin',
  'first_name' => 'Staging',
  'last_name' => 'Admin',
  'password' => Hash::make('REPLACE_WITH_STRONG_PASSWORD'),
]);
\$user->syncRoles(['admin']);
echo \"Admin ready: {\$email}\n\";
"
```

Then redeploy from Forge (or run `bash ../scripts/forge-deploy.sh` from site root).

### 0.4 Deployment script

- [x] Forge → Deployment script:

```bash
cd /home/forge/fil.on-forge.com
bash scripts/forge-deploy.sh
```

- [x] First deploy: verify `composer install`, `npm ci`, migrations, **access seeders**, frontend + widget build succeed

### 0.5 Daemons

| Daemon | Forge command |
| ------ | ------------- |
| Queue | `cd /home/forge/fil.on-forge.com/backend && php artisan queue:work database --sleep=3 --tries=3 --max-time=3600` |
| Scheduler | `* * * * * cd /home/forge/fil.on-forge.com/backend && php artisan schedule:run` |

- [x] Enable both in Forge → Daemons / Scheduler

### 0.6 Backups

- [x] Enable Forge **daily database backup**
- [x] Confirm `.env` stored in Forge encrypted env only (not in git)

### 0.7 Staging gate

```bash
cd /home/forge/fil.on-forge.com/backend
php artisan mvp:staging-check
php artisan security:csp
```

- [x] Exit code **0** (fix all FAIL rows before smoke)
- [x] No `@fil.test` demo users in DB

### 0.8 Manual smoke (~15 min)

- [x] `GET https://fil.on-forge.com/api/health` → 200
- [x] `/app/login` → staff login (create admin via seeder or import)
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
- [ ] Configure Mailgun route → `POST https://fil.on-forge.com/api/webhooks/mailgun`
- [ ] Subscribe Mailgun webhook events: `delivered`, `failed`, `rejected`, `complained`, `opened`, `clicked` (see `docs/COMM_WEBHOOKS.md`)
- [ ] Test: send email from lead composer; verify delivery + webhook

### Twilio (if SMS enabled)

- [ ] Twilio account + messaging service
- [ ] Set `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_FROM_NUMBER`
- [ ] Webhooks → `/api/webhooks/twilio/inbound`, `/api/webhooks/twilio/status` (status callback required for delivered/read activity)
- [ ] Test: send SMS from composer; verify inbound reply matching

### Drip verification

- [ ] Enroll test lead in drip; confirm `SendDripStepJob` processes (queue logs)
- [ ] Re-run `mvp:staging-check` after mail config

---

## Phase 4 — Client data import

**Runbook:** [LEGACY_IMPORT_DRY_RUN.md](./LEGACY_IMPORT_DRY_RUN.md)

- [ ] Obtain latest client `.sql.gz`; upload to server (not in git)
- [ ] Set `FIL_LEGACY_DUMP_PATH`, `FIL_LEGACY_TABLE_PREFIX`
- [ ] Fresh DB (from `backend/`):

```bash
cd /home/forge/fil.on-forge.com/backend
php artisan migrate --force
```

- [ ] Pipeline (same directory; pass dump path or set `FIL_LEGACY_DUMP_PATH`):

  1. `php artisan legacy:import-acf`
  2. `php artisan legacy:sync-interest-region-terms /path/to/dump.sql.gz --execute`
  3. `php artisan legacy:import /path/to/dump.sql.gz` (dry-run — review counts)
  4. `php artisan legacy:import /path/to/dump.sql.gz --execute --force --confirm=legacy-import`
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
| Health | `GET https://fil.on-forge.com/api/health` |
| Staging gate | `cd /home/forge/fil.on-forge.com/backend && php artisan mvp:staging-check` |
| CSP preview | `cd /home/forge/fil.on-forge.com/backend && php artisan security:csp` |
| E2E (staging URL) | `E2E_BASE_URL=https://fil.on-forge.com ./scripts/e2e-smoke.sh` |
| Agent context | [STAGING.local.md](./STAGING.local.md) (gitignored) |

**Related:** [DEPLOYMENT.md](./DEPLOYMENT.md) · [SECURITY_AUDIT.md](./SECURITY_AUDIT.md) financial checklist

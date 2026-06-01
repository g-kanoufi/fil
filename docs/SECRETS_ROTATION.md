# Secrets & credential rotation

**Status:** Draft (2026-05-31). Operational runbook for rotating secrets on a FIL client VPS (single-tenant Forge box). Pair with [SECURITY_AUDIT.md](./SECURITY_AUDIT.md) and [MVP_DEPLOY.md](./MVP_DEPLOY.md).

## Where secrets live

- **Runtime:** `backend/.env` on the Forge box (never committed). Forge "Environment" editor is the source of truth.
- **CI:** GitHub Actions secrets (no production secrets needed for the test matrix).
- **Never in git:** `.env`, client SQL dumps, provider keys, `APP_KEY`.

## Inventory

| Secret | Env var(s) | Used by | Rotation trigger |
|--------|-----------|---------|------------------|
| App key | `APP_KEY` | Laravel encryption, **encrypted DB columns** (Plaid/POS tokens) | Suspected leak only — see warning below |
| DB credentials | `DB_PASSWORD` (+ user) | Postgres | Quarterly / on staff offboarding |
| Session/cookie | derived from `APP_KEY` | Sanctum SPA sessions | With `APP_KEY` |
| Mailgun | `MAILGUN_SECRET`, `MAILGUN_WEBHOOK_SIGNING_KEY` | outbound mail + inbound/bounce webhook | On leak / provider rotation |
| Twilio | `TWILIO_AUTH_TOKEN` (+ SID) | SMS send + webhook signature | On leak / provider rotation |
| Dwolla | `DWOLLA_KEY`, `DWOLLA_SECRET`, `DWOLLA_WEBHOOK_SECRET` | ACH + webhook verification | On leak; before go-live |
| Plaid | `PLAID_CLIENT_ID`, `PLAID_SECRET` | bank linking | On leak; before go-live |
| Embed site keys | `FIL_EMBED_SITE_KEYS` | public widget allowlist | Per client / on leak |
| reCAPTCHA | `FIL_RECAPTCHA_SECRET_KEY` | public lead intake | On leak |
| AI proxy | `FIL_AI_SERVICE_URL` (+ key if any) | grid AI / assistant | On leak |

## Standard rotation (provider keys)

1. Generate the new key in the provider dashboard (keep the old one active).
2. Update the value in Forge → Environment; **Save**.
3. Restart PHP-FPM + queue worker (Forge "Restart" or the deploy script).
4. Smoke-test the affected path (send test mail/SMS, trigger a sandbox webhook).
5. Revoke the old key in the provider dashboard.
6. Record date + who rotated in the client ops log.

## ⚠️ APP_KEY rotation (special case)

`APP_KEY` decrypts **encrypted DB columns** — `ach_customers.profile` (Plaid tokens) and `pos_connections.credentials` (SEC-003 / SEC-021). Rotating `APP_KEY` makes existing ciphertext unreadable.

Do **not** rotate `APP_KEY` casually. If it must be rotated (confirmed compromise):

1. Put the app in maintenance mode.
2. Set the current key as `APP_PREVIOUS_KEYS` in Forge **before** generating the new key (Laravel decrypts with previous keys during migration).
3. Run `php artisan key:generate` and deploy the new `APP_KEY`.
4. Re-encrypt encrypted columns: `ach_customers.profile` (Plaid tokens) and `pos_connections.credentials`. A one-off artisan command can iterate rows, read decrypted values, and rewrite — or accept that bank/POS links must be re-established manually.
5. Plaid/Dwolla links may need re-linking if rows were not re-encrypted.
6. All staff sessions are invalidated — expect re-login.
7. Remove `APP_PREVIOUS_KEYS` after all rows are migrated and verified.

## On staff offboarding

- Rotate `DB_PASSWORD` if the person had VPS/DB access.
- Remove their Forge/GitHub access.
- Rotate any provider key they could have viewed.

## Verification

After any rotation run `php artisan mvp:staging-check` — it flags missing/placeholder secrets (Sanctum domains, embed keys, demo users).

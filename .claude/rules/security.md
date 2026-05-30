# FIL security rules

Applies to auth, API, embed, webhooks, documents, communications, and financial (Dwolla / Plaid / ACH). Full audit: `docs/SECURITY_AUDIT.md`.

## Stop-ship patterns (never regress)

- **Webhooks:** verify signatures before processing (Dwolla HMAC, Twilio, Mailgun, Plaid JWT). Use idempotency via `webhook_events`.
- **Production/staging:** fail closed — no sandbox Dwolla/Plaid when creds missing; empty `FIL_EMBED_SITE_KEYS` rejects embed intake.
- **Franchise scope:** list endpoints and policies must use `ResourceScopeService` (leads, stores, activity, comms, documents, FDD, closings, ACH).
- **Secrets at rest:** encrypt sensitive JSON (`encrypted:array` on models — e.g. ACH profile, POS credentials).
- **Public intake:** minimal response DTO (no internal IDs); honeypot; reCAPTCHA when enabled.

## Financial / ACH

- Dwolla enroll requires prior client-token session (`AchDwollaEnrollmentSession`); client-token actions are allowlisted.
- ACH transfers: idempotent per `(store_id, royalty_period_id)`; persist `correlation_id`.
- Store-nested financial routes: `$this->authorize('view', $store)` plus permission checks.

## Documents & redirects

- `DocumentPolicy` + scope service for franchise boundaries.
- `preview_url` redirects: validate host via `PreviewUrlValidator` allowlist; serve attachments with `Content-Disposition: attachment`.

## Session & login

- Login throttle in production/staging only (`ThrottleStaffLogin`).
- Production: `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax` — checked by `mvp:staging-check`.

## Before go-live

Run `php artisan mvp:staging-check` and resolve FAIL rows. See `docs/MVP_DEPLOY.md` and `docs/PRODUCTION_READINESS.md`.

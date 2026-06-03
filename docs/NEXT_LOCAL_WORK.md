# Next work — local status

Local P1–P3 engineering is **complete**. Optional stretch (not blocking staging): SEC-015 redirect test, SEC-013 boot test, Playwright comms-denial E2E, `legacy:import-stream`, admin ACH reconciliation UI, per-subject PII export bundle.

**What's next:** [PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md) Phase 0 → [FORGE_STAGING_CHECKLIST.md](./FORGE_STAGING_CHECKLIST.md)

**Last updated:** 2026-06-01 · **Tests:** 385 Pest · 68 Vitest · 10 Playwright

---

## Blocked on external services

| Task | Blocker |
|------|---------|
| Phase 0 staging ship | Forge VPS + env (template: [`backend/.env.staging.example`](../backend/.env.staging.example)) |
| CSP enforce on staging | Browser validation with Plaid/Dwolla sandbox keys |
| Phase 4 import on staging | Client dump + Forge |
| Mail/SMS prod hardening | Mailgun + Twilio credentials |
| Dwolla/Plaid live ACH | Sandbox/prod API keys |
| AI search in prod | `FIL_AI_SERVICE_URL` |
| Sentry | DSN |
| Pentest / SOC review | Vendor engagement |

When Plaid/Dwolla sandbox keys arrive: run [CSP live validation](./MVP_DEPLOY.md#csp-live-validation-plaid--dwolla--recaptcha) on staging.

---

## Verification (every chunk)

```bash
cd backend && composer pint:test && php artisan test --compact
php artisan openapi:audit --fail-on-drift
cd frontend && npm run test:run && npm run build
php artisan mvp:staging-check   # staging env after migrate
php artisan security:csp        # preview CSP header on staging
php artisan legacy:finalize --strict   # after legacy import
```

---

## Related docs

- [PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md)
- [FORGE_STAGING_CHECKLIST.md](./FORGE_STAGING_CHECKLIST.md)
- [MVP_DEPLOY.md](./MVP_DEPLOY.md)
- [SECURITY_AUDIT.md](./SECURITY_AUDIT.md)
- [LEGACY_IMPORT_DRY_RUN.md](./LEGACY_IMPORT_DRY_RUN.md)
- [LOCAL_DEV.md](./LOCAL_DEV.md)

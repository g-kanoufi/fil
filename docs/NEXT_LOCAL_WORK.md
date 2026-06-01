# Next work — no Forge, no API keys

Work that can proceed **locally** without Laravel Forge, Mailgun, Twilio, Dwolla, Plaid, or AI service credentials.

**Last updated:** 2026-06-01 (P3 pre-staging prep complete)

---

## Recently completed (local)

| Item | Notes |
|------|--------|
| **Staging env template (P3 #7)** | [`backend/.env.staging.example`](../backend/.env.staging.example) + `mvp:staging-check` gate table in [MVP_DEPLOY.md](./MVP_DEPLOY.md) |
| **CSP validation prep (P3 #8)** | `security:csp` command, CSP + SPA asset checks in `mvp:staging-check`, report-only runbook in MVP_DEPLOY |
| **Store postmeta gap triage (P1 #1)** | Bundled ACF catalog + document patterns; real dump → **0 unmapped** store keys |
| **Lead postmeta gap audit (P1 #2)** | Same triage pass; real dump → **0 unmapped** lead keys |
| **Interest region legacy term sync** | `legacy:sync-interest-region-terms` — 43 seeded links + 30 market regions |
| **Closing/fee CSV export (P2 #4)** | `GET /v1/closings/export` + Export CSV on Closings page |
| **E2E smoke expansion (P2 #5)** | Lead custom fields panel + widget demo — `e2e/tests/product-polish.spec.ts` |
| **Vitest bump (P2 #6)** | `EntityCustomFieldsPanel`, `closingsExport`, CSP-safe `notificationPreview` |

**Test counts:** **384** Pest · **68** Vitest · **10** Playwright specs

---

## Priority queue (local-only)

_No open P1–P3 items._ Next local work depends on Forge VPS or sandbox API keys — see blocked table below.

**When Plaid/Dwolla sandbox keys arrive:** run the CSP live validation checklist in [MVP_DEPLOY.md](./MVP_DEPLOY.md#csp-live-validation-plaid--dwolla--recaptcha) on staging.

---

## Blocked on external services

| Task | Blocker |
|------|---------|
| Phase 0 staging ship | Forge VPS + env (template ready: `.env.staging.example`) |
| CSP enforce on staging | Browser validation with Plaid/Dwolla sandbox keys |
| Phase 4 import on staging | Client dump + Forge |
| Mail/SMS prod hardening | Mailgun + Twilio credentials |
| Dwolla/Plaid live ACH | Sandbox/prod API keys |
| AI search in prod | `FIL_AI_SERVICE_URL` |
| Sentry | DSN |
| Pentest / SOC review | Vendor engagement |

---

## Verification (every chunk)

```bash
cd backend && composer pint:test && php artisan test --compact
php artisan openapi:audit --fail-on-drift
cd frontend && npm run test:run && npm run build
php artisan mvp:staging-check   # after migrate + seed (staging env)
php artisan security:csp        # preview CSP header on staging
php artisan legacy:finalize --strict   # after legacy import
```

---

## Related docs

- [PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md)
- [MVP_DEPLOY.md](./MVP_DEPLOY.md)
- [SECURITY_AUDIT.md](./SECURITY_AUDIT.md)
- [LEGACY_IMPORT_DRY_RUN.md](./LEGACY_IMPORT_DRY_RUN.md)
- [LEGACY_MAPPING_GAPS.md](./LEGACY_MAPPING_GAPS.md)
- [LOCAL_DEV.md](./LOCAL_DEV.md)

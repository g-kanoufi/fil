# Next work — no Forge, no API keys

Work that can proceed **locally** without Laravel Forge, Mailgun, Twilio, Dwolla, Plaid, or AI service credentials.

**Last updated:** 2026-06-01 (P1 + P2 complete)

---

## Recently completed (local)

| Item | Notes |
|------|--------|
| **Store postmeta gap triage (P1 #1)** | Bundled ACF catalog + document patterns; real dump → **0 unmapped** store keys — [LEGACY_MAPPING_GAPS.md](./LEGACY_MAPPING_GAPS.md) |
| **Lead postmeta gap audit (P1 #2)** | Same triage pass; real dump → **0 unmapped** lead keys |
| **Interest region legacy term sync (P1 #2)** | `legacy:sync-interest-region-terms` — 43 seeded links + 30 market regions from PrimeIV dump; auto-runs before postmeta import |
| **Closing/fee CSV export (P2 #4)** | `GET /v1/closings/export` + Export CSV on Closings page; one row per fee line |
| **E2E smoke expansion (P2 #5)** | Lead custom fields panel + widget demo page — `e2e/tests/product-polish.spec.ts` |
| **Vitest bump (P2 #6)** | `EntityCustomFieldsPanel`, `closingsExport`, CSP-safe `notificationPreview` |
| **Legacy mapping gaps** | `legacy:mapping-gaps` command + `LegacyMappingGapsService`; `out_of_scope` notes in `fil-legacy-acf.php` |
| **Parity spot-checks** | `legacy:parity-report --samples` — field_values, interest_region_id, extras, documents |
| **Interest regions** | `interest_regions` table + US/CA seeder, admin CRUD API + Settings UI, `leads.interest_region_id`, legacy `area_of_interest` → region |
| Legacy extras drain | ACF-aware `legacy:drain-extras`; `legacy:finalize --strict` green (0 staged extras) |
| SEC follow-ups | Plaid ITEM/AUTH webhooks, CSP allowlist, per-site-key intake throttle, import `--confirm=legacy-import` + audit |
| P1–P3 quality pass | AG Grid split, empty states, Pint/ESLint, OpenAPI audit, a11y, activity export API |
| Security docs | [SECURITY_AUDIT.md](./SECURITY_AUDIT.md), [SECRETS_ROTATION.md](./SECRETS_ROTATION.md), [PII_RETENTION.md](./PII_RETENTION.md) |

**Test counts:** **379** Pest · **68** Vitest · **10** Playwright specs

---

## Priority queue (local-only)

### P3 — Pre-staging prep (still local)

| # | Task | Effort | Notes |
|---|------|--------|-------|
| 7 | **Staging env template** | 0.5d | Document required Forge env vars + `mvp:staging-check` gate in [MVP_DEPLOY.md](./MVP_DEPLOY.md) |
| 8 | **CSP live validation** | 0.5d | When Plaid/Dwolla sandbox keys available — tune `FIL_CSP_*` allowlist; optional `FIL_CSP_REPORT_ONLY=true` first |

---

## Blocked on external services

| Task | Blocker |
|------|---------|
| Phase 0 staging ship | Forge VPS + env |
| Phase 4 import on staging | Client dump + Forge |
| Mail/SMS prod hardening | Mailgun + Twilio credentials |
| Dwolla/Plaid live ACH | Sandbox/prod API keys |
| AI search in prod | `FIL_AI_SERVICE_URL` |
| Sentry | DSN |
| Pentest / SOC review | Vendor engagement |

---

## Suggested next feature branch

```bash
git checkout dev
git pull
git checkout -b feature/staging-env-template
```

---

## Verification (every chunk)

```bash
cd backend && composer pint:test && php artisan test --compact
php artisan openapi:audit --fail-on-drift
cd frontend && npm run test:run && npm run build
php artisan mvp:staging-check   # after migrate + seed
php artisan legacy:finalize --strict   # after legacy import
```

---

## Related docs

- [PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md)
- [SECURITY_AUDIT.md](./SECURITY_AUDIT.md)
- [LEGACY_IMPORT_DRY_RUN.md](./LEGACY_IMPORT_DRY_RUN.md)
- [LEGACY_MAPPING_GAPS.md](./LEGACY_MAPPING_GAPS.md)
- [LOCAL_DEV.md](./LOCAL_DEV.md)

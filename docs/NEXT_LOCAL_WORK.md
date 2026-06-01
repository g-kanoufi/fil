# Next work — no Forge, no API keys

Work that can proceed **locally** without Laravel Forge, Mailgun, Twilio, Dwolla, Plaid, or AI service credentials.

**Last updated:** 2026-06-01 (legacy mapping gaps + parity samples)

---

## Recently completed (local)

| Item | Notes |
|------|--------|
| **Legacy mapping gaps (P1 #1)** | `legacy:mapping-gaps` command + `LegacyMappingGapsService`; `out_of_scope` notes in `fil-legacy-acf.php`; [LEGACY_MAPPING_GAPS.md](./LEGACY_MAPPING_GAPS.md) |
| **Parity spot-checks (P1 #2)** | `legacy:parity-report --samples` — field_values, interest_region_id, extras, documents |
| **Interest regions** | `interest_regions` table + US/CA seeder, admin CRUD API + Settings UI, `leads.interest_region_id`, legacy `area_of_interest` → region |
| Legacy extras drain | ACF-aware `legacy:drain-extras`; `legacy:finalize --strict` green (0 staged extras) |
| SEC follow-ups | Plaid ITEM/AUTH webhooks, CSP allowlist, per-site-key intake throttle, import `--confirm=legacy-import` + audit, failed-Dwolla batch test — **360** Pest tests |
| P1–P3 quality pass | AG Grid split, empty states, Pint/ESLint, OpenAPI audit, a11y, activity export API |
| Security docs | [SECURITY_AUDIT.md](./SECURITY_AUDIT.md), [SECRETS_ROTATION.md](./SECRETS_ROTATION.md), [PII_RETENTION.md](./PII_RETENTION.md) |

---

## Priority queue (local-only)

### P1 — Data & parity

| # | Task | Effort | Notes |
|---|------|--------|-------|
| 1 | **Real dump gap audit** | 0.5d | Run `legacy:mapping-gaps` on `data/local.sql.gz` for store + lead; tune `out_of_scope` if new patterns appear |
| 2 | **Interest region legacy term IDs** | 0.5d | Set `legacy_term_id` on subdivisions when import term IDs differ from seeded US/CA rows |

### P2 — Product polish (no external deps)

| # | Task | Effort | Notes |
|---|------|--------|-------|
| 4 | **Closing/fee CSV export** | 0.5d | Phase 3.4 — mirror activity export pattern |
| 5 | **E2E smoke expansion** | 1d | Lead custom fields panel, widget demo page — extend `scripts/e2e-smoke.sh` |
| 6 | **Vitest bump** | 0.5d | Cover `EntityCustomFieldsPanel`, CSP-safe notification preview |

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
git checkout -b feature/legacy-mapping-gaps
```

---

## Verification (every chunk)

```bash
cd backend && php artisan test --compact
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

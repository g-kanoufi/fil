# Next work — local status

Local P1–P3 engineering is **complete**.

**Staging live:** https://fil.on-forge.com — see [STAGING.local.md](./STAGING.local.md) (gitignored) and [FORGE_STAGING_CHECKLIST.md](./FORGE_STAGING_CHECKLIST.md).

**Last updated:** 2026-06-03 · **Tests:** backend Pest · 76 Vitest · **20 Playwright** (`./scripts/e2e-smoke.sh`)

**Plan 3** (four-stage platform A–D) is merged to **`dev`**. Sidebar shows unit status filters directly (no “My Units” parent); **Areas** lives at `/reports/areas` with franchise territories + US/Canada defaults.

Next engineering focus: **Phase 4 — legacy import** on staging.

---

## Staging — what's left (ops)

| Priority | Task | Doc |
| --- | --- | --- |
| 1 | Finish manual smoke (grid, lead detail, widget, queue logs) | FORGE_STAGING_CHECKLIST §0.8 |
| 2 | Embed + webhook env if not set | FORGE_STAGING_CHECKLIST §0.3 |
| 3 | **Phase 4** — upload client dump + legacy import pipeline | LEGACY_IMPORT_DRY_RUN |
| 4 | Mailgun sandbox + composer test | FORGE_STAGING_CHECKLIST § Phase 2 |
| 5 | Plaid/Dwolla + CSP enforce | Phase 2b |

---

## Blocked on external services

| Task | Blocker |
|------|---------|
| CSP enforce on staging | Browser validation with Plaid/Dwolla sandbox keys |
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
# On staging server:
cd /home/forge/fil.on-forge.com/backend && php artisan mvp:staging-check
```

---

## Zorzees gap review (2026-06-01)

- [ZORZEES_GAP_REVIEW.md](./ZORZEES_GAP_REVIEW.md) — feature matrix vs legacy
- [V1_SCOPE_DECISIONS.md](./V1_SCOPE_DECISIONS.md) — long-form portal post-v1 until client confirms
- [BUG_PASS_RESULTS.md](./BUG_PASS_RESULTS.md) — automated + E2E results
- `php artisan legacy:spot-check` — post-import tier-1 samples

## Related docs

- [PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md)
- [FORGE_STAGING_CHECKLIST.md](./FORGE_STAGING_CHECKLIST.md)
- [STAGING.example.md](./STAGING.example.md)
- [MVP_DEPLOY.md](./MVP_DEPLOY.md)
- [LEGACY_IMPORT_DRY_RUN.md](./LEGACY_IMPORT_DRY_RUN.md)

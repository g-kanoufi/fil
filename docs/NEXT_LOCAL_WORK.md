# Next work — no Forge, no API keys

Work that can proceed **locally** without Laravel Forge, Mailgun, Twilio, Dwolla, Plaid, or AI service credentials.

**Last updated:** 2026-05-28

---

## Done on `dev`

| Item | Notes |
|------|--------|
| Legacy import dry-run docs | [LEGACY_IMPORT_DRY_RUN.md](./LEGACY_IMPORT_DRY_RUN.md) — Phase 4 checklist |
| Grid export polish | Shared `ExportCsvButton`, `fil-*` filename helpers, full activity feed export |
| Closing detail workflow | `PATCH /api/v1/closings/{closing}`, status transitions, fee lines, list + detail UI |
| Contact custom fields (P-026) | `PATCH /api/v1/contacts/{contact}`, `field_values` entity `contact`, detail panel, seeder |
| Activity CSV export | History page + lead/store/contact timelines (client-side) |
| `mvp:staging-check` | No crash when roles not seeded |
| Forbidden page | Consistent `TextLink` styling |

---

## Priority queue (local-only)

### P1 — High value, no external deps

| # | Task | Effort | Notes |
|---|------|--------|-------|
| 1 | **PHPUnit coverage gaps** | 1d | Activity export auth paths, document scope edge cases |

### P2 — Quality & design

| # | Task | Effort | Notes |
|---|------|--------|-------|
| 5 | **AG Grid code-split** | 1d | Dynamic import on grid routes (build warns >600kB chunk) |
| 6 | **Empty states audit** | 0.5d | Standardize empty/error/loading across detail pages |
| 7 | **Pint / ESLint pass** | 0.5d | Add Laravel Pint to CI if not present; fix autofixable issues |
| 8 | **OpenAPI sync audit** | 1d | Diff `docs/api.openapi.yaml` vs live routes |
| 9 | **Accessibility pass** | 1d | Login, grids, modals — focus trap, labels, axe on key pages |

### P3 — Security & compliance (still local)

| # | Task | Effort | Notes |
|---|------|--------|-------|
| 10 | **Audit log export API** | 1d | Staff-only CSV/JSON export of `activity_events` (scoped) |
| 11 | **Secrets rotation runbook** | 0.5d | Doc only — `docs/SECRETS_ROTATION.md` |
| 12 | **PII retention policy draft** | 0.5d | Doc only — align with client legal |
| 13 | **Dependency audit automation** | 0.5d | CI fails on high/critical `composer audit` / `npm audit` |

---

## Blocked on external services (do not start locally)

| Task | Blocker |
|------|---------|
| Phase 0 staging ship | Forge VPS + env |
| Mail/SMS prod hardening | Mailgun + Twilio credentials |
| Dwolla/Plaid live ACH | Sandbox/prod API keys |
| AI search in prod | `FIL_AI_SERVICE_URL` |
| Sentry | DSN |
| Client data import sign-off | Client DB dump on staging |

---

## Suggested next feature branch

```bash
git checkout dev && git pull
git checkout -b feature/phpunit-coverage-gaps
```

**Scope:** Activity export auth paths and document scope edge case tests.

---

## Verification (every chunk)

```bash
cd backend && php artisan test --compact
cd frontend && npm run test:run && npm run build
php artisan mvp:staging-check   # after migrate + seed
```

---

## Related docs

- [PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md)
- [SECURITY_AUDIT.md](./SECURITY_AUDIT.md)
- [parity-checklist.md](./parity-checklist.md)
- [LOCAL_DEV.md](./LOCAL_DEV.md)

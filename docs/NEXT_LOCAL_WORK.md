# Next work — no Forge, no API keys

Work that can proceed **locally** without Laravel Forge, Mailgun, Twilio, Dwolla, Plaid, or AI service credentials.

**Last updated:** 2026-05-31

---

## Done on `dev`

| Item | Notes |
|------|--------|
| Legacy import dry-run docs | [LEGACY_IMPORT_DRY_RUN.md](./LEGACY_IMPORT_DRY_RUN.md) — Phase 4 checklist |
| Test coverage (Pest) | Activity feed auth/scope + document scope/export edge cases — 306 Pest tests |
| Grid export polish | Shared `ExportCsvButton`, `fil-*` filename helpers, full activity feed export |
| Closing detail workflow | `PATCH /api/v1/closings/{closing}`, status transitions, fee lines, list + detail UI |
| Contact custom fields (P-026) | `PATCH /api/v1/contacts/{contact}`, `field_values` entity `contact`, detail panel, seeder |
| Activity CSV export | History page + lead/store/contact timelines (client-side) |
| `mvp:staging-check` | No crash when roles not seeded |
| Forbidden page | Consistent `TextLink` styling |
| AG Grid code-split | `FilGridLazy` on grid routes |
| PostgreSQL local default | Docker Postgres + legacy import on pgsql (see LOCAL_DEV.md) |
| Empty states audit | `EntityLoadState`, `AsyncSection`, compact `EmptyState` on detail pages |
| OpenAPI sync audit | `php artisan openapi:audit --fail-on-drift`, 114/114 routes |
| Pint / ESLint pass | `pint.json`, `eslint.config.js`, CI `pint --test` + `npm run lint` |
| Accessibility pass | `ModalDialog`, `useDialogA11y`, login/grid/modal ARIA — 57 Vitest |
| Pest 4 migration | 306 Pest tests; see [PEST_STANDARD.md](./PEST_STANDARD.md) |
| Activity export API | `GET /api/v1/activity/export` CSV/JSON, scoped + filters; History "Export (server)" button (`1dc0e79`) |
| Security truth-up | Verified SEC-001…025 → accurate status table; dep-audit CI now blocking; compliance docs ([SECURITY_AUDIT.md](./SECURITY_AUDIT.md), [SECRETS_ROTATION.md](./SECRETS_ROTATION.md), [PII_RETENTION.md](./PII_RETENTION.md)) |

---

## Priority queue (local-only)

### P1 — High value, no external deps

| # | Task | Effort | Notes |
|---|------|--------|-------|
| 1 | ~~**AG Grid code-split**~~ | — | Done |

### P2 — Quality & design

| # | Task | Effort | Notes |
|---|------|--------|-------|
| 2 | ~~**Empty states audit**~~ | — | Done |
| 3 | ~~**Pint / ESLint pass**~~ | — | Pint on 70 files, ESLint flat config, CI gates |
| 4 | ~~**OpenAPI sync audit**~~ | — | `openapi:audit` command (CI `--fail-on-drift`) |
| 5 | ~~**Accessibility pass**~~ | — | Done — committed on `dev` (quality pass) |

### P3 — Security & compliance (still local)

| # | Task | Effort | Notes |
|---|------|--------|-------|
| 10 | ~~**Audit log export API**~~ | — | Done — `GET /api/v1/activity/export` (CSV/JSON, scoped); merged `1dc0e79` |
| 11 | ~~**Secrets rotation runbook**~~ | — | Done — [SECRETS_ROTATION.md](./SECRETS_ROTATION.md) |
| 12 | ~~**PII retention policy draft**~~ | — | Done — [PII_RETENTION.md](./PII_RETENTION.md) (pending client legal) |
| 13 | ~~**Dependency audit automation**~~ | — | Done — CI `composer audit` / `npm audit --audit-level=high` now blocking |
| 14 | ~~**SEC local hardening**~~ | — | Done — SEC-012 (login audit + lockout), SEC-018 (.env template), SEC-020 (DOMPurify + security headers), SEC-022 (AI PII strip), SEC-025 (magic-byte upload), SEC-008 (UI guard) |
| 15 | **SEC follow-ups (local)** | 1–2d | SEC-003 Plaid ITEM/AUTH webhook + `APP_KEY` rotation note; SEC-020 CSP allowlist (Plaid/Dwolla/reCAPTCHA/widget) + browser validation; SEC-019/023 intake & import — see [SECURITY_AUDIT.md](./SECURITY_AUDIT.md) |

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
git checkout dev
# Next P3 (local): git checkout -b feature/dependency-audit-ci
# Or compliance docs: SECRETS_ROTATION.md, PII retention draft
# Phase 0 staging (needs Forge): git checkout -b feature/staging-phase-0
```

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

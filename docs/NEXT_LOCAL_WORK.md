# Next work — no Forge, no API keys

Work that can proceed **locally** without Laravel Forge, Mailgun, Twilio, Dwolla, Plaid, or AI service credentials.

**Last updated:** 2026-05-31

**Next session handoff:** [NEXT_SESSION.md](./NEXT_SESSION.md) — paste the chat starter into a new chat to execute.

---

## Done on `dev`

| Item | Notes |
|------|--------|
| Legacy import dry-run docs | [LEGACY_IMPORT_DRY_RUN.md](./LEGACY_IMPORT_DRY_RUN.md) — Phase 4 checklist |
| PHPUnit coverage gaps | Activity feed auth/scope + document scope/export edge cases (290 tests) |
| Grid export polish | Shared `ExportCsvButton`, `fil-*` filename helpers, full activity feed export |
| Closing detail workflow | `PATCH /api/v1/closings/{closing}`, status transitions, fee lines, list + detail UI |
| Contact custom fields (P-026) | `PATCH /api/v1/contacts/{contact}`, `field_values` entity `contact`, detail panel, seeder |
| Activity CSV export | History page + lead/store/contact timelines (client-side) |
| `mvp:staging-check` | No crash when roles not seeded |
| Forbidden page | Consistent `TextLink` styling |
| AG Grid code-split | `FilGridLazy` on grid routes |
| PostgreSQL local default | Docker Postgres + legacy import on pgsql (see LOCAL_DEV.md) |
| Empty states audit | `EntityLoadState`, `AsyncSection`, compact `EmptyState` on detail pages |
| OpenAPI sync audit | `php artisan openapi:audit`, 113/113 routes — [API_OPENAPI_AUDIT.md](./API_OPENAPI_AUDIT.md) |
| Pint / ESLint pass | `pint.json`, `eslint.config.js`, CI `pint --test` + `npm run lint` |
| Accessibility pass | `ModalDialog`, `useDialogA11y`, login/grid/modal ARIA — 50 Vitest |
| Pest 4 migration | 294 Pest tests; see [PEST_REVIEW.md](./PEST_REVIEW.md) |

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
| 4 | ~~**OpenAPI sync audit**~~ | — | `openapi:audit` command + [API_OPENAPI_AUDIT.md](./API_OPENAPI_AUDIT.md) |
| 5 | ~~**Accessibility pass**~~ | — | Done — see [NEXT_SESSION.md](./NEXT_SESSION.md) Block 1 to commit |

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

See [NEXT_SESSION.md](./NEXT_SESSION.md) for the full ordered plan.

```bash
git checkout dev
# Block 1: land uncommitted work (quality pass)
# Block 3A: git checkout -b feature/activity-export-api
# Block 2:  git checkout -b feature/staging-phase-0   # needs Forge
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

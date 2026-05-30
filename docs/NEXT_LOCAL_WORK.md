# Next work — no Forge, no API keys

Work that can proceed **locally** without Laravel Forge, Mailgun, Twilio, Dwolla, Plaid, or AI service credentials.

**Last updated:** 2026-05-30

---

## Done this branch (`feature/local-hardening-and-cleanup`)

| Item | Notes |
|------|--------|
| Activity CSV export | History page + lead/store/contact timelines (client-side) |
| `mvp:staging-check` | No crash when roles not seeded |
| Forbidden page | Consistent `TextLink` styling |
| Docs | This plan + readiness/security status refresh |

---

## Priority queue (local-only)

### P1 — High value, no external deps

| # | Task | Effort | Notes |
|---|------|--------|-------|
| 1 | **Contact custom fields (P-002)** | 2–3d | Mirror lead `field_values` on contacts; admin + detail UI |
| 2 | **Closing detail workflow UI** | 1–2d | Status transitions, fee line display (read/write against existing API) |
| 3 | **Grid export polish** | 0.5d | Consistent export button placement, filename conventions |
| 4 | **Legacy import dry-run docs** | 0.5d | Document `legacy:import --dry-run` checklist for Phase 4 prep |
| 5 | **PHPUnit coverage gaps** | 1d | Activity export auth paths, document scope edge cases |

### P2 — Quality & design

| # | Task | Effort | Notes |
|---|------|--------|-------|
| 6 | **AG Grid code-split** | 1d | Dynamic import on grid routes (build warns >600kB chunk) |
| 7 | **Empty states audit** | 0.5d | Standardize empty/error/loading across detail pages |
| 8 | **Pint / ESLint pass** | 0.5d | Add Laravel Pint to CI if not present; fix autofixable issues |
| 9 | **OpenAPI sync audit** | 1d | Diff `docs/api.openapi.yaml` vs live routes |
| 10 | **Accessibility pass** | 1d | Login, grids, modals — focus trap, labels, axe on key pages |

### P3 — Security & compliance (still local)

| # | Task | Effort | Notes |
|---|------|--------|-------|
| 11 | **Audit log export API** | 1d | Staff-only CSV/JSON export of `activity_events` (scoped) |
| 12 | **Secrets rotation runbook** | 0.5d | Doc only — `docs/SECRETS_ROTATION.md` |
| 13 | **PII retention policy draft** | 0.5d | Doc only — align with client legal |
| 14 | **Dependency audit automation** | 0.5d | CI fails on high/critical `composer audit` / `npm audit` |

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

After merging this branch to `dev`:

```bash
git checkout dev && git pull
git checkout -b feature/contact-custom-fields
```

**Scope:** P-002 contact custom fields — backend field schema extension, contact detail panel, PHPUnit + Vitest.

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

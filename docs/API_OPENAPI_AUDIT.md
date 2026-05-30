# OpenAPI sync audit

> **Spec:** [api.openapi.yaml](./api.openapi.yaml) · **Routes:** `backend/routes/api.php`

FIL follows **OpenAPI-first** for API changes (see [AGENTS.md](../AGENTS.md)). This doc tracks how we keep the spec aligned with Laravel routes.

**Last audit:** 2026-05-30 — **113/113** route operations documented (summary stubs; detailed schemas on high-traffic paths).

---

## Run the audit

```bash
cd backend
php artisan openapi:audit
```

Options:

| Flag | Purpose |
|------|---------|
| `--fail-on-drift` | Exit 1 if any live route is missing from the spec (CI gate) |
| `--write-missing` | Append summary-only stubs for paths not yet in the YAML |
| `--spec=path` | Alternate spec file |

After adding routes:

1. Implement the endpoint
2. Run `php artisan openapi:audit --write-missing` (adds new **paths** only)
3. For new methods on an **existing** path, edit `docs/api.openapi.yaml` manually
4. Enrich stubs with request/response schemas for public or high-traffic endpoints
5. `php artisan openapi:audit --fail-on-drift` must pass before merge

---

## Coverage tiers

| Tier | Paths | Detail level |
|------|-------|--------------|
| **A** | `/v1/query/{resource}`, `/v1/contacts/{contact}`, `/v1/closings/*` | Request/response schemas |
| **B** | All other staff + public + webhook routes | Summary stub |
| **C** | Future | Full schemas as endpoints stabilize |

---

## Webhooks

Webhook routes are included in the spec (`/webhooks/*`) for inventory purposes. They are **not** staff SPA routes; document provider payloads when hardening Phase 2 comms.

---

## CI (recommended)

Add to `.github/workflows/ci.yml` after PHPUnit:

```yaml
- name: OpenAPI drift check
  run: cd backend && php artisan openapi:audit --fail-on-drift
```

---

## Related

- [api.openapi.yaml](./api.openapi.yaml)
- [METADATA.md](./METADATA.md)
- [NEXT_LOCAL_WORK.md](./NEXT_LOCAL_WORK.md)

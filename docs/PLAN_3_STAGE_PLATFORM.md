# Plan 3 — Stage-complete Franchise Intelligence Platform

**Status:** Active (feature/plan-3-stage-platform)  
**Supersedes:** [V1_SCOPE_DECISIONS.md](./V1_SCOPE_DECISIONS.md) default on long-form portal (now in v1 under Plan 3).

Organize FIL as a **four-stage platform** aligned with Zorzees ICP v3 / Franchise Intelligence positioning:

| Stage | Label | FIL modules |
| --- | --- | --- |
| 1 | **Find + Sell** | Widget, prospect portal, leads, FDD, drips, comms, activity |
| 2 | **Build + Open** | Closings, lead→store convert, opening checklist |
| 3 | **Operate + Inspect** | Stores, contacts, documents, franchisee views |
| 4 | **Grow + Earn** | Royalties, ACH, POS sync, performance dashboards |

Config: `backend/config/fil-platform.php` · Catalog: `PlatformStageCatalog`.

---

## Phase A — Find + Sell

| # | Deliverable | Status |
| --- | --- | --- |
| A1 | Prospect portal (`/portal`) — login, password setup, long-form application | ☑ foundation |
| A2 | E-sign vendor integration (Item 23) | ☑ foundation |
| A3 | Widget → portal redirect + reCAPTCHA verify on staging | ☑ |
| A4 | Pipeline phases tagged to `find_sell` stage in config | ☑ |

## Phase B — Build + Open

| # | Deliverable | Status |
| --- | --- | --- |
| B1 | `store_opening_checklist_items` + staff UI on store detail | ☑ |
| B2 | Build-out timeline fields on stores | ☑ |
| B3 | Closing → documents links | ☑ |

---

## Phase C — Operate + Inspect

| # | Deliverable | Status |
| --- | --- | --- |
| C1 | Corp notes + lightweight todos API | ☑ foundation |
| C2 | Inspection-due notifications (field + rule, not full fl-inspections) | ☑ |
| C3 | Franchisee store dashboard (read-only ops summary) | ☑ |

---

## Phase D — Grow + Earn

| # | Deliverable | Status |
| --- | --- | --- |
| D1 | Enable royalty calc job on staging (`FIL_ENABLE_ROYALTY_CALC_JOB`) | ☑ |
| D2 | ACH reconciliation admin UI | ☑ |
| D3 | Square POS adapter (first real sync) | ☑ foundation |
| D4 | Royalties intelligence dashboard (unit vs area) | ☑ |

---

## Verification (each phase)

```bash
cd backend && php artisan test --compact
php artisan openapi:audit --fail-on-drift
cd frontend && npm run test:run && npm run build
./scripts/e2e-smoke.sh   # when stack running
```

---

## Related

- [ZORZEES_GAP_REVIEW.md](./ZORZEES_GAP_REVIEW.md)
- [PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md)
- [AUTH.md](./AUTH.md) — staff vs prospect surfaces

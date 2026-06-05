# v1 scope decisions (Zorzees → FIL gap review)

**Last updated:** 2026-06-01  
**Status:** Engineering defaults until client sign-off. Update rows when the client confirms.

## Decision log

| # | Topic | Default for v1 | Post-v1 / out of scope | Rationale |
| --- | --- | --- | --- | --- |
| 1 | **Prospect long-form portal** (password + multi-step Applications ACF) | **In v1 (Plan 3 Phase A)** | — | `/portal` surface + `/api/portal/v1/*`; see [PLAN_3_STAGE_PLATFORM.md](./PLAN_3_STAGE_PLATFORM.md) |
| 2 | **fl-signature / Item 23 e-sign** | Portal FDD sign + vendor drivers (`local`, `sandbox`, `dropbox_sign`) | Full vendor webhook hardening | Plan 3 Phase A2 foundation shipped. |
| 3 | **legacy:import-stream** (WP Stream backfill) | Optional stretch | — | Runtime activity replaced ([ACTIVITY_HISTORY.md](./ACTIVITY_HISTORY.md) Phase D). |
| 4 | **POS revenue sync** | Stub / manual | Square/Clover/Booker adapters | MVP: POS sync not required. |
| 5 | **ACH admin reconciliation UI** | Post-v1 unless launch needs fees | SEC-007 daily reconcile command | Phase 3.4 open ([PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md)). |
| 6 | **Areas / organizations admin UI** | **Areas:** staff CRUD at `/reports/areas` (2026-06) | **Organizations:** import + `GET /v1/organizations` only unless client needs UI | Areas needed for territory edits without re-import. |
| 7 | **Closings in sidebar nav** | **In v1** (shipped) | — | Feature complete; was deep-link only — added to nav 2026-06-01. |
| 8 | **Per-subject PII export bundle** | Post-v1 | — | [NEXT_LOCAL_WORK.md](./NEXT_LOCAL_WORK.md) optional stretch. |
| 9 | **fl-marketing, fl-operations, fl-inspections, website galleries** | Out of scope | Phase 8 / WP public site | [AGENTS.md](../AGENTS.md) scope (out). |

## Client confirmation (fill in)

| # | Client answer | Date | Notes |
| --- | --- | --- | --- |
| 1 | _Pending_ | | If **required for v1**, add prospect routes + long-form wizard before cutover; blocks parity with Z stages 3–4. |
| 2 | _Pending_ | | |
| 5 | _Pending_ | | |

## Related

- Gap review summary: [ZORZEES_GAP_REVIEW.md](./ZORZEES_GAP_REVIEW.md)
- Parity IDs: [parity-checklist.md](./parity-checklist.md)
- Import verification: `php artisan legacy:spot-check`

# Zorzees → FIL feature gap review

**Last updated:** 2026-06-03  
**Purpose:** Single reference for staff CRM parity vs legacy Zorzees; complements [PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md).

**Lead status / pipeline (2026-06):** Application status menus and grid filters read `app-config.lead_application_status` (field catalog + DB orphans). Canonical column is `leads.lead_status`; `lead_fdd_status` is mirrored until normalized via `leads:normalize-status`. See [schema-mapping.md](./schema-mapping.md) § Lead lifecycle.

## Summary

| Question | Answer |
| --- | --- |
| Staff CRM parity (P-001–P-033) | Code-complete locally — see [parity-checklist.md](./parity-checklist.md) |
| Full Zorzees replacement | No — several `fl-*` plugins, Stream backfill, POS, full e-sign still open; prospect portal Phase A shipped |
| Blocking go-live | Ops (Forge staging), Phase 4 import on client dump, Mailgun/Twilio/Dwolla prod verification |
| v1 scope decisions | [V1_SCOPE_DECISIONS.md](./V1_SCOPE_DECISIONS.md) |

## Plugin mapping (abbreviated)

| Zorzees | FIL | Status |
| --- | --- | --- |
| fl-react | Staff SPA + API | Replaced |
| fl-salespipeline | Embed widget + prospect portal | Short form + `/portal` long-form (Plan 3 Phase A) |
| z-communications | Communications + webhooks | Code done; prod creds pending |
| z-royalties-and-fees | Royalties API | Jobs env-gated |
| z-ach-transfer | ACH UI + webhooks | Sandbox verify pending |
| z-pos-integrations | POS stub | No adapter |
| fl-signature | FddSignModal | Partial |
| Stream | activity_events | No `legacy:import-stream` |
| fl-marketing, fl-operations, fl-inspections, … | — | Out of scope |

## Gaps by priority

### B — Product / engineering (tracked)

1. Prospect long-form portal — **Phase A shipped** ([PLAN_3_STAGE_PLATFORM.md](./PLAN_3_STAGE_PLATFORM.md)); polish + legacy field parity ongoing
2. `legacy:import-stream` — optional
3. ~~Closings nav~~ — added to sidebar
4. Areas / organizations — no admin UI
5. ACH reconciliation UI — **Phase D shipped** ([PLAN_3_STAGE_PLATFORM.md](./PLAN_3_STAGE_PLATFORM.md))
6. POS — Square adapter foundation (sandbox fallback); OAuth polish open
7. FDD PDF placeholder + staff sign vs fl-signature
8. Activity FTS — optional

### C — Prove in staging

Import + parity-report, Twilio SMS, Dwolla/Plaid + CSP, widget origins, royalty/ACH jobs when flags on.

## Verification commands

```bash
cd backend && php artisan test --compact
php artisan legacy:spot-check --samples=10   # after legacy:import --execute
php artisan mvp:staging-check
cd .. && ./scripts/e2e-smoke.sh              # when stack running
```

Bug-review checklist: [LOCAL_DEV.md](./LOCAL_DEV.md) § What to test in the browser.

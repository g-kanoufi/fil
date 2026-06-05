# FIL parity checklist

Map legacy CRM behavior to FIL tests. Legacy plugin names are reference-only for import tooling.

**Status tracking:** [PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md) · **Docs index:** [README.md](./README.md)

---

## Platform & auth

| ID | Legacy behavior | FIL test / endpoint |
| --- | --- | --- |
| P-001 | Short form creates lead phase 1 | `POST /api/public/v1/leads` feature test |
| P-002 | Staff login | `POST /api/v1/session` + `app.access` permission |
| P-002a | Prospect blocked from staff app | login returns 422; no `app.access` |
| P-002b | Session returns roles, permissions, navigation | `GET /api/v1/session` |
| P-002c | Unauthenticated staff routes redirect to login | web middleware |
| P-002d | Adminimize-style grid/nav restrictions per role | `session.ui_restrictions` from `ui_menu_items` + `role_ui_grants` |
| P-002e | React options branding/menus | `GET /api/v1/app-config` → `client_settings` + catalog |
| P-003 | Lead list/show/update | `GET/PATCH /api/v1/leads` |
| P-004 | Postgres grid query (ES-compatible) | `POST /api/v1/query/{resource}` |
| P-005 | Public widget lead intake | `POST /api/public/v1/leads` + site-key middleware |
| P-006 | Options alias (app-config parity) | `GET /api/v1/options` |
| P-007 | Stores/areas/organizations index | `GET /api/v1/stores`, `/areas`, `/organizations` |
| P-008 | ACF field import command | `php artisan legacy:import-acf` |

---

## CRM features

| ID | Legacy behavior | FIL test / endpoint | Status |
| --- | --- | --- | --- |
| P-020 | Lead pipeline phase transition | `POST /api/v1/leads/{id}/transition-phase` | ☑ |
| P-021 | FDD send to lead (single) | `POST /api/v1/leads/{id}/fdd/send` | ☑ |
| P-022 | Bulk FDD from leads grid | Grid selection + bulk send modal | ☑ |
| P-023 | Grid search / filter leads tab | `POST /api/v1/query/leads` | ☑ |
| P-023a | Sidebar facet counts (`meta.lead_status` buckets) | `aggregations.*.buckets` with `doc_count` | ☑ |
| P-023b | Dashboard leads-by-status chart | `POST /api/v1/query/leads` with `size: 0` | ☑ |
| P-023c | Dynamic lead_owner filter menu | `aggregations.meta.lead_owner.buckets` | ☑ |
| P-024 | SMS/email composer (staff) | `POST /api/v1/communications` | ☑ |
| P-025 | Activity timeline (lead + contact) | `GET /api/v1/activity` + domain merge | ☑ |
| P-026 | Contact custom fields | `PATCH /api/v1/contacts/{contact}` + detail panel | ☑ |
| P-027 | Store royalties calculate | `POST /api/v1/royalties/calculate` | ☑ |
| P-028 | ACH funding sources | `GET /api/v1/ach/customer/fundingsources/{storeId}` | ☑ |
| P-029 | SMS outbound (provider webhooks) | Twilio webhook + communications log | ☐ Phase 2 (Twilio prod creds) |
| P-030 | Email drip step | Queue job `SendDripStepJob` | Partial — email path tested; SMS needs Twilio prod |
| P-032 | API 401/staff → login; policy 403 → forbidden page | ☑ |
| P-033 | Franchise scope tiers (`ResourceScopeService`) | ☑ |

**Note:** IDs P-020+ replaced duplicate P-003–P-010 rows from an earlier draft. Update tests/docs if you reference old IDs.

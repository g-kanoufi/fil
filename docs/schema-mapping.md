# FIL schema mapping (PrimeIV / legacy site 9)

Generated during Phase 1. Legacy table prefix: `vnzokz0zw_9_`.

**Metadata strategy:** see `docs/METADATA.md`. Legacy meta tables are **not** mirrored as EAV. Values land in typed columns, FKs, relation tables, or `field_values`.

## Entity mapping

| Legacy | FIL table | Notes |
|--------|-----------|-------|
| `posts.post_type=application` | `leads` | Tier-1 columns for pipeline + grid fields; long-tail → `field_values` |
| `posts.post_type=store` | `stores` | `store_status`, POS, royalty config as columns / `royalty_config` JSON |
| `posts.post_type=area` | `areas` | `approval_status`, `territory` JSON (structured geo, not meta soup) |
| `posts.post_type=organization` | `organizations` | |
| `posts.post_type=grabbafdd` / `areafdd` | `fdds` | type discriminator |
| `wp_users` + site caps | `users` + roles | profile fields on `users`; no user-meta EAV |
| `z_communications` | `communications` | typed columns + optional `meta` for provider payload only |
| `weekly_store_revenue` | `royalty_periods` | |
| `weekly_store_royalties_detail` | `royalty_line_items` | |
| `ach_transfers` | `ach_transfers` | |
| `notifications` + carriers | `notification_rules` | |
| `zai_chats` | `ai_threads` | |

## Legacy meta → FIL (leads / applications)

| Legacy meta key | FIL storage | Target |
| ----------------- | ----------- | ------ |
| `lead_status` | column | `leads.lead_status` |
| `lead_stage` | column | `leads.lead_stage` |
| `lead_fdd_status` | column | `leads.lead_fdd_status` |
| `lead_temp` | column | `leads.lead_temp` |
| `lead_source` | column | `leads.lead_source` |
| `likelihood_to_close` | column | `leads.likelihood_to_close` |
| `lead_owner` | foreign_key | `leads.owner_user_id` → `users` |
| prospect user link | foreign_key | `leads.prospect_user_id` |
| area / org | foreign_key | `leads.area_id`, `organization_id` |
| FDD / NDA dates | column | `disclosed_at`, `nda_signed_at`, `fdd_signed_at`, … |
| drip fields | column | `drip_campaign_id`, `eligible_for_drip` |
| long-tail ACF fields | field_value | `field_values` via `fields` schema |
| unmigrated keys (import only) | staging | `leads.extras` — must drain to zero |

## Legacy meta → FIL (stores)

| Legacy meta key | FIL storage | Target |
| ----------------- | ----------- | ------ |
| `store_status` | column | `stores.store_status` |
| SPA / POS IDs | column | `spa_id`, `pos_provider`, `pos_external_id` |
| royalty settings | structured JSON | `stores.royalty_config` (schema-validated, not arbitrary meta) |
| owners | relation | `store_owners` pivot |
| medical / compliance files | documents | `documents` via `doctors_license_*`, `medical_certification_*` postmeta |
| website photo galleries | out of scope | See [LEGACY_MAPPING_GAPS.md](./LEGACY_MAPPING_GAPS.md) |
| checklist plugin rows | out of scope | Retain `nso_checklist_embed` field only |

## Custom tables inventory (site 9)

See `php tools/inventory-dump.php data/local.sql.gz` or `php artisan legacy:inventory`.

Key FIL-relevant tables:

- `ach_transfers`
- `areas_royalties`
- `weekly_store_revenue`
- `weekly_store_royalties_detail`
- `z_communications`
- `zai_chats`
- `notifications`, `notification_carriers`, `notification_extras`, `notification_logs`

## ACF import source

`backend/resources/legacy-acf/*.json` (or `FIL_LEGACY_ACF_PATH`)

Maps to:

- `field_groups` — UI groups
- `fields` — definitions with `entity`, `storage`, `maps_to_column`, facet/sort flags
- `field_values` — typed values for `storage=field_value`
- Tier-1 promotion for grid/aggregation fields (see `docs/METADATA.md`)

## Configurable field tables

| Table | Purpose |
| ----- | ------- |
| `field_groups` | Grouping, location rules, sort order |
| `fields` | Schema: type, choices, storage tier, role rules |
| `field_role_rules` | Per-role read/write/hide |
| `field_values` | Typed custom values (replaces legacy meta for extensions) |

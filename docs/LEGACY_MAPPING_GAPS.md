# Legacy mapping gaps

Audit and decisions for legacy postmeta keys that do not map cleanly to FIL tier-1 columns, `field_values`, or the documents browser.

**PrimeIV dump (local):** store and lead entities → **0 unmapped** keys after triage. Re-run commands below after any new client dump.

**Commands**

```bash
php artisan legacy:mapping-gaps ../data/local.sql.gz              # store (default)
php artisan legacy:mapping-gaps ../data/local.sql.gz --entity=lead
php artisan legacy:mapping-gaps ../data/local.sql.gz --entity=store --post-type=franchise_location
php artisan legacy:parity-report ../data/local.sql.gz --samples
```

Exit code **1** from `legacy:mapping-gaps` means unmapped keys remain above `--min` threshold.

---

## Bundled ACF coverage

| Source JSON | FIL field group | Entity | Legacy post type |
|-------------|-----------------|--------|------------------|
| `group_5e55f6ed3094a.json` | `units` | `store` | `store` |
| `group_570fc6f67d6f6.json` | `locations` | `store` | `franchise_location` |
| `group_5f6adcab783f1.json` | `units-client-fields` | `store` | `store` |
| `group_56b9dc28339ca.json` | `areas` | `area` | `area` |

Compliance file fields classify as **document** patterns from unit/location ACF groups → `legacy:import --only=documents`. Remaining scalars drain via `legacy:import-acf` + postmeta drain. Lead financial subfields use `group_5654f5590ab60.json`; repeater container keys and legacy-only flags are discarded.

---

## Intentionally out of scope (FIL CRM MVP)

| Category | Example keys / prefixes | Reason |
|----------|-------------------------|--------|
| Public website photos | `finished_photos_group_*`, `lead_photo_for_website`, `website_card_photo` | No public locations CMS in FIL |
| Construction photos | `shell_building_photos_*`, `under_construction_photos_*`, `site_audit_photos_*` | Optional future media module |
| Legacy checklist plugin | `checklist_*`, `_checklist_*`, `1_checklist*` | Flattened rows; retain `nso_checklist_embed` (URL) only |
| Area marketing photos | `area_website_*` | Public site assets on area CPT |

Configuration: `config/fil-legacy-acf.php` → `extras_discard_prefixes` + `out_of_scope`.

---

## Mapped elsewhere (not field_values)

| Pattern | FIL destination |
|---------|-------------------|
| `*_license_*_file`, `medical_certification_*_file` | `documents` via `legacy:import --only=documents` |
| `store_status`, `spa_id`, `pos_*` | Typed columns on `stores` |
| `area`, `area_cpt_store` | `stores.area_id` via postmeta import |

---

## Interest regions (leads)

Legacy `area_of_interest` maps to `leads.interest_region_id`, **not** franchise `areas.area_id`. Run `legacy:sync-interest-region-terms --execute` before postmeta import.

---

## When you find a new gap

1. Run `legacy:mapping-gaps --entity=store --min=3` after import.
2. CRM UI field → bundled ACF JSON or tier-1 in `fil-legacy-acf.php` → re-run `legacy:import-acf`.
3. File attachment → extend `fil-documents.php` patterns + document import.
4. Legacy-only / public-site → add prefix to `out_of_scope`.
5. Re-run `legacy:drain-extras --execute` and `legacy:finalize --strict`.

See also: [schema-mapping.md](./schema-mapping.md) · [LEGACY_IMPORT_DRY_RUN.md](./LEGACY_IMPORT_DRY_RUN.md)

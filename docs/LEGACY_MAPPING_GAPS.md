# Legacy mapping gaps

Audit and decisions for legacy postmeta keys that do not map cleanly to FIL tier-1 columns, `field_values`, or the documents browser.

**Commands**

```bash
# Store postmeta audit (default entity)
php artisan legacy:mapping-gaps ../data/local.sql.gz

# Other entities
php artisan legacy:mapping-gaps ../data/local.sql.gz --entity=lead
php artisan legacy:mapping-gaps ../data/local.sql.gz --entity=location

# Post-import row counts + field spot checks
php artisan legacy:parity-report ../data/local.sql.gz --samples
```

Exit code **1** from `legacy:mapping-gaps` means unmapped keys remain above `--min` threshold — add schema, document out-of-scope, or extend drain rules.

---

## Store entity — bundled ACF coverage

| Source JSON | FIL field group | Entity | Notes |
|-------------|-----------------|--------|-------|
| `units.json` | `units` | `store` | Core unit fields, status, owners, royalties UI |
| `locations.json` | `locations` | `store` | Franchise location / build-out fields |
| `store-client-fields.json` | `units` (merge) | `store` | Medical docs repeaters — same legacy group key `group_5f6adcab783f1` |
| `areas.json` | `areas` | `area` | Franchise territories (not interest regions) |

`store-client-fields.json` is **not** a separate UI group — it merges into `units` during `legacy:import-acf`. File meta (`doctors_license_*_file`) is imported via `legacy:import-documents` using patterns from `config/fil-documents.php`.

---

## Intentionally out of scope (FIL CRM MVP)

These keys may exist in legacy postmeta but are **not** promoted to `field_values`:

| Category | Example keys / prefixes | Reason |
|----------|-------------------------|--------|
| Public website photos | `finished_photos_group_*`, `lead_photo_for_website`, `website_card_photo`, `supporting_photos_for_website` | WordPress attachment IDs for marketing site — FIL has no public locations CMS |
| Construction photos | `shell_building_photos_*`, `under_construction_photos_*`, `site_audit_photos_*`, `demo_photos_*` | Attachment galleries; optional future media module |
| Legacy checklist plugin | `checklist_*`, `_checklist_*`, `1_checklist*` | Flattened checklist rows; retain `nso_checklist_embed` (URL) only |
| Area marketing photos | `area_website_*` | Public site assets on area CPT |

Configuration: `config/fil-legacy-acf.php` → `extras_discard_prefixes` + `out_of_scope` (human-readable notes for `legacy:mapping-gaps`).

---

## Mapped elsewhere (not field_values)

| Pattern | FIL destination |
|---------|-------------------|
| `*_license_*_file`, `medical_certification_*_file` | `documents` table via `legacy:import-documents` |
| `store_status`, `spa_id`, `pos_*` | Typed columns on `stores` |
| `area`, `area_cpt_store` | `stores.area_id` via postmeta import |

---

## Interest regions (leads)

Legacy `area_of_interest` (taxonomy `grabba_tax_area`) maps to `leads.interest_region_id`, **not** franchise `areas.area_id`. Set `legacy_term_id` on interest region subdivisions when legacy term IDs differ from seeded US/CA rows.

---

## When you find a new gap

1. Run `legacy:mapping-gaps --entity=store --min=3` after import.
2. If the key belongs in CRM UI → add to bundled ACF JSON (or tier-1 in `fil-legacy-acf.php`) and re-run `legacy:import-acf`.
3. If it is a file attachment → extend `fil-documents.php` patterns + document import.
4. If it is legacy-only / public-site → add prefix to `out_of_scope` with a one-line reason.
5. Re-run `legacy:drain-extras --execute` and `legacy:finalize --strict`.

See also: [schema-mapping.md](./schema-mapping.md) · [LEGACY_IMPORT_DRY_RUN.md](./LEGACY_IMPORT_DRY_RUN.md)
